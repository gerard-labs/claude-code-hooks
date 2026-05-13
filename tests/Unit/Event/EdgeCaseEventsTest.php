<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Tests\Unit\Event;

use Gerard\ClaudeCodeHooks\Event\Ctx\WorktreeCreateEvent;
use Gerard\ClaudeCodeHooks\Event\Perm\ElicitationEvent;
use Gerard\ClaudeCodeHooks\Event\Perm\ElicitationResultEvent;
use Gerard\ClaudeCodeHooks\Event\Perm\PermissionDeniedEvent;
use Gerard\ClaudeCodeHooks\Event\Perm\PermissionRequestEvent;
use Gerard\ClaudeCodeHooks\Event\Tool\PostToolBatchEvent;
use Gerard\ClaudeCodeHooks\Event\Tool\PostToolUseEvent;
use Gerard\ClaudeCodeHooks\Event\Tool\PostToolUseFailureEvent;
use Gerard\ClaudeCodeHooks\Event\Tool\PreToolUseEvent;
use Gerard\ClaudeCodeHooks\Event\Turn\StopEvent;
use Gerard\ClaudeCodeHooks\Event\Turn\UserPromptSubmitEvent;
use Gerard\ClaudeCodeHooks\Exception\InvalidPayloadException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PreToolUseEvent::class)]
#[CoversClass(PostToolUseEvent::class)]
#[CoversClass(PostToolUseFailureEvent::class)]
#[CoversClass(PostToolBatchEvent::class)]
#[CoversClass(PermissionRequestEvent::class)]
#[CoversClass(PermissionDeniedEvent::class)]
#[CoversClass(ElicitationEvent::class)]
#[CoversClass(ElicitationResultEvent::class)]
#[CoversClass(WorktreeCreateEvent::class)]
#[CoversClass(StopEvent::class)]
#[CoversClass(UserPromptSubmitEvent::class)]
final class EdgeCaseEventsTest extends TestCase
{
    private const array BASE = [
        'session_id' => 's',
        'transcript_path' => '/p',
        'cwd' => '/c',
        'permission_mode' => 'default',
    ];

    public function testPreToolUseRejectsMissingToolInput(): void
    {
        self::expectException(InvalidPayloadException::class);
        PreToolUseEvent::fromPayload(self::BASE + ['hook_event_name' => 'PreToolUse', 'tool_name' => 'Bash']);
    }

    public function testPostToolUseRejectsMissingToolResponse(): void
    {
        self::expectException(InvalidPayloadException::class);
        PostToolUseEvent::fromPayload(self::BASE + [
            'hook_event_name' => 'PostToolUse', 'tool_name' => 'Bash', 'tool_input' => ['command' => 'ls'],
        ]);
    }

    public function testPostToolUseRejectsToolResponseArrayWithNonStringKeys(): void
    {
        // The wire shape is a JSON object (string keys). A list (numeric keys)
        // must be rejected — pins the foreach key validation against a mutant
        // that drops the `is_string($key)` check.
        self::expectException(InvalidPayloadException::class);
        PostToolUseEvent::fromPayload(self::BASE + [
            'hook_event_name' => 'PostToolUse', 'tool_name' => 'Bash', 'tool_input' => ['command' => 'ls'],
            'tool_response' => ['a', 'b', 'c'],
        ]);
    }

    public function testPostToolUseAcceptsToolResponseArrayWithStringKeys(): void
    {
        $event = PostToolUseEvent::fromPayload(self::BASE + [
            'hook_event_name' => 'PostToolUse', 'tool_name' => 'Bash', 'tool_input' => ['command' => 'ls'],
            'tool_response' => ['stdout' => 'ok', 'exit_code' => 0],
        ]);
        self::assertSame(['stdout' => 'ok', 'exit_code' => 0], $event->toolResponse);
    }

    public function testPostToolUseRejectsNonStringNonArrayResponse(): void
    {
        self::expectException(InvalidPayloadException::class);
        PostToolUseEvent::fromPayload(self::BASE + [
            'hook_event_name' => 'PostToolUse', 'tool_name' => 'Bash', 'tool_input' => ['command' => 'ls'],
            'tool_response' => 42,
        ]);
    }

    public function testPostToolUseAcceptsArrayResponse(): void
    {
        $event = PostToolUseEvent::fromPayload(self::BASE + [
            'hook_event_name' => 'PostToolUse', 'tool_name' => 'Bash', 'tool_input' => ['command' => 'ls'],
            'tool_response' => ['stdout' => 'ok', 'stderr' => '', 'interrupted' => false],
            'duration_ms' => 181,
        ]);
        self::assertSame(['stdout' => 'ok', 'stderr' => '', 'interrupted' => false], $event->toolResponse);
        self::assertSame(181, $event->durationMs);
    }

    public function testPostToolUseCoercesNumericStringDurationMsToInt(): void
    {
        $event = PostToolUseEvent::fromPayload(self::BASE + [
            'hook_event_name' => 'PostToolUse', 'tool_name' => 'Bash', 'tool_input' => ['command' => 'ls'],
            'tool_response' => 'ok',
            'duration_ms' => '42',
        ]);
        self::assertSame(42, $event->durationMs);
    }

    public function testPostToolUseLeavesDurationMsNullWhenNonNumeric(): void
    {
        $event = PostToolUseEvent::fromPayload(self::BASE + [
            'hook_event_name' => 'PostToolUse', 'tool_name' => 'Bash', 'tool_input' => ['command' => 'ls'],
            'tool_response' => 'ok',
            'duration_ms' => ['not', 'numeric'],
        ]);
        self::assertNull($event->durationMs);
    }

    public function testStopRejectsPayloadWithoutStopHookActive(): void
    {
        self::expectException(InvalidPayloadException::class);
        StopEvent::fromPayload(self::BASE + ['hook_event_name' => 'Stop']);
    }

    public function testStopRejectsNonBoolStopHookActive(): void
    {
        self::expectException(InvalidPayloadException::class);
        StopEvent::fromPayload(self::BASE + ['hook_event_name' => 'Stop', 'stop_hook_active' => 1]);
    }

    public function testStopAcceptsOptionalLastAssistantMessage(): void
    {
        $event = StopEvent::fromPayload(self::BASE + [
            'hook_event_name' => 'Stop',
            'stop_hook_active' => true,
            'last_assistant_message' => 'goodbye',
        ]);
        self::assertSame('goodbye', $event->lastAssistantMessage);
        self::assertTrue($event->stopHookActive);
    }

    public function testPostToolUseFailureRejectsMalformed(): void
    {
        self::expectException(InvalidPayloadException::class);
        PostToolUseFailureEvent::fromPayload(self::BASE + [
            'hook_event_name' => 'PostToolUseFailure', 'tool_name' => 'Bash',
        ]);
    }

    public function testPostToolBatchRejectsMissingToolCalls(): void
    {
        self::expectException(InvalidPayloadException::class);
        PostToolBatchEvent::fromPayload(self::BASE + ['hook_event_name' => 'PostToolBatch']);
    }

    public function testPermissionRequestRejectsMissingToolInput(): void
    {
        self::expectException(InvalidPayloadException::class);
        PermissionRequestEvent::fromPayload(self::BASE + [
            'hook_event_name' => 'PermissionRequest', 'tool_name' => 'Bash',
        ]);
    }

    public function testPermissionRequestParsesSuggestions(): void
    {
        $event = PermissionRequestEvent::fromPayload(self::BASE + [
            'hook_event_name' => 'PermissionRequest', 'tool_name' => 'Bash',
            'tool_input' => ['command' => 'ls'],
            'permission_suggestions' => [
                ['type' => 'addRules', 'rules' => []],
                'not-an-array',
            ],
        ]);
        self::assertCount(1, $event->permissionSuggestions);
    }

    public function testPermissionDeniedRejectsMissingToolInput(): void
    {
        self::expectException(InvalidPayloadException::class);
        PermissionDeniedEvent::fromPayload(self::BASE + [
            'hook_event_name' => 'PermissionDenied', 'tool_name' => 'Bash',
        ]);
    }

    public function testElicitationParsesFields(): void
    {
        $event = ElicitationEvent::fromPayload(self::BASE + [
            'hook_event_name' => 'Elicitation', 'mcp_server' => 's', 'tool_name' => 't', 'elicitation_type' => 'select',
            'fields' => [['name' => 'x'], 'invalid'],
        ]);
        self::assertCount(1, $event->fields);
    }

    public function testElicitationResultRejectsMissingUserResponse(): void
    {
        self::expectException(InvalidPayloadException::class);
        ElicitationResultEvent::fromPayload(self::BASE + [
            'hook_event_name' => 'ElicitationResult', 'mcp_server' => 's', 'tool_name' => 't',
        ]);
    }

    public function testWorktreeCreateRejectsMissingIsolation(): void
    {
        self::expectException(InvalidPayloadException::class);
        WorktreeCreateEvent::fromPayload(self::BASE + [
            'hook_event_name' => 'WorktreeCreate', 'base_path' => '/x',
        ]);
    }

    public function testWorktreeCreateRejectsNonBoolIsolation(): void
    {
        self::expectException(InvalidPayloadException::class);
        WorktreeCreateEvent::fromPayload(self::BASE + [
            'hook_event_name' => 'WorktreeCreate', 'worktree_isolation' => 'yes', 'base_path' => '/x',
        ]);
    }

    public function testUserPromptSubmitDefaultsSessionTitleToNullWhenAbsent(): void
    {
        $event = UserPromptSubmitEvent::fromPayload(self::BASE + [
            'hook_event_name' => 'UserPromptSubmit',
            'prompt' => 'hello',
        ]);
        self::assertNull($event->sessionTitle);
        self::assertSame('hello', $event->prompt);
    }

    public function testUserPromptSubmitParsesSessionTitleWhenPresent(): void
    {
        // Real corpus: 48/48 UserPromptSubmit events carry `session_title`
        // populated by the orchestrator (e.g. "01-01 · 1 · architecture").
        $event = UserPromptSubmitEvent::fromPayload(self::BASE + [
            'hook_event_name' => 'UserPromptSubmit',
            'prompt' => 'hello',
            'session_title' => 'feat-7 · review',
        ]);
        self::assertSame('feat-7 · review', $event->sessionTitle);
    }

    public function testUserPromptSubmitIgnoresNonStringSessionTitle(): void
    {
        $event = UserPromptSubmitEvent::fromPayload(self::BASE + [
            'hook_event_name' => 'UserPromptSubmit',
            'prompt' => 'hello',
            'session_title' => 42,
        ]);
        self::assertNull($event->sessionTitle);
    }
}
