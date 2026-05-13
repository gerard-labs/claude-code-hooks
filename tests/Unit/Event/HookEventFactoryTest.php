<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Tests\Unit\Event;

use Gerard\ClaudeCodeHooks\Event\Compact\PostCompactEvent;
use Gerard\ClaudeCodeHooks\Event\Compact\PreCompactEvent;
use Gerard\ClaudeCodeHooks\Event\Ctx\ConfigChangeEvent;
use Gerard\ClaudeCodeHooks\Event\Ctx\CwdChangedEvent;
use Gerard\ClaudeCodeHooks\Event\Ctx\FileChangedEvent;
use Gerard\ClaudeCodeHooks\Event\Ctx\InstructionsLoadedEvent;
use Gerard\ClaudeCodeHooks\Event\Ctx\WorktreeCreateEvent;
use Gerard\ClaudeCodeHooks\Event\Ctx\WorktreeRemoveEvent;
use Gerard\ClaudeCodeHooks\Event\Family;
use Gerard\ClaudeCodeHooks\Event\HookEventFactory;
use Gerard\ClaudeCodeHooks\Event\Perm\ElicitationEvent;
use Gerard\ClaudeCodeHooks\Event\Perm\ElicitationResultEvent;
use Gerard\ClaudeCodeHooks\Event\Perm\NotificationEvent;
use Gerard\ClaudeCodeHooks\Event\Perm\PermissionDeniedEvent;
use Gerard\ClaudeCodeHooks\Event\Perm\PermissionRequestEvent;
use Gerard\ClaudeCodeHooks\Event\Session\SessionEndEvent;
use Gerard\ClaudeCodeHooks\Event\Session\SessionStartEvent;
use Gerard\ClaudeCodeHooks\Event\Session\SetupEvent;
use Gerard\ClaudeCodeHooks\Event\Team\TaskCompletedEvent;
use Gerard\ClaudeCodeHooks\Event\Team\TaskCreatedEvent;
use Gerard\ClaudeCodeHooks\Event\Team\TeammateIdleEvent;
use Gerard\ClaudeCodeHooks\Event\Tool\PostToolBatchEvent;
use Gerard\ClaudeCodeHooks\Event\Tool\PostToolUseEvent;
use Gerard\ClaudeCodeHooks\Event\Tool\PostToolUseFailureEvent;
use Gerard\ClaudeCodeHooks\Event\Tool\PreToolUseEvent;
use Gerard\ClaudeCodeHooks\Event\Turn\StopEvent;
use Gerard\ClaudeCodeHooks\Event\Turn\StopFailureEvent;
use Gerard\ClaudeCodeHooks\Event\Turn\SubagentStartEvent;
use Gerard\ClaudeCodeHooks\Event\Turn\SubagentStopEvent;
use Gerard\ClaudeCodeHooks\Event\Turn\UserPromptExpansionEvent;
use Gerard\ClaudeCodeHooks\Event\Turn\UserPromptSubmitEvent;
use Gerard\ClaudeCodeHooks\Event\UnknownHookEvent;
use Gerard\ClaudeCodeHooks\Exception\InvalidPayloadException;

use const JSON_THROW_ON_ERROR;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(HookEventFactory::class)]
#[CoversClass(UnknownHookEvent::class)]
#[CoversClass(SetupEvent::class)]
#[CoversClass(SessionStartEvent::class)]
#[CoversClass(SessionEndEvent::class)]
#[CoversClass(UserPromptSubmitEvent::class)]
#[CoversClass(UserPromptExpansionEvent::class)]
#[CoversClass(StopEvent::class)]
#[CoversClass(StopFailureEvent::class)]
#[CoversClass(SubagentStartEvent::class)]
#[CoversClass(SubagentStopEvent::class)]
#[CoversClass(PreToolUseEvent::class)]
#[CoversClass(PostToolUseEvent::class)]
#[CoversClass(PostToolUseFailureEvent::class)]
#[CoversClass(PostToolBatchEvent::class)]
#[CoversClass(PermissionRequestEvent::class)]
#[CoversClass(PermissionDeniedEvent::class)]
#[CoversClass(NotificationEvent::class)]
#[CoversClass(ElicitationEvent::class)]
#[CoversClass(ElicitationResultEvent::class)]
#[CoversClass(PreCompactEvent::class)]
#[CoversClass(PostCompactEvent::class)]
#[CoversClass(InstructionsLoadedEvent::class)]
#[CoversClass(ConfigChangeEvent::class)]
#[CoversClass(CwdChangedEvent::class)]
#[CoversClass(FileChangedEvent::class)]
#[CoversClass(WorktreeCreateEvent::class)]
#[CoversClass(WorktreeRemoveEvent::class)]
#[CoversClass(TaskCreatedEvent::class)]
#[CoversClass(TaskCompletedEvent::class)]
#[CoversClass(TeammateIdleEvent::class)]
final class HookEventFactoryTest extends TestCase
{
    private HookEventFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new HookEventFactory();
    }

    public function testItReturnsUnknownEventForUndocumentedName(): void
    {
        $event = $this->factory->fromPayload([
            'session_id' => 'sess', 'transcript_path' => '/p', 'cwd' => '/c',
            'permission_mode' => 'default', 'hook_event_name' => 'TotallyNew',
            'extra' => ['foo' => 'bar'],
        ]);
        self::assertInstanceOf(UnknownHookEvent::class, $event);
        self::assertSame(Family::Unknown, $event->family());
        self::assertSame('TotallyNew', $event->hookEventName);
        self::assertArrayHasKey('extra', $event->rawPayload);
    }

    public function testItRejectsPayloadMissingHookEventName(): void
    {
        self::expectException(InvalidPayloadException::class);
        $this->factory->fromPayload(['session_id' => 's']);
    }

    public function testItRejectsNonStringHookEventName(): void
    {
        self::expectException(InvalidPayloadException::class);
        $this->factory->fromPayload(['hook_event_name' => 42]);
    }

    /**
     * @return iterable<string, array{0: string, 1: array<string, mixed>, 2: class-string, 3: Family}>
     */
    public static function payloadProvider(): iterable
    {
        $base = [
            'session_id' => 's', 'transcript_path' => '/p', 'cwd' => '/c',
            'permission_mode' => 'default',
        ];

        yield 'Setup' => ['Setup', $base + ['hook_event_name' => 'Setup', 'trigger' => 'init'], SetupEvent::class, Family::Session];
        yield 'SessionStart' => ['SessionStart', $base + ['hook_event_name' => 'SessionStart', 'source' => 'startup'], SessionStartEvent::class, Family::Session];
        yield 'SessionEnd' => ['SessionEnd', $base + ['hook_event_name' => 'SessionEnd', 'reason' => 'logout'], SessionEndEvent::class, Family::Session];
        yield 'UserPromptSubmit' => ['UserPromptSubmit', $base + ['hook_event_name' => 'UserPromptSubmit', 'prompt' => 'hi'], UserPromptSubmitEvent::class, Family::Turn];
        yield 'UserPromptExpansion' => ['UserPromptExpansion', $base + ['hook_event_name' => 'UserPromptExpansion', 'expansion_type' => 'slash_command', 'command_name' => 'foo', 'prompt' => 'p'], UserPromptExpansionEvent::class, Family::Turn];
        yield 'Stop' => ['Stop', $base + ['hook_event_name' => 'Stop', 'stop_hook_active' => false], StopEvent::class, Family::Turn];
        yield 'StopFailure' => ['StopFailure', $base + ['hook_event_name' => 'StopFailure', 'error_type' => 'rate_limit', 'error_message' => '429'], StopFailureEvent::class, Family::Turn];
        yield 'SubagentStart' => ['SubagentStart', $base + ['hook_event_name' => 'SubagentStart', 'agent_type' => 'Explore', 'prompt' => 'p'], SubagentStartEvent::class, Family::Turn];
        yield 'SubagentStop' => ['SubagentStop', $base + ['hook_event_name' => 'SubagentStop', 'agent_type' => 'Explore'], SubagentStopEvent::class, Family::Turn];
        yield 'PreToolUse' => ['PreToolUse', $base + ['hook_event_name' => 'PreToolUse', 'tool_name' => 'Bash', 'tool_input' => ['command' => 'ls']], PreToolUseEvent::class, Family::Tool];
        yield 'PostToolUse' => ['PostToolUse', $base + ['hook_event_name' => 'PostToolUse', 'tool_name' => 'Bash', 'tool_input' => ['command' => 'ls'], 'tool_response' => 'output'], PostToolUseEvent::class, Family::Tool];
        yield 'PostToolUseFailure' => ['PostToolUseFailure', $base + ['hook_event_name' => 'PostToolUseFailure', 'tool_name' => 'Bash', 'tool_input' => ['command' => 'ls'], 'error' => 'boom'], PostToolUseFailureEvent::class, Family::Tool];
        yield 'PostToolBatch' => ['PostToolBatch', $base + ['hook_event_name' => 'PostToolBatch', 'tool_calls' => [['tool_name' => 'Bash']]], PostToolBatchEvent::class, Family::Tool];
        yield 'PermissionRequest' => ['PermissionRequest', $base + ['hook_event_name' => 'PermissionRequest', 'tool_name' => 'Bash', 'tool_input' => ['command' => 'ls']], PermissionRequestEvent::class, Family::Perm];
        yield 'PermissionDenied' => ['PermissionDenied', $base + ['hook_event_name' => 'PermissionDenied', 'tool_name' => 'Bash', 'tool_input' => ['command' => 'ls']], PermissionDeniedEvent::class, Family::Perm];
        yield 'Notification' => ['Notification', $base + ['hook_event_name' => 'Notification', 'notification_type' => 'permission_prompt', 'message' => 'hi'], NotificationEvent::class, Family::Perm];
        yield 'Elicitation' => ['Elicitation', $base + ['hook_event_name' => 'Elicitation', 'mcp_server' => 's', 'tool_name' => 't', 'elicitation_type' => 'select'], ElicitationEvent::class, Family::Perm];
        yield 'ElicitationResult' => ['ElicitationResult', $base + ['hook_event_name' => 'ElicitationResult', 'mcp_server' => 's', 'tool_name' => 't', 'user_response' => ['ok' => true]], ElicitationResultEvent::class, Family::Perm];
        yield 'PreCompact' => ['PreCompact', $base + ['hook_event_name' => 'PreCompact', 'trigger' => 'auto'], PreCompactEvent::class, Family::Compact];
        yield 'PostCompact' => ['PostCompact', $base + ['hook_event_name' => 'PostCompact', 'trigger' => 'auto'], PostCompactEvent::class, Family::Compact];
        yield 'InstructionsLoaded' => ['InstructionsLoaded', $base + ['hook_event_name' => 'InstructionsLoaded', 'file_path' => 'CLAUDE.md', 'memory_type' => 'Project', 'load_reason' => 'session_start', 'globs' => ['src/**']], InstructionsLoadedEvent::class, Family::Ctx];
        yield 'ConfigChange' => ['ConfigChange', $base + ['hook_event_name' => 'ConfigChange', 'config_source' => 'project_settings', 'changed_keys' => ['hooks']], ConfigChangeEvent::class, Family::Ctx];
        yield 'CwdChanged' => ['CwdChanged', $base + ['hook_event_name' => 'CwdChanged', 'old_cwd' => '/a', 'new_cwd' => '/b'], CwdChangedEvent::class, Family::Ctx];
        yield 'FileChanged' => ['FileChanged', $base + ['hook_event_name' => 'FileChanged', 'file_path' => '.envrc', 'change_type' => 'modified'], FileChangedEvent::class, Family::Ctx];
        yield 'WorktreeCreate' => ['WorktreeCreate', $base + ['hook_event_name' => 'WorktreeCreate', 'worktree_isolation' => true, 'base_path' => '/wt'], WorktreeCreateEvent::class, Family::Ctx];
        yield 'WorktreeRemove' => ['WorktreeRemove', $base + ['hook_event_name' => 'WorktreeRemove', 'worktree_path' => '/wt'], WorktreeRemoveEvent::class, Family::Ctx];
        yield 'TaskCreated' => ['TaskCreated', $base + ['hook_event_name' => 'TaskCreated', 'task_id' => 't1', 'task_title' => 'Refacto'], TaskCreatedEvent::class, Family::Team];
        yield 'TaskCompleted' => ['TaskCompleted', $base + ['hook_event_name' => 'TaskCompleted', 'task_id' => 't1', 'task_title' => 'Refacto'], TaskCompletedEvent::class, Family::Team];
        yield 'TeammateIdle' => ['TeammateIdle', $base + ['hook_event_name' => 'TeammateIdle', 'teammate_name' => 'impl', 'reason' => 'wait'], TeammateIdleEvent::class, Family::Team];
    }

    /**
     * @param array<string, mixed> $payload
     * @param class-string $expectedClass
     */
    #[DataProvider('payloadProvider')]
    public function testEachDocumentedEventNameDispatchesToTypedDto(string $name, array $payload, string $expectedClass, Family $expectedFamily): void
    {
        $event = $this->factory->fromPayload($payload);
        self::assertInstanceOf($expectedClass, $event);
        self::assertSame($expectedFamily, $event->family());
        self::assertSame($name, $event->hookEventName);
    }

    public function testItRoundTripsDocumentedPreToolUsePayloadFromFixture(): void
    {
        $raw = (string) file_get_contents(__DIR__ . '/../../Fixtures/payloads/PreToolUse.json');
        /** @var array<string, mixed> $payload */
        $payload = (array) json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
        $event = $this->factory->fromPayload($payload);
        self::assertInstanceOf(PreToolUseEvent::class, $event);
        self::assertSame('Bash', $event->toolName);
        self::assertSame('toolu_REDACTED', $event->toolUseId);
    }
}
