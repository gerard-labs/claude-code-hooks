<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Conformance;

use Gerard\ClaudeCodeHooks\Exception\InvalidPayloadException;
use Gerard\ClaudeCodeHooks\Support\JsonDecoder;

use function is_string;
use function sprintf;

/**
 * Loader for the blessed Anthropic spec snapshot used by the conformance
 * tests. Set `CLAUDE_HOOKS_SPEC_FILE` to point at a different file (used by
 * the doc-drift workflow to test against the live spec).
 */
final readonly class SpecSnapshot
{
    /**
     * @return array<string, mixed>
     */
    public static function load(string $defaultPath): array
    {
        $envPath = getenv('CLAUDE_HOOKS_SPEC_FILE');
        $path = (is_string($envPath) && $envPath !== '') ? $envPath : $defaultPath;

        if (!is_file($path)) {
            throw new InvalidPayloadException(sprintf('Spec snapshot not found at %s', $path));
        }

        /** @var array<string, mixed> $decoded */
        $decoded = JsonDecoder::decode((string) file_get_contents($path), $path);

        return $decoded;
    }
}
