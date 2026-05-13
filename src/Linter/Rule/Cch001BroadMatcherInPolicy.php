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
 * CCH001 — broad matcher (`.*`, `*`, empty) is an Error in the `Policy` profile.
 *
 * The `Observability` profile inverts this rule on purpose (matching everything
 * is the documented "full data" pattern, cf. plan §0).
 */
final class Cch001BroadMatcherInPolicy implements LinterRule
{
    private const array BROAD_MATCHERS = ['.*', '*', ''];

    /**
     * @return iterable<Finding>
     */
    #[Override]
    public function inspect(HookConfig $config, ConfigProfile $profile): iterable
    {
        if ($profile !== ConfigProfile::Policy) {
            return;
        }
        if (!in_array($config->matcher, self::BROAD_MATCHERS, true)) {
            return;
        }

        yield new Finding(
            severity: Severity::Error,
            code: 'CCH001',
            message: sprintf('Broad matcher "%s" forbidden in Policy profile', $config->matcher),
            jsonPointer: sprintf('$.hooks.%s.matcher', $config->event),
        );
    }
}
