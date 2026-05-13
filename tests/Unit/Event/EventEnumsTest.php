<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Tests\Unit\Event;

use Gerard\ClaudeCodeHooks\Event\Family;
use Gerard\ClaudeCodeHooks\Event\HandlerType;
use Gerard\ClaudeCodeHooks\Event\PermissionMode;
use Gerard\ClaudeCodeHooks\Event\ToolName;
use Gerard\ClaudeCodeHooks\Exception\InvalidPayloadException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Family::class)]
#[CoversClass(HandlerType::class)]
#[CoversClass(PermissionMode::class)]
#[CoversClass(ToolName::class)]
final class EventEnumsTest extends TestCase
{
    public function testFamilyHasAllSevenFamiliesPlusUnknown(): void
    {
        $names = array_map(static fn(Family $f): string => $f->value, Family::cases());
        self::assertCount(8, $names);
        self::assertContains('unknown', $names);
    }

    public function testPermissionModeFromStringRejectsUnknownValue(): void
    {
        self::expectException(InvalidPayloadException::class);
        PermissionMode::fromString('not-a-mode');
    }

    public function testPermissionModeFromStringAcceptsDocumentedValues(): void
    {
        foreach (['default', 'plan', 'acceptEdits', 'auto', 'dontAsk', 'bypassPermissions'] as $value) {
            self::assertSame($value, PermissionMode::fromString($value)->value);
        }
    }

    public function testHandlerTypeCoversFiveDocumentedTypes(): void
    {
        $values = array_map(static fn(HandlerType $t): string => $t->value, HandlerType::cases());
        self::assertSame(['command', 'http', 'mcp_tool', 'prompt', 'agent'], $values);
    }

    public function testToolNameMcpHelperRecognizesPrefix(): void
    {
        self::assertTrue(ToolName::isMcpToolName('mcp__server__tool'));
        self::assertFalse(ToolName::isMcpToolName('Bash'));
    }

    public function testToolNameEnumIncludesThirteenDocumentedTools(): void
    {
        $values = array_map(static fn(ToolName $t): string => $t->value, ToolName::cases());
        self::assertCount(13, $values);
        self::assertSame(
            ['Bash', 'Edit', 'Write', 'Read', 'Glob', 'Grep', 'WebFetch', 'WebSearch', 'Agent', 'AskUserQuestion', 'ExitPlanMode', 'TodoWrite', 'Skill'],
            $values,
        );
    }
}
