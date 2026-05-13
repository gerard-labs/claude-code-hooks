<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Resolver;

enum HookSource: string
{
    case Policy = 'policySettings';
    case User = 'userSettings';
    case Project = 'projectSettings';
    case Local = 'localSettings';
    case Plugin = 'plugin';
    case Runtime = 'runtime';

    public function precedence(): int
    {
        return match ($this) {
            self::Policy => 0,
            self::User => 1,
            self::Project => 2,
            self::Local => 3,
            self::Plugin => 4,
            self::Runtime => 5,
        };
    }
}
