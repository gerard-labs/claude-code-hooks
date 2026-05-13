<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Response\Turn;

use Gerard\ClaudeCodeHooks\Response\AbstractHookResponse;

/**
 * SubagentStart can only inject `additionalContext` per OBSERVABILITY_SPEC §3.1.
 */
final class SubagentStartResponse extends AbstractHookResponse
{
    public static function noop(): self
    {
        return new self();
    }
}
