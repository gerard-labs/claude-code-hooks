<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class SmokeTest extends TestCase
{
    public function testItLoadsAutoload(): void
    {
        self::assertTrue(class_exists(TestCase::class));
    }
}
