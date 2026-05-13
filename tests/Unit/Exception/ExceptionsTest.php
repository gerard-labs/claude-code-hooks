<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Tests\Unit\Exception;

use Gerard\ClaudeCodeHooks\Exception\ExtractionFailedException;
use Gerard\ClaudeCodeHooks\Exception\HookException;
use Gerard\ClaudeCodeHooks\Exception\InvalidPayloadException;
use Gerard\ClaudeCodeHooks\Exception\InvalidTranscriptLineException;
use Gerard\ClaudeCodeHooks\Exception\UnknownEventException;

use function in_array;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(InvalidPayloadException::class)]
#[CoversClass(UnknownEventException::class)]
#[CoversClass(InvalidTranscriptLineException::class)]
#[CoversClass(ExtractionFailedException::class)]
final class ExceptionsTest extends TestCase
{
    public function testInvalidPayloadFactories(): void
    {
        self::assertTrue($this->implementsHookException(InvalidPayloadException::class));
        self::assertSame('Missing required field $.x in ctx', InvalidPayloadException::missingField('ctx', '$.x')->getMessage());
        self::assertSame('Field $.x in ctx must be of type string', InvalidPayloadException::wrongType('ctx', '$.x', 'string')->getMessage());
        self::assertStringContainsString('a|b', InvalidPayloadException::unsupportedValue('ctx', '$.x', 'a|b')->getMessage());
    }

    public function testUnknownEventFactory(): void
    {
        $e = UnknownEventException::forName('NeoEvent');
        self::assertTrue($this->implementsHookException($e::class));
        self::assertStringContainsString('NeoEvent', $e->getMessage());
    }

    public function testInvalidTranscriptLineFactories(): void
    {
        $tooLarge = InvalidTranscriptLineException::tooLarge('/tmp/t.jsonl', 7, 9_000_000, 8_388_608);
        $malformed = InvalidTranscriptLineException::malformed('/tmp/t.jsonl', 7);
        self::assertStringContainsString('/tmp/t.jsonl', $tooLarge->getMessage());
        $bare = str_replace(',', '', $tooLarge->getMessage());
        self::assertStringContainsString('9000000', $bare);
        self::assertStringContainsString('malformed', $malformed->getMessage());
    }

    public function testExtractionFailedIsHookException(): void
    {
        self::assertTrue($this->implementsHookException(ExtractionFailedException::class));
    }

    /**
     * @param class-string $exceptionClass
     */
    private function implementsHookException(string $exceptionClass): bool
    {
        $implements = class_implements($exceptionClass);

        return $implements !== false && in_array(HookException::class, $implements, true);
    }
}
