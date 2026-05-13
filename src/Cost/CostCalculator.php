<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Cost;

use Gerard\ClaudeCodeHooks\Transcript\Usage;

/**
 * Computes the cost of a single Usage block, in micro-dollars (Money).
 *
 * Formula (cf. plan §B.9):
 *
 *   net_input = max(0, input_tokens - cache_read_input_tokens - cache_creation_input_tokens)
 *   cost = net_input          * P_in
 *        + cache_read_tokens  * P_cr
 *        + cache_create_tokens* P_cc
 *        + output_tokens      * P_out
 *
 * Per-million-token rates are looked up via the supplied `PriceTable` and
 * multiplied by tokens then divided by 1_000_000 with integer arithmetic.
 */
final readonly class CostCalculator
{
    public function compute(Usage $usage, PriceTable $table): Money
    {
        $rates = $table->ratesPerMillionTokens($usage->serviceTier);

        $netInput = max(
            0,
            $usage->inputTokens - $usage->cacheReadInputTokens - $usage->cacheCreationInputTokens,
        );

        $micro = (int) ($netInput * $rates['input'] / 1_000_000)
            + (int) ($usage->cacheReadInputTokens * $rates['cache_read'] / 1_000_000)
            + (int) ($usage->cacheCreationInputTokens * $rates['cache_creation'] / 1_000_000)
            + (int) ($usage->outputTokens * $rates['output'] / 1_000_000);

        return new Money(max(0, $micro));
    }
}
