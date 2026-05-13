<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Exception;

use DomainException;

use function sprintf;

final class UnknownEventException extends DomainException implements HookException
{
    public static function forName(string $eventName): self
    {
        return new self(sprintf('Unknown hook event name: %s', $eventName));
    }
}
