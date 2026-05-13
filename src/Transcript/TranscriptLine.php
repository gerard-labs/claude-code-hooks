<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Transcript;

final readonly class TranscriptLine
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public string $type,
        public ?string $uuid,
        public ?string $parentUuid,
        public bool $isSidechain,
        public array $payload,
        public int $offset,
        public int $byteLength,
        public bool $truncated = false,
    ) {}
}
