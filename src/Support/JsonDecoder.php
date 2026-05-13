<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Support;

use Gerard\ClaudeCodeHooks\Exception\InvalidPayloadException;

use function is_array;

use const JSON_THROW_ON_ERROR;

use JsonException;

use function sprintf;

/**
 * SEC-04: every JSON payload entering the package is decoded through this
 * single chokepoint with `JSON_THROW_ON_ERROR` and `depth: 64`.
 *
 * Exceptions raised here NEVER contain the raw JSON content — only the source
 * path / context label. Callers map the failure to the appropriate domain
 * exception (e.g. an `InvalidTranscriptLineException` from `TranscriptReader`).
 */
final class JsonDecoder
{
    private const int MAX_DEPTH = 64;

    /**
     * @return array<array-key, mixed>
     *
     * @throws InvalidPayloadException
     */
    public static function decode(string $raw, string $sourceLabel): array
    {
        try {
            /** @var mixed $decoded */
            $decoded = json_decode($raw, associative: true, depth: self::MAX_DEPTH, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new InvalidPayloadException(sprintf('Invalid JSON in %s', $sourceLabel));
        }

        if (!is_array($decoded)) {
            throw new InvalidPayloadException(sprintf('Expected JSON object or array in %s', $sourceLabel));
        }

        return $decoded;
    }
}
