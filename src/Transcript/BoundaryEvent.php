<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Transcript;

/**
 * Yielded by `TranscriptReader::tail` when the transcript signals
 * `type:"system" + subtype:"compact_boundary"`. The caller resets its
 * cursor (the reader itself is side-effect-free on the FS).
 */
final readonly class BoundaryEvent
{
    public function __construct(
        public string $reason,
        public int $offset,
    ) {}
}
