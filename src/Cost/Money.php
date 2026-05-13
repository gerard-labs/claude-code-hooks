<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Cost;

use Gerard\ClaudeCodeHooks\Exception\InvalidPayloadException;

/**
 * Integer micro-dollar amount (1 micro-dollar = $0.000001).
 *
 * Money is closed under addition (`add`); division and currency conversion are
 * intentionally absent at this layer (would require localization input).
 */
final readonly class Money
{
    public function __construct(public int $microDollars)
    {
        if ($microDollars < 0) {
            throw InvalidPayloadException::unsupportedValue('Money', '$.microDollars', '>= 0');
        }
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public function add(self $other): self
    {
        return new self($this->microDollars + $other->microDollars);
    }

    public function asDollars(): float
    {
        return $this->microDollars / 1_000_000;
    }
}
