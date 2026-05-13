<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Scanner\Agent;

final readonly class Agent
{
    public function __construct(
        public string $name,
        public string $path,
        public string $description,
    ) {}
}
