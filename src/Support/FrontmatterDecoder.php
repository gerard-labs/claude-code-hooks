<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Support;

use function assert;

use Gerard\ClaudeCodeHooks\Exception\InvalidPayloadException;

use function is_array;
use function sprintf;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * SEC-03: every YAML frontmatter is parsed through this decoder.
 *
 * The flag `Yaml::PARSE_EXCEPTION_ON_INVALID_TYPE` is the only flag passed —
 * `PARSE_OBJECT*` flags are forbidden (they enable PHP object instantiation
 * via `!php/object` tags, which is a remote-code-execution vector). The CI
 * `Yaml::PARSE_OBJECT` grep guard fails the build if anyone bypasses this
 * decoder.
 */
final class FrontmatterDecoder
{
    /**
     * @return array<string, mixed>
     *
     * @throws InvalidPayloadException
     */
    public static function decode(string $yaml, string $sourceLabel): array
    {
        try {
            $decoded = Yaml::parse($yaml, Yaml::PARSE_EXCEPTION_ON_INVALID_TYPE);
        } catch (ParseException) {
            throw new InvalidPayloadException(sprintf('Invalid YAML frontmatter in %s', $sourceLabel));
        }

        if (!is_array($decoded)) {
            throw new InvalidPayloadException(sprintf('Frontmatter in %s must be a YAML mapping', $sourceLabel));
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }

    /**
     * Parse a `--- yaml --- body` document and return the decoded frontmatter
     * plus the remaining body. The opening fence MUST be on the first line.
     *
     * @return array{frontmatter: array<string, mixed>, body: string}
     *
     * @throws InvalidPayloadException
     */
    public static function split(string $document, string $sourceLabel): array
    {
        if (!str_starts_with($document, "---\n") && !str_starts_with($document, "---\r\n")) {
            throw new InvalidPayloadException(sprintf('Missing opening frontmatter fence in %s', $sourceLabel));
        }

        $newlinePos = strpos($document, "\n");
        assert($newlinePos !== false, 'starts_with already checked the opening fence');
        $afterOpening = substr($document, $newlinePos + 1);
        $closing = strpos($afterOpening, "\n---");

        if ($closing === false) {
            throw new InvalidPayloadException(sprintf('Missing closing frontmatter fence in %s', $sourceLabel));
        }

        $yaml = substr($afterOpening, 0, $closing);
        $body = substr($afterOpening, $closing + 4);
        $body = ltrim($body, "\r\n");

        return [
            'frontmatter' => self::decode($yaml, $sourceLabel),
            'body' => $body,
        ];
    }
}
