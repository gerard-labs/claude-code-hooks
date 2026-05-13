<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Transcript;

use Generator;

/**
 * Lazily groups `TranscriptLine`s by `parentUuid` so callers can rebuild the
 * sidechain (sub-agent) tree without holding the entire transcript in memory.
 *
 * The output map is keyed by the parent uuid and yields the children in the
 * order they appeared in the source.
 */
final class SidechainGrouper
{
    /**
     * @param iterable<TranscriptLine> $lines
     *
     * @return Generator<string, list<TranscriptLine>>
     */
    public function group(iterable $lines): Generator
    {
        /** @var array<string, list<TranscriptLine>> $buckets */
        $buckets = [];

        foreach ($lines as $line) {
            if ($line->parentUuid === null) {
                continue;
            }
            $buckets[$line->parentUuid] ??= [];
            $buckets[$line->parentUuid][] = $line;
        }

        foreach ($buckets as $parent => $children) {
            yield $parent => $children;
        }
    }
}
