<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Tests\Unit\Builder;

use Gerard\ClaudeCodeHooks\Builder\HookConfigBuilder;
use Gerard\ClaudeCodeHooks\Builder\HookHandlerBuilder;
use Gerard\ClaudeCodeHooks\Builder\MatcherBuilder;
use Gerard\ClaudeCodeHooks\Exception\InvalidPayloadException;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HookConfigBuilder::class)]
#[CoversClass(MatcherBuilder::class)]
#[CoversClass(HookHandlerBuilder::class)]
final class HookConfigBuilderTest extends TestCase
{
    public function testItBuildsSettingsJsonCompatibleArray(): void
    {
        $config = HookConfigBuilder::create()
            ->event('PreToolUse')
            ->matcher('Bash')
            ->httpHandler('http://127.0.0.1:42987/?event=PreToolUse')
            ->withHeader('Authorization', '${GERARD_TOKEN}')
            ->withTimeout(5)
            ->async(true)
            ->build();
        self::assertArrayHasKey('hooks', $config);
        self::assertArrayHasKey('PreToolUse', $config['hooks']);
        $entry = $config['hooks']['PreToolUse'][0];
        self::assertSame('Bash', $entry['matcher']);
        self::assertSame('http', $entry['hooks'][0]['type']);
        self::assertSame(5, $entry['hooks'][0]['timeout']);
        self::assertTrue($entry['hooks'][0]['async']);
    }

    public function testItRequiresEventBeforeBuild(): void
    {
        self::expectException(LogicException::class);
        HookConfigBuilder::create()->build();
    }

    public function testMatcherBeforeEventThrows(): void
    {
        self::expectException(LogicException::class);
        HookConfigBuilder::create()->matcher('Bash');
    }

    public function testCommandHandlerWithoutCurrentMatcherThrows(): void
    {
        self::expectException(LogicException::class);
        HookConfigBuilder::create()->commandHandler('foo.sh');
    }

    public function testHttpHandlerWithoutCurrentMatcherThrows(): void
    {
        self::expectException(LogicException::class);
        HookConfigBuilder::create()->httpHandler('https://x');
    }

    public function testWithHeaderRejectsRealSecrets(): void
    {
        self::expectException(InvalidPayloadException::class);
        HookConfigBuilder::create()
            ->event('PreToolUse')->matcher('Bash')
            ->httpHandler('https://x')
            ->withHeader('Authorization', 'Bearer real-secret');
    }

    public function testCommandHandlerEmitsCommandEntry(): void
    {
        $config = HookConfigBuilder::create()
            ->event('PostToolUse')->matcher('Edit')
            ->commandHandler('./scripts/format.sh')
            ->build();
        self::assertSame('command', $config['hooks']['PostToolUse'][0]['hooks'][0]['type']);
        self::assertSame('./scripts/format.sh', $config['hooks']['PostToolUse'][0]['hooks'][0]['command']);
    }

    public function testMatcherChainingPreservesEventAcrossHandlers(): void
    {
        $config = HookConfigBuilder::create()
            ->event('PreToolUse')->matcher('Bash')
            ->commandHandler('a.sh')
            ->matcher('Read')
            ->commandHandler('b.sh')
            ->build();
        self::assertCount(2, $config['hooks']['PreToolUse']);
    }

    public function testEmptyMatcherWithNoHandlerSkipped(): void
    {
        // event() called but no handler — produces empty hooks array, skipped
        $config = HookConfigBuilder::create()
            ->event('PreToolUse')
            ->commandHandler('x.sh')
            ->event('PostToolUse')
            ->build();
        self::assertArrayHasKey('PreToolUse', $config['hooks']);
        self::assertArrayNotHasKey('PostToolUse', $config['hooks']);
    }

    public function testHandlerBuilderFluentChainBackToParent(): void
    {
        $config = HookConfigBuilder::create()
            ->event('Stop')->matcher('')
            ->commandHandler('done.sh')->withTimeout(1)->async(false)
            ->event('PreCompact')->matcher('auto')
            ->commandHandler('pre.sh')
            ->build();
        self::assertCount(1, $config['hooks']['Stop']);
        self::assertCount(1, $config['hooks']['PreCompact']);
    }

    public function testMatcherBuilderHandlersAccessor(): void
    {
        $matcher = HookConfigBuilder::create()->event('PreToolUse')->matcher('Bash');
        $matcher->commandHandler('x.sh');
        self::assertCount(1, $matcher->handlers());
        self::assertSame('Bash', $matcher->matcherValue());
        self::assertSame('PreToolUse', $matcher->eventName());
    }
}
