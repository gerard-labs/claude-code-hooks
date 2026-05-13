<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Event;

use Gerard\ClaudeCodeHooks\Exception\InvalidPayloadException;

enum PermissionMode: string
{
    case Default = 'default';
    case Plan = 'plan';
    case AcceptEdits = 'acceptEdits';
    case Auto = 'auto';
    case DontAsk = 'dontAsk';
    case BypassPermissions = 'bypassPermissions';

    public static function fromString(string $value): self
    {
        return self::tryFrom($value)
            ?? throw InvalidPayloadException::unsupportedValue(
                'permission_mode',
                '$.permission_mode',
                'default|plan|acceptEdits|auto|dontAsk|bypassPermissions',
            );
    }
}
