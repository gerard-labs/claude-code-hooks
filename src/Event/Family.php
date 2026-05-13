<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Event;

enum Family: string
{
    case Session = 'session';
    case Turn = 'turn';
    case Tool = 'tool';
    case Perm = 'perm';
    case Compact = 'compact';
    case Ctx = 'ctx';
    case Team = 'team';
    case Unknown = 'unknown';
}
