<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Linter\Rule;

use function array_key_exists;

use Gerard\ClaudeCodeHooks\Linter\ConfigProfile;
use Gerard\ClaudeCodeHooks\Linter\Finding;
use Gerard\ClaudeCodeHooks\Linter\HookConfig;
use Gerard\ClaudeCodeHooks\Linter\LinterRule;
use Gerard\ClaudeCodeHooks\Linter\Severity;

use function is_string;

use Override;

use function sprintf;

/**
 * CCH004 — `http` handler without an explicit `timeout` blocks Claude until
 * Claude Code's default 60s timeout fires. Always set one.
 */
final class Cch004HttpHandlerWithoutTimeout implements LinterRule
{
    /**
     * @return iterable<Finding>
     */
    #[Override]
    public function inspect(HookConfig $config, ConfigProfile $profile): iterable
    {
        $type = is_string($config->handler['type'] ?? null) ? $config->handler['type'] : null;

        if ($type !== 'http') {
            return;
        }
        if (array_key_exists('timeout', $config->handler)) {
            return;
        }

        yield new Finding(
            severity: Severity::Warning,
            code: 'CCH004',
            message: sprintf('HTTP handler for %s missing timeout', $config->event),
            jsonPointer: sprintf('$.hooks.%s.handler.timeout', $config->event),
        );
    }
}
