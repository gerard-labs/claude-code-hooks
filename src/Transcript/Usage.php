<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Transcript;

use function array_key_exists;

use Gerard\ClaudeCodeHooks\Exception\InvalidPayloadException;

use function is_int;
use function is_string;

/**
 * Snapshot of a `message.usage` block from a `type:"assistant"` transcript
 * line (cf. OBSERVABILITY_SPEC §3.2). All token counters default to 0 when
 * absent — this matches the documented Anthropic behaviour for short turns.
 */
final readonly class Usage
{
    public function __construct(
        public int $inputTokens,
        public int $outputTokens,
        public int $cacheReadInputTokens = 0,
        public int $cacheCreationInputTokens = 0,
        public string $serviceTier = 'standard',
    ) {
        if ($inputTokens < 0 || $outputTokens < 0 || $cacheReadInputTokens < 0 || $cacheCreationInputTokens < 0) {
            throw InvalidPayloadException::unsupportedValue('Usage', '$', 'non-negative integer counters');
        }
    }

    /**
     * @param array<array-key, mixed> $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self(
            inputTokens: self::int($payload, 'input_tokens'),
            outputTokens: self::int($payload, 'output_tokens'),
            cacheReadInputTokens: self::int($payload, 'cache_read_input_tokens'),
            cacheCreationInputTokens: self::int($payload, 'cache_creation_input_tokens'),
            serviceTier: is_string($payload['service_tier'] ?? null) ? $payload['service_tier'] : 'standard',
        );
    }

    /**
     * @param array<array-key, mixed> $payload
     */
    private static function int(array $payload, string $key): int
    {
        if (!array_key_exists($key, $payload)) {
            return 0;
        }
        $value = $payload[$key];

        return is_int($value) ? $value : 0;
    }
}
