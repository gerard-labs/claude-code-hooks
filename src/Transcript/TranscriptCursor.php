<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Transcript;

final readonly class TranscriptCursor
{
    public function __construct(
        public string $filePath,
        public int $offset = 0,
    ) {}

    public function withOffset(int $offset): self
    {
        return new self($this->filePath, $offset);
    }

    public function withFilePath(string $filePath): self
    {
        return new self($filePath, 0);
    }
}
