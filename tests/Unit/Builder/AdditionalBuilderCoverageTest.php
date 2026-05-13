<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Tests\Unit\Builder;

use Gerard\ClaudeCodeHooks\Builder\HookConfigBuilder;
use Gerard\ClaudeCodeHooks\Builder\HookHandlerBuilder;
use Gerard\ClaudeCodeHooks\Builder\MatcherBuilder;
use Gerard\ClaudeCodeHooks\Exception\InvalidPayloadException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HookConfigBuilder::class)]
#[CoversClass(MatcherBuilder::class)]
#[CoversClass(HookHandlerBuilder::class)]
final class AdditionalBuilderCoverageTest extends TestCase
{
    public function testHandlerToArrayContainsExpectedKeys(): void
    {
        $builder = HookConfigBuilder::create()->event('PreToolUse')->matcher('Bash');
        $handler = $builder->httpHandler('https://x')
            ->withHeader('Authorization', '${TOKEN}')
            ->withTimeout(3)
            ->async(true);
        $array = $handler->toArray();
        self::assertSame('http', $array['type']);
        self::assertSame(3, $array['timeout']);
        self::assertSame(['Authorization' => '${TOKEN}'], $array['headers']);
        self::assertTrue($array['async']);
    }

    public function testHandlerBuilderPropagatesBuildAndChain(): void
    {
        $config = HookConfigBuilder::create()
            ->event('PreToolUse')->matcher('Bash')
            ->httpHandler('https://x')->withTimeout(5)
            ->build();
        self::assertCount(1, $config['hooks']['PreToolUse']);
    }

    public function testMatcherBuilderCommandShortcut(): void
    {
        $config = HookConfigBuilder::create()
            ->event('PreToolUse')->matcher('Bash')->commandHandler('foo.sh')
            ->build();
        self::assertSame('command', $config['hooks']['PreToolUse'][0]['hooks'][0]['type']);
    }

    /**
     * SEC-18 — `withHeader` MUST anchor the regex with `^`, so values that
     * embed `${VAR}` inside arbitrary prefix/suffix text are rejected. Pins
     * the leading `^` against a `PregMatchRemoveCaret` mutation that would
     * otherwise allow `'XX${TOKEN}'` as a valid placeholder.
     */
    public function testWithHeaderRejectsValueWithGarbagePrefix(): void
    {
        $builder = HookConfigBuilder::create()->event('PreToolUse')->matcher('Bash')->httpHandler('https://x');
        $this->expectException(InvalidPayloadException::class);
        $builder->withHeader('Authorization', 'XX${TOKEN}');
    }

    /**
     * `async()` defaults to `true` — calling without an argument must enable
     * async dispatch. Pins the default-value contract against a `TrueValue`
     * mutation flipping the default to `false`.
     */
    public function testAsyncDefaultsToTrueWhenCalledWithoutArgument(): void
    {
        $handler = HookConfigBuilder::create()->event('PreToolUse')->matcher('Bash')
            ->httpHandler('https://x')
            ->async();
        self::assertTrue($handler->toArray()['async']);
    }
}
