<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Tests\Unit\Support;

use Gerard\ClaudeCodeHooks\Exception\InvalidPayloadException;
use Gerard\ClaudeCodeHooks\Support\FrontmatterDecoder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FrontmatterDecoder::class)]
final class FrontmatterDecoderTest extends TestCase
{
    public function testItDecodesYamlMapping(): void
    {
        $result = FrontmatterDecoder::decode("name: foo\ndescription: bar", 'fixture');
        self::assertSame(['name' => 'foo', 'description' => 'bar'], $result);
    }

    public function testItRejectsMalformedYaml(): void
    {
        self::expectException(InvalidPayloadException::class);
        FrontmatterDecoder::decode("name: foo\n  bad: indent: nope", 'fixture');
    }

    public function testItSplitsFrontmatterAndBody(): void
    {
        $doc = "---\nname: skill\n---\nBody markdown\nhere\n";
        $split = FrontmatterDecoder::split($doc, 'SKILL.md');
        self::assertSame(['name' => 'skill'], $split['frontmatter']);
        self::assertSame("Body markdown\nhere\n", $split['body']);
    }

    public function testItRejectsMissingOpeningFence(): void
    {
        self::expectException(InvalidPayloadException::class);
        FrontmatterDecoder::split("name: foo\n", 'fixture');
    }

    public function testItRejectsMissingClosingFence(): void
    {
        self::expectException(InvalidPayloadException::class);
        FrontmatterDecoder::split("---\nname: foo\nbody without closing\n", 'fixture');
    }

    public function testItRejectsScalarFrontmatter(): void
    {
        self::expectException(InvalidPayloadException::class);
        FrontmatterDecoder::decode('42', 'fixture');
    }
}
