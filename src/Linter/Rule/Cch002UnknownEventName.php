<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Linter\Rule;

use Gerard\ClaudeCodeHooks\Linter\ConfigProfile;
use Gerard\ClaudeCodeHooks\Linter\Finding;
use Gerard\ClaudeCodeHooks\Linter\HookConfig;
use Gerard\ClaudeCodeHooks\Linter\LinterRule;
use Gerard\ClaudeCodeHooks\Linter\Severity;

use function in_array;

use Override;

use function sprintf;

/**
 * CCH002 — `hook_event_name` not in the documented set of 29 events.
 */
final class Cch002UnknownEventName implements LinterRule
{
    private const array KNOWN_EVENTS = [
        'Setup', 'SessionStart', 'SessionEnd',
        'UserPromptSubmit', 'UserPromptExpansion', 'Stop', 'StopFailure', 'SubagentStart', 'SubagentStop',
        'PreToolUse', 'PostToolUse', 'PostToolUseFailure', 'PostToolBatch',
        'PermissionRequest', 'PermissionDenied', 'Notification', 'Elicitation', 'ElicitationResult',
        'PreCompact', 'PostCompact',
        'InstructionsLoaded', 'ConfigChange', 'CwdChanged', 'FileChanged', 'WorktreeCreate', 'WorktreeRemove',
        'TaskCreated', 'TaskCompleted', 'TeammateIdle',
    ];

    /**
     * @return iterable<Finding>
     */
    #[Override]
    public function inspect(HookConfig $config, ConfigProfile $profile): iterable
    {
        if (in_array($config->event, self::KNOWN_EVENTS, true)) {
            return;
        }

        yield new Finding(
            severity: Severity::Error,
            code: 'CCH002',
            message: sprintf('Unknown hook event "%s"', $config->event),
            jsonPointer: sprintf('$.hooks.%s', $config->event),
        );
    }
}
