<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Tests\Integration\Event;

use function dirname;
use function file_get_contents;

use Gerard\ClaudeCodeHooks\Event\HookEventFactory;
use Gerard\ClaudeCodeHooks\Event\Tool\Input\AgentInput;
use Gerard\ClaudeCodeHooks\Event\Tool\Input\BashInput;
use Gerard\ClaudeCodeHooks\Event\Tool\Input\GlobInput;
use Gerard\ClaudeCodeHooks\Event\Tool\Input\GrepInput;
use Gerard\ClaudeCodeHooks\Event\Tool\Input\RawToolInput;
use Gerard\ClaudeCodeHooks\Event\Tool\PostToolUseEvent;
use Gerard\ClaudeCodeHooks\Event\Turn\UserPromptSubmitEvent;
use Gerard\ClaudeCodeHooks\Support\JsonDecoder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Pins specific field-level assertions that are easy to mutate without breaking
 * the smoke `RealCorpusRoundTripTest`. Every fixture loaded here exercises a
 * concrete real-corpus shape that motivated a recent code change:
 *
 *   - `dangerouslyDisableSandbox` on Bash (12/1979 in the corpus)
 *   - `run_in_background` + `backgroundTaskId` Bash response (5/1979)
 *   - `-n`, `head_limit`, `-A`, `-i` Grep flags (75/139, 18/139, 3/139, 10/139)
 *   - `agent_id` / `agent_type` ambient context (4426/4714 PostToolUse)
 *   - `session_title` on UserPromptSubmit (48/48)
 *   - `ToolSearch` / `Monitor` / `TaskStop` falling back to `RawToolInput`
 *
 * REDACTION CHECKLIST is documented at the top of `RealCorpusRoundTripTest`.
 */
#[CoversClass(HookEventFactory::class)]
#[CoversClass(PostToolUseEvent::class)]
#[CoversClass(UserPromptSubmitEvent::class)]
#[CoversClass(BashInput::class)]
#[CoversClass(GrepInput::class)]
#[CoversClass(GlobInput::class)]
#[CoversClass(AgentInput::class)]
#[CoversClass(RawToolInput::class)]
final class RealCorpusFieldsTest extends TestCase
{
    public function testBashFixturePinsDangerouslyDisableSandbox(): void
    {
        $payload = $this->load('PostToolUse-Bash-DangerouslyDisableSandbox.json');
        self::assertIsArray($payload);

        $event = (new HookEventFactory())->fromPayload($payload);

        self::assertInstanceOf(PostToolUseEvent::class, $event);
        self::assertInstanceOf(BashInput::class, $event->toolInput);
        self::assertTrue($event->toolInput->dangerouslyDisableSandbox);
        self::assertSame('lead-developer', $event->agentType);
        self::assertSame('agent_REDACTED', $event->agentId);
    }

    public function testBashFixturePinsBackgroundTaskShape(): void
    {
        $payload = $this->load('PostToolUse-Bash-Background.json');
        self::assertIsArray($payload);

        $event = (new HookEventFactory())->fromPayload($payload);

        self::assertInstanceOf(PostToolUseEvent::class, $event);
        self::assertInstanceOf(BashInput::class, $event->toolInput);
        self::assertTrue($event->toolInput->runInBackground);
        self::assertSame(600000, $event->toolInput->timeout);
        self::assertNull($event->toolInput->dangerouslyDisableSandbox);
        // Background-mode bash responses ship `backgroundTaskId` instead of stdout/stderr.
        self::assertIsArray($event->toolResponse);
        self::assertArrayHasKey('backgroundTaskId', $event->toolResponse);
    }

    public function testGrepFixturePinsLineNumbersAndHeadLimit(): void
    {
        $payload = $this->load('PostToolUse-Grep.json');
        self::assertIsArray($payload);

        $event = (new HookEventFactory())->fromPayload($payload);

        self::assertInstanceOf(PostToolUseEvent::class, $event);
        self::assertInstanceOf(GrepInput::class, $event->toolInput);
        self::assertTrue($event->toolInput->lineNumbers);
        self::assertSame(30, $event->toolInput->headLimit);
        self::assertSame('content', $event->toolInput->outputMode);
        self::assertNull($event->toolInput->caseInsensitive);
    }

    public function testGrepFixturePinsCaseInsensitiveAndLinesAfter(): void
    {
        $payload = $this->load('PostToolUse-Grep-CaseInsensitive.json');
        self::assertIsArray($payload);

        $event = (new HookEventFactory())->fromPayload($payload);

        self::assertInstanceOf(PostToolUseEvent::class, $event);
        self::assertInstanceOf(GrepInput::class, $event->toolInput);
        self::assertTrue($event->toolInput->caseInsensitive);
        self::assertSame(3, $event->toolInput->linesAfter);
        self::assertNull($event->toolInput->lineNumbers);
        self::assertNull($event->toolInput->headLimit);
    }

    public function testGlobFixtureAcceptsPatternOnly(): void
    {
        $payload = $this->load('PostToolUse-Glob.json');
        self::assertIsArray($payload);

        $event = (new HookEventFactory())->fromPayload($payload);

        self::assertInstanceOf(PostToolUseEvent::class, $event);
        self::assertInstanceOf(GlobInput::class, $event->toolInput);
        self::assertNull($event->toolInput->path);
    }

    public function testAgentFixturePinsSubagentRouting(): void
    {
        $payload = $this->load('PostToolUse-Agent.json');
        self::assertIsArray($payload);

        $event = (new HookEventFactory())->fromPayload($payload);

        self::assertInstanceOf(PostToolUseEvent::class, $event);
        self::assertInstanceOf(AgentInput::class, $event->toolInput);
        self::assertSame('sdet', $event->toolInput->subagentType);
        self::assertNull($event->toolInput->model);
    }

    public function testToolSearchFallsBackToRawToolInput(): void
    {
        $payload = $this->load('PostToolUse-ToolSearch.json');
        self::assertIsArray($payload);

        $event = (new HookEventFactory())->fromPayload($payload);

        self::assertInstanceOf(PostToolUseEvent::class, $event);
        self::assertInstanceOf(RawToolInput::class, $event->toolInput);
        self::assertSame('ToolSearch', $event->toolInput->name);
        self::assertArrayHasKey('query', $event->toolInput->payload);
        self::assertArrayHasKey('max_results', $event->toolInput->payload);
    }

    public function testMonitorFallsBackToRawToolInput(): void
    {
        $payload = $this->load('PostToolUse-Monitor.json');
        self::assertIsArray($payload);

        $event = (new HookEventFactory())->fromPayload($payload);

        self::assertInstanceOf(PostToolUseEvent::class, $event);
        self::assertInstanceOf(RawToolInput::class, $event->toolInput);
        self::assertSame('Monitor', $event->toolInput->name);
        self::assertSame(60000, $event->toolInput->payload['timeout_ms'] ?? null);
        self::assertTrue($event->toolInput->payload['persistent'] ?? false);
    }

    public function testTaskStopFallsBackToRawToolInput(): void
    {
        $payload = $this->load('PostToolUse-TaskStop.json');
        self::assertIsArray($payload);

        $event = (new HookEventFactory())->fromPayload($payload);

        self::assertInstanceOf(PostToolUseEvent::class, $event);
        self::assertInstanceOf(RawToolInput::class, $event->toolInput);
        self::assertSame('TaskStop', $event->toolInput->name);
        self::assertArrayHasKey('task_id', $event->toolInput->payload);
    }

    public function testUserPromptSubmitFixturePinsSessionTitle(): void
    {
        $payload = $this->load('UserPromptSubmit.json');
        self::assertIsArray($payload);

        $event = (new HookEventFactory())->fromPayload($payload);

        self::assertInstanceOf(UserPromptSubmitEvent::class, $event);
        self::assertSame('[REDACTED] · review', $event->sessionTitle);
    }

    private function load(string $name): mixed
    {
        $path = dirname(__DIR__, 2) . '/Fixtures/payloads/' . $name;
        $contents = file_get_contents($path);
        self::assertNotFalse($contents, 'Fixture must be readable: ' . $name);

        return JsonDecoder::decode($contents, $path);
    }
}
