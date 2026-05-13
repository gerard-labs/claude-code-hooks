<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Resolver;

final readonly class HookRule
{
    /**
     * @param array<string, mixed> $handler
     */
    public function __construct(
        public string $event,
        public string $matcher,
        public array $handler,
        public HookSource $source,
        public bool $managed = false,
    ) {}
}
