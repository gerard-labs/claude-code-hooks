<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Scanner\Skill;

final readonly class Skill
{
    /**
     * @param list<string> $triggerHooks
     */
    public function __construct(
        public string $name,
        public string $path,
        public string $description,
        public array $triggerHooks = [],
    ) {}
}
