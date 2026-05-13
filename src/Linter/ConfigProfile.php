<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Linter;

enum ConfigProfile: string
{
    case Observability = 'observability';
    case Policy = 'policy';
    case Default = 'default';
}
