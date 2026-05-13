<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Cost;

use Gerard\ClaudeCodeHooks\Exception\InvalidPayloadException;
use Override;

/**
 * Sonnet 4.5 published rates (per million tokens, micro-dollars).
 *
 * Source: <https://www.anthropic.com/pricing> — captured 2026-05-01.
 *  - input        $3.00  per Mt
 *  - output       $15.00 per Mt
 *  - cache_read   $0.30  per Mt
 *  - cache_creation $3.75 per Mt (5m) — using 5m as the default tier
 */
final readonly class Sonnet45PriceTable implements PriceTable
{
    #[Override]
    public function ratesPerMillionTokens(string $serviceTier): array
    {
        return match ($serviceTier) {
            'standard' => [
                'input' => 3_000_000,
                'output' => 15_000_000,
                'cache_read' => 300_000,
                'cache_creation' => 3_750_000,
            ],
            default => throw InvalidPayloadException::unsupportedValue(
                'Sonnet45PriceTable',
                '$.service_tier',
                'standard',
            ),
        };
    }
}
