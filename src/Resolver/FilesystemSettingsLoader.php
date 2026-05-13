<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Resolver;

use Gerard\ClaudeCodeHooks\Support\JsonDecoder;
use Override;

final readonly class FilesystemSettingsLoader implements SettingsLoader
{
    /**
     * @param array<value-of<HookSource>, string> $sourcePaths
     */
    public function __construct(private array $sourcePaths) {}

    #[Override]
    public function load(HookSource $source): ?array
    {
        $path = $this->sourcePaths[$source->value] ?? null;

        if ($path === null || !is_file($path)) {
            return null;
        }

        $raw = file_get_contents($path);

        if ($raw === false || $raw === '') {
            return null;
        }

        /** @var array<string, mixed> $decoded */
        $decoded = JsonDecoder::decode($raw, $path);

        return $decoded;
    }
}
