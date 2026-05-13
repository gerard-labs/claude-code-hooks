<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Linter;

interface LinterRule
{
    /**
     * @return iterable<Finding>
     */
    public function inspect(HookConfig $config, ConfigProfile $profile): iterable;
}
