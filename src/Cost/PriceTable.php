<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Cost;

interface PriceTable
{
    /**
     * Returns the per-million-token rates as integer micro-dollars for a given
     * service tier. Implementations MUST throw `InvalidPayloadException` for
     * unsupported tiers (AC-COST-02).
     *
     * @return array{
     *     input: int,
     *     output: int,
     *     cache_read: int,
     *     cache_creation: int,
     * }
     */
    public function ratesPerMillionTokens(string $serviceTier): array;
}
