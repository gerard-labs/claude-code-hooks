<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Tests\Unit\Support;

use Gerard\ClaudeCodeHooks\Exception\InvalidPayloadException;
use Gerard\ClaudeCodeHooks\Support\JsonDecoder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(JsonDecoder::class)]
final class JsonDecoderTest extends TestCase
{
    public function testItDecodesValidJsonObject(): void
    {
        $result = JsonDecoder::decode('{"a":1,"b":[2,3]}', 'fixture');
        self::assertSame(['a' => 1, 'b' => [2, 3]], $result);
    }

    public function testItThrowsInvalidPayloadOnTruncatedJsonWithoutLeakingContent(): void
    {
        try {
            JsonDecoder::decode('{"unterminated":', '/tmp/settings.json');
            self::fail('Expected InvalidPayloadException');
        } catch (InvalidPayloadException $e) {
            self::assertStringContainsString('/tmp/settings.json', $e->getMessage());
            self::assertStringNotContainsString('unterminated', $e->getMessage());
        }
    }

    public function testItRejectsScalarTopLevelJson(): void
    {
        self::expectException(InvalidPayloadException::class);
        JsonDecoder::decode('42', 'fixture');
    }
}
