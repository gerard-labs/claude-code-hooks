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
 * CCH003 — tool events (`PreToolUse`, `PostToolUse`, `PostToolUseFailure`,
 * `PermissionRequest`, `PermissionDenied`) require an explicit matcher,
 * because Claude Code uses the matcher to dispatch on the tool name.
 */
final class Cch003MissingMatcherForToolEvent implements LinterRule
{
    private const array TOOL_EVENTS = [
        'PreToolUse', 'PostToolUse', 'PostToolUseFailure',
        'PermissionRequest', 'PermissionDenied',
    ];

    /**
     * @return iterable<Finding>
     */
    #[Override]
    public function inspect(HookConfig $config, ConfigProfile $profile): iterable
    {
        if (!in_array($config->event, self::TOOL_EVENTS, true)) {
            return;
        }
        if ($config->matcher !== '') {
            return;
        }

        yield new Finding(
            severity: Severity::Warning,
            code: 'CCH003',
            message: sprintf('Tool event %s missing matcher', $config->event),
            jsonPointer: sprintf('$.hooks.%s.matcher', $config->event),
        );
    }
}
