<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Transcript;

use function assert;

use Generator;
use Gerard\ClaudeCodeHooks\Exception\InvalidPayloadException;
use Gerard\ClaudeCodeHooks\Exception\InvalidTranscriptLineException;
use Gerard\ClaudeCodeHooks\Support\JsonDecoder;

use function is_string;
use function strlen;

/**
 * Generator-based JSONL reader (OBSERVABILITY_SPEC §3.2).
 *
 * SEC-02:
 *   - `fgets($fp, $maxLineBytes)` caps every line at 8 MB by default.
 *   - Lines exceeding the cap surface as a `TranscriptLine` with
 *     `truncated: true`; the cursor is advanced to the next `\n`.
 *   - Incomplete tails (no trailing `\n`) are not yielded; the caller can
 *     safely resume from the last advanced offset.
 *
 * The reader itself never mutates the filesystem — `BoundaryEvent` signals
 * compactions to the caller, who is responsible for relocating the cursor.
 */
final readonly class TranscriptReader
{
    public const int DEFAULT_MAX_LINE_BYTES = 8 * 1024 * 1024;

    public function __construct(
        private int $maxLineBytes = self::DEFAULT_MAX_LINE_BYTES,
    ) {
        if ($maxLineBytes < 1024) {
            throw InvalidPayloadException::unsupportedValue('TranscriptReader', '$.maxLineBytes', '>= 1024');
        }
    }

    /**
     * @return Generator<int, TranscriptLine|BoundaryEvent, mixed, TranscriptCursor>
     */
    public function tail(TranscriptCursor $cursor): Generator
    {
        if (!is_file($cursor->filePath)) {
            return $cursor;
        }

        $fp = fopen($cursor->filePath, 'rb');
        assert($fp !== false, 'is_file() guaranteed the file exists');

        try {
            fseek($fp, $cursor->offset);
            $offset = $cursor->offset;
            $lineNumber = 0;

            while (!feof($fp)) {
                $start = $offset;
                /** @var int<0, max> $cap */
                $cap = $this->maxLineBytes;
                $raw = fgets($fp, $cap);

                if ($raw === false) {
                    break;
                }

                $byteLen = strlen($raw);
                $offset += $byteLen;
                ++$lineNumber;

                $endsWithNewline = str_ends_with($raw, "\n");

                if (!$endsWithNewline) {
                    if ($byteLen >= $this->maxLineBytes - 1) {
                        $consumed = $this->advanceToNewline($fp);
                        $offset += $consumed;
                        yield new TranscriptLine(
                            type: 'oversize',
                            uuid: null,
                            parentUuid: null,
                            isSidechain: false,
                            payload: [],
                            offset: $start,
                            byteLength: $byteLen + $consumed,
                            truncated: true,
                        );

                        continue;
                    }

                    // Incomplete tail — caller resumes from $start.
                    return $cursor->withOffset($start);
                }

                $trimmed = rtrim($raw, "\n");

                if ($trimmed === '') {
                    continue;
                }

                try {
                    /** @var array<string, mixed> $decoded */
                    $decoded = JsonDecoder::decode($trimmed, $cursor->filePath);
                } catch (InvalidPayloadException) {
                    throw InvalidTranscriptLineException::malformed($cursor->filePath, $lineNumber);
                }

                $type = is_string($decoded['type'] ?? null) ? $decoded['type'] : '';
                $subtype = is_string($decoded['subtype'] ?? null) ? $decoded['subtype'] : '';

                if ($type === 'system' && $subtype === 'compact_boundary') {
                    yield new BoundaryEvent($subtype, $start);

                    continue;
                }

                yield new TranscriptLine(
                    type: $type,
                    uuid: is_string($decoded['uuid'] ?? null) ? $decoded['uuid'] : null,
                    parentUuid: is_string($decoded['parentUuid'] ?? null) ? $decoded['parentUuid'] : null,
                    isSidechain: ($decoded['isSidechain'] ?? false) === true,
                    payload: $decoded,
                    offset: $start,
                    byteLength: $byteLen,
                );
            }

            return $cursor->withOffset($offset);
        } finally {
            fclose($fp);
        }
    }

    /**
     * @param resource $fp
     */
    private function advanceToNewline($fp): int
    {
        $consumed = 0;
        while (!feof($fp)) {
            $chunk = fgets($fp, 65_536);
            assert($chunk !== false, 'feof just returned false');
            $consumed += strlen($chunk);

            if (str_ends_with($chunk, "\n")) {
                break;
            }
        }

        return $consumed;
    }
}
