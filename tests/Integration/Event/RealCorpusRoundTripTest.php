<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Tests\Integration\Event;

use function basename;
use function dirname;
use function file_get_contents;

use Generator;
use Gerard\ClaudeCodeHooks\Event\HookEventFactory;
use Gerard\ClaudeCodeHooks\Event\Session\SessionEndEvent;
use Gerard\ClaudeCodeHooks\Event\Tool\Input\AgentInput;
use Gerard\ClaudeCodeHooks\Event\Tool\Input\BashInput;
use Gerard\ClaudeCodeHooks\Event\Tool\Input\EditInput;
use Gerard\ClaudeCodeHooks\Event\Tool\Input\GlobInput;
use Gerard\ClaudeCodeHooks\Event\Tool\Input\GrepInput;
use Gerard\ClaudeCodeHooks\Event\Tool\Input\RawToolInput;
use Gerard\ClaudeCodeHooks\Event\Tool\Input\ReadInput;
use Gerard\ClaudeCodeHooks\Event\Tool\Input\SkillInput;
use Gerard\ClaudeCodeHooks\Event\Tool\Input\TodoWriteInput;
use Gerard\ClaudeCodeHooks\Event\Tool\Input\WriteInput;
use Gerard\ClaudeCodeHooks\Event\Tool\PostToolUseEvent;
use Gerard\ClaudeCodeHooks\Event\Turn\StopEvent;
use Gerard\ClaudeCodeHooks\Event\Turn\UserPromptSubmitEvent;
use Gerard\ClaudeCodeHooks\Support\JsonDecoder;

use function glob;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * AC-1: every distinct hook payload variant observed in a real-world corpus
 * (4089 rows) round-trips through `HookEventFactory::fromPayload()` without
 * exception. This test iterates 8 anonymised JSON fixtures, one per defect
 * class / new tool DTO.
 *
 * REDACTION CHECKLIST — apply ALL of these before committing a fixture.
 *   session_id        → "00000000-0000-0000-0000-000000000000"
 *   transcript_path   → "/tmp/[REDACTED].jsonl"
 *   cwd               → "/tmp/[REDACTED]"
 *   prompt            → "[REDACTED]"
 *   tool_input.command, .description, .file_path, .pattern, .url → "[REDACTED]"
 *   tool_input.todos[*].content, .activeForm                     → "[REDACTED]"
 *   tool_input.args, .skill                                       → "[REDACTED]"
 *   tool_response.stdout, .stderr, .content, .matches             → "[REDACTED]"
 *   tool_use_id      → "toolu_REDACTED"
 *   agent_id         → "agent_REDACTED"
 * Verify before commit:
 *   grep -nrE '(AKIA[0-9A-Z]{16}|ASIA[0-9A-Z]{16}|ghp_[A-Za-z0-9]{36}|xoxb-|xoxp-|sk-|eyJ[A-Za-z0-9_-]+\.|Bearer\s+|-----BEGIN [A-Z ]+PRIVATE KEY-----)' \
 *        tests/Fixtures/payloads/
 *   (must produce zero matches)
 */
#[CoversClass(HookEventFactory::class)]
#[CoversClass(PostToolUseEvent::class)]
#[CoversClass(SessionEndEvent::class)]
#[CoversClass(StopEvent::class)]
#[CoversClass(UserPromptSubmitEvent::class)]
#[CoversClass(BashInput::class)]
#[CoversClass(ReadInput::class)]
#[CoversClass(WriteInput::class)]
#[CoversClass(EditInput::class)]
#[CoversClass(GlobInput::class)]
#[CoversClass(GrepInput::class)]
#[CoversClass(AgentInput::class)]
#[CoversClass(TodoWriteInput::class)]
#[CoversClass(SkillInput::class)]
#[CoversClass(RawToolInput::class)]
final class RealCorpusRoundTripTest extends TestCase
{
    /**
     * @return Generator<string, array{0: string, 1: string}>
     */
    public static function realCorpusFixtures(): Generator
    {
        $dir = dirname(__DIR__, 2) . '/Fixtures/payloads';
        $files = glob($dir . '/PostToolUse-*.json');
        if ($files === false) {
            $files = [];
        }
        foreach ($files as $file) {
            yield basename($file, '.json') => [$file, 'PostToolUse'];
        }

        yield 'SessionEnd' => [$dir . '/SessionEnd.json', 'SessionEnd'];
        yield 'Stop' => [$dir . '/Stop.json', 'Stop'];
        yield 'UserPromptSubmit' => [$dir . '/UserPromptSubmit.json', 'UserPromptSubmit'];
    }

    #[DataProvider('realCorpusFixtures')]
    public function test_real_corpus_payload_round_trips_through_factory(
        string $fixturePath,
        string $expectedHookEventName,
    ): void {
        $payload = JsonDecoder::decode((string) file_get_contents($fixturePath), $fixturePath);

        $event = (new HookEventFactory())->fromPayload($payload);

        self::assertSame($expectedHookEventName, $event->hookEventName);
    }
}
