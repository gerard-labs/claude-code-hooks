<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Scanner\Plugin;

final readonly class Plugin
{
    public function __construct(
        public string $name,
        public string $installPath,
        public string $version,
        public int $hooksCount,
        public int $skillsCount,
        public int $commandsCount,
        public int $agentsCount,
        public ?string $gitCommitSha = null,
    ) {}
}
