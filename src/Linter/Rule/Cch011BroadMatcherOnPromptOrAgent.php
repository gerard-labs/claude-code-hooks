<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Linter\Rule;

use Gerard\ClaudeCodeHooks\Linter\ConfigProfile;
use Gerard\ClaudeCodeHooks\Linter\Finding;
use Gerard\ClaudeCodeHooks\Linter\HookConfig;
use Gerard\ClaudeCodeHooks\Linter\LinterRule;
use Gerard\ClaudeCodeHooks\Linter\Severity;

use function in_array;
use function is_string;

use Override;

use function sprintf;

/**
 * CCH011 — `prompt` and `agent` handlers are billed against the user's quota.
 * A broad matcher (`.*` / `*` / empty) on these handlers can rapidly burn
 * tokens, so we warn — even in `Observability` profile.
 */
final class Cch011BroadMatcherOnPromptOrAgent implements LinterRule
{
    private const array BROAD_MATCHERS = ['.*', '*', ''];
    private const array BILLED_HANDLER_TYPES = ['prompt', 'agent'];

    /**
     * @return iterable<Finding>
     */
    #[Override]
    public function inspect(HookConfig $config, ConfigProfile $profile): iterable
    {
        $type = is_string($config->handler['type'] ?? null) ? $config->handler['type'] : null;

        if (!in_array($type, self::BILLED_HANDLER_TYPES, true)) {
            return;
        }
        if (!in_array($config->matcher, self::BROAD_MATCHERS, true)) {
            return;
        }

        yield new Finding(
            severity: Severity::Warning,
            code: 'CCH011',
            message: sprintf(
                'Broad matcher on billed %s handler — restrict scope to avoid quota burn',
                $type,
            ),
            jsonPointer: sprintf('$.hooks.%s.matcher', $config->event),
        );
    }
}
