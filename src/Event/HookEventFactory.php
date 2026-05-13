<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Event;

use function array_key_exists;

use Gerard\ClaudeCodeHooks\Event\Compact\PostCompactEvent;
use Gerard\ClaudeCodeHooks\Event\Compact\PreCompactEvent;
use Gerard\ClaudeCodeHooks\Event\Ctx\ConfigChangeEvent;
use Gerard\ClaudeCodeHooks\Event\Ctx\CwdChangedEvent;
use Gerard\ClaudeCodeHooks\Event\Ctx\FileChangedEvent;
use Gerard\ClaudeCodeHooks\Event\Ctx\InstructionsLoadedEvent;
use Gerard\ClaudeCodeHooks\Event\Ctx\WorktreeCreateEvent;
use Gerard\ClaudeCodeHooks\Event\Ctx\WorktreeRemoveEvent;
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
use Gerard\ClaudeCodeHooks\Exception\InvalidPayloadException;

use function is_string;

/**
 * Hash-table dispatcher (O(1) per call) from `hook_event_name` payloads to
 * concrete event DTOs. Unknown event names degrade to `UnknownHookEvent`
 * rather than throwing — forward-compat as ADR-0007 requires.
 */
final class HookEventFactory
{
    /**
     * @param array<array-key, mixed> $payload
     */
    public function fromPayload(array $payload): AbstractHookEvent
    {
        if (!array_key_exists('hook_event_name', $payload)) {
            throw InvalidPayloadException::missingField('hook payload', '$.hook_event_name');
        }
        if (!is_string($payload['hook_event_name'])) {
            throw InvalidPayloadException::wrongType('hook payload', '$.hook_event_name', 'string');
        }

        return match ($payload['hook_event_name']) {
            'Setup' => SetupEvent::fromPayload($payload),
            'SessionStart' => SessionStartEvent::fromPayload($payload),
            'SessionEnd' => SessionEndEvent::fromPayload($payload),
            'UserPromptSubmit' => UserPromptSubmitEvent::fromPayload($payload),
            'UserPromptExpansion' => UserPromptExpansionEvent::fromPayload($payload),
            'Stop' => StopEvent::fromPayload($payload),
            'StopFailure' => StopFailureEvent::fromPayload($payload),
            'SubagentStart' => SubagentStartEvent::fromPayload($payload),
            'SubagentStop' => SubagentStopEvent::fromPayload($payload),
            'PreToolUse' => PreToolUseEvent::fromPayload($payload),
            'PostToolUse' => PostToolUseEvent::fromPayload($payload),
            'PostToolUseFailure' => PostToolUseFailureEvent::fromPayload($payload),
            'PostToolBatch' => PostToolBatchEvent::fromPayload($payload),
            'PermissionRequest' => PermissionRequestEvent::fromPayload($payload),
            'PermissionDenied' => PermissionDeniedEvent::fromPayload($payload),
            'Notification' => NotificationEvent::fromPayload($payload),
            'Elicitation' => ElicitationEvent::fromPayload($payload),
            'ElicitationResult' => ElicitationResultEvent::fromPayload($payload),
            'PreCompact' => PreCompactEvent::fromPayload($payload),
            'PostCompact' => PostCompactEvent::fromPayload($payload),
            'InstructionsLoaded' => InstructionsLoadedEvent::fromPayload($payload),
            'ConfigChange' => ConfigChangeEvent::fromPayload($payload),
            'CwdChanged' => CwdChangedEvent::fromPayload($payload),
            'FileChanged' => FileChangedEvent::fromPayload($payload),
            'WorktreeCreate' => WorktreeCreateEvent::fromPayload($payload),
            'WorktreeRemove' => WorktreeRemoveEvent::fromPayload($payload),
            'TaskCreated' => TaskCreatedEvent::fromPayload($payload),
            'TaskCompleted' => TaskCompletedEvent::fromPayload($payload),
            'TeammateIdle' => TeammateIdleEvent::fromPayload($payload),
            default => $this->unknown($payload),
        };
    }

    /**
     * @param array<array-key, mixed> $payload
     */
    private function unknown(array $payload): UnknownHookEvent
    {
        $modeRaw = $this->optionalString($payload, 'permission_mode') ?? 'default';
        $mode = PermissionMode::tryFrom($modeRaw) ?? PermissionMode::Default;

        /** @var array<string, mixed> $rawPayload */
        $rawPayload = [];
        foreach ($payload as $key => $value) {
            if (is_string($key)) {
                $rawPayload[$key] = $value;
            }
        }

        return new UnknownHookEvent(
            sessionId: $this->optionalString($payload, 'session_id') ?? '',
            transcriptPath: $this->optionalString($payload, 'transcript_path') ?? '',
            cwd: $this->optionalString($payload, 'cwd') ?? '',
            permissionMode: $mode,
            hookEventName: $this->optionalString($payload, 'hook_event_name') ?? '',
            rawPayload: $rawPayload,
            agentId: $this->optionalString($payload, 'agent_id'),
            agentType: $this->optionalString($payload, 'agent_type'),
        );
    }

    /**
     * @param array<array-key, mixed> $payload
     */
    private function optionalString(array $payload, string $key): ?string
    {
        if (!array_key_exists($key, $payload)) {
            return null;
        }
        $value = $payload[$key];

        return is_string($value) ? $value : null;
    }
}
