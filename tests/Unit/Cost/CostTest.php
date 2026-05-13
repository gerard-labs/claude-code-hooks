<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Tests\Unit\Cost;

use Gerard\ClaudeCodeHooks\Cost\CostCalculator;
use Gerard\ClaudeCodeHooks\Cost\Money;
use Gerard\ClaudeCodeHooks\Cost\Sonnet45PriceTable;
use Gerard\ClaudeCodeHooks\Exception\InvalidPayloadException;
use Gerard\ClaudeCodeHooks\Transcript\Usage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CostCalculator::class)]
#[CoversClass(Money::class)]
#[CoversClass(Sonnet45PriceTable::class)]
final class CostTest extends TestCase
{
    public function testItComputesDocumentedWorkedExample(): void
    {
        // Worked example: 10000 input tokens (8000 cache_read + 1000 cache_creation),
        //                 2000 output tokens, standard tier.
        $usage = new Usage(
            inputTokens: 10_000,
            outputTokens: 2_000,
            cacheReadInputTokens: 8_000,
            cacheCreationInputTokens: 1_000,
            serviceTier: 'standard',
        );
        $money = (new CostCalculator())->compute($usage, new Sonnet45PriceTable());
        // net_input = max(0, 10000 - 8000 - 1000) = 1000
        // 1000 input * $3.00/Mt        =     3_000 µ$
        // 8000 cache_read * $0.30/Mt   =     2_400 µ$
        // 1000 cache_create * $3.75/Mt =     3_750 µ$
        // 2000 output * $15.00/Mt      =    30_000 µ$
        // total                          =  39_150 micro-dollars = $0.03915
        self::assertSame(39_150, $money->microDollars);
        self::assertEqualsWithDelta(0.03915, $money->asDollars(), PHP_FLOAT_EPSILON);
    }

    public function testItThrowsInvalidPayloadOnUnsupportedServiceTier(): void
    {
        self::expectException(InvalidPayloadException::class);
        (new CostCalculator())->compute(
            new Usage(1, 1, serviceTier: 'priority'),
            new Sonnet45PriceTable(),
        );
    }

    public function testItClampsNegativeNetInput(): void
    {
        $usage = new Usage(inputTokens: 100, outputTokens: 50, cacheReadInputTokens: 200);
        $money = (new CostCalculator())->compute($usage, new Sonnet45PriceTable());
        // net_input would be 100-200 = -100 → clamped to 0
        // cost = 0 + 200*0.3 + 50*15 = 60 + 750 = 810 micro-units before *1
        // = 60 (cache_read) + 750 (output) = 810 micro-dollars
        self::assertSame(810, $money->microDollars);
    }

    public function testMoneyRemainsNonNegativeIntegerMicroDollars(): void
    {
        self::expectException(InvalidPayloadException::class);
        new Money(-1);
    }

    public function testMoneyZeroAndAdd(): void
    {
        $a = Money::zero();
        $b = new Money(42);
        self::assertSame(42, $a->add($b)->microDollars);
        self::assertEqualsWithDelta(0.0, $a->asDollars(), PHP_FLOAT_EPSILON);
    }
}
