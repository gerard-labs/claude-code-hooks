<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Resolver;

use Override;

final readonly class InMemorySettingsLoader implements SettingsLoader
{
    /**
     * @param array<value-of<HookSource>, array<string, mixed>|null> $settings
     */
    public function __construct(private array $settings) {}

    #[Override]
    public function load(HookSource $source): ?array
    {
        return $this->settings[$source->value] ?? null;
    }
}
