<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Linter;

enum Severity: string
{
    case Error = 'error';
    case Warning = 'warning';
    case Info = 'info';
}
