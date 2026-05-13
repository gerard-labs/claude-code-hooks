<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Response\Session;

use Gerard\ClaudeCodeHooks\Response\AbstractHookResponse;

final class SessionStartResponse extends AbstractHookResponse
{
    public static function noop(): self
    {
        return new self();
    }
}
