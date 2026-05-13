<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Exception;

use RuntimeException;

use function sprintf;

/**
 * Raised when a transcript line is structurally invalid or exceeds the
 * configured size cap. The exception message NEVER embeds line content —
 * only the path, line number, and byte count (SEC-02).
 */
final class InvalidTranscriptLineException extends RuntimeException implements HookException
{
    public static function tooLarge(string $path, int $lineNumber, int $byteCount, int $maxBytes): self
    {
        return new self(sprintf(
            'Transcript line %d in %s exceeds the %d-byte cap (read %d bytes)',
            $lineNumber,
            $path,
            $maxBytes,
            $byteCount,
        ));
    }

    public static function malformed(string $path, int $lineNumber): self
    {
        return new self(sprintf('Transcript line %d in %s is malformed', $lineNumber, $path));
    }
}
