<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Tests\Unit\Event\Tool\Input;

use Gerard\ClaudeCodeHooks\Event\Tool\Input\AgentInput;
use Gerard\ClaudeCodeHooks\Event\Tool\Input\AskUserQuestionInput;
use Gerard\ClaudeCodeHooks\Event\Tool\Input\BashInput;
use Gerard\ClaudeCodeHooks\Event\Tool\Input\EditInput;
use Gerard\ClaudeCodeHooks\Event\Tool\Input\GlobInput;
use Gerard\ClaudeCodeHooks\Event\Tool\Input\GrepInput;
use Gerard\ClaudeCodeHooks\Event\Tool\Input\RawToolInput;
use Gerard\ClaudeCodeHooks\Event\Tool\Input\ReadInput;
use Gerard\ClaudeCodeHooks\Event\Tool\Input\ToolInputFactory;
use Gerard\ClaudeCodeHooks\Event\Tool\Input\WebFetchInput;
use Gerard\ClaudeCodeHooks\Event\Tool\Input\WebSearchInput;
use Gerard\ClaudeCodeHooks\Event\Tool\Input\WriteInput;
use Gerard\ClaudeCodeHooks\Exception\InvalidPayloadException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BashInput::class)]
#[CoversClass(EditInput::class)]
#[CoversClass(WriteInput::class)]
#[CoversClass(ReadInput::class)]
#[CoversClass(GlobInput::class)]
#[CoversClass(GrepInput::class)]
#[CoversClass(WebFetchInput::class)]
#[CoversClass(WebSearchInput::class)]
#[CoversClass(AgentInput::class)]
#[CoversClass(AskUserQuestionInput::class)]
#[CoversClass(RawToolInput::class)]
#[CoversClass(ToolInputFactory::class)]
final class ToolInputsTest extends TestCase
{
    public function testBashInput(): void
    {
        $i = BashInput::fromArray(['command' => 'ls', 'description' => 'list', 'timeout' => 5, 'run_in_background' => false]);
        self::assertSame('Bash', $i->toolName());
        self::assertSame('ls', $i->command);
        self::assertSame(5, $i->timeout);
        self::assertFalse($i->runInBackground);
        self::assertNull($i->dangerouslyDisableSandbox);
    }

    public function testBashInputAcceptsDangerouslyDisableSandbox(): void
    {
        // Real corpus carries this Anthropic-documented flag on 12/1979 Bash
        // invocations (~0.6 %). Pin its parsing so the runInBackground/timeout
        // path stays orthogonal.
        $i = BashInput::fromArray([
            'command' => 'ls',
            'dangerouslyDisableSandbox' => true,
        ]);
        self::assertTrue($i->dangerouslyDisableSandbox);
    }

    public function testBashInputIgnoresNonBoolDangerouslyDisableSandbox(): void
    {
        $i = BashInput::fromArray([
            'command' => 'ls',
            'dangerouslyDisableSandbox' => 'yes',
        ]);
        self::assertNull($i->dangerouslyDisableSandbox);
    }

    public function testBashInputRequiresCommand(): void
    {
        self::expectException(InvalidPayloadException::class);
        BashInput::fromArray([]);
    }

    public function testBashInputRejectsNonStringCommand(): void
    {
        self::expectException(InvalidPayloadException::class);
        BashInput::fromArray(['command' => 42]);
    }

    public function testEditInput(): void
    {
        $i = EditInput::fromArray(['file_path' => '/p', 'old_string' => 'a', 'new_string' => 'b', 'replace_all' => true]);
        self::assertSame('Edit', $i->toolName());
        self::assertTrue($i->replaceAll);
    }

    public function testEditInputRejectsMissingNewString(): void
    {
        self::expectException(InvalidPayloadException::class);
        EditInput::fromArray(['file_path' => '/p', 'old_string' => 'a']);
    }

    public function testEditInputRejectsNonStringField(): void
    {
        self::expectException(InvalidPayloadException::class);
        EditInput::fromArray(['file_path' => 1, 'old_string' => 'a', 'new_string' => 'b']);
    }

    public function testWriteInput(): void
    {
        $i = WriteInput::fromArray(['file_path' => '/p', 'content' => 'x']);
        self::assertSame('Write', $i->toolName());
    }

    public function testWriteInputRejectsMalformed(): void
    {
        self::expectException(InvalidPayloadException::class);
        WriteInput::fromArray(['file_path' => '/p']);
    }

    public function testReadInput(): void
    {
        $i = ReadInput::fromArray(['file_path' => '/p', 'offset' => 10, 'limit' => 50]);
        self::assertSame('Read', $i->toolName());
        self::assertSame(10, $i->offset);
        self::assertSame(50, $i->limit);
    }

    public function testReadInputRejectsMalformed(): void
    {
        self::expectException(InvalidPayloadException::class);
        ReadInput::fromArray([]);
    }

    public function testGlobInput(): void
    {
        $i = GlobInput::fromArray(['pattern' => '*.php', 'path' => '/x']);
        self::assertSame('Glob', $i->toolName());
        self::assertSame('/x', $i->path);
    }

    public function testGlobInputRejectsMalformed(): void
    {
        self::expectException(InvalidPayloadException::class);
        GlobInput::fromArray([]);
    }

    public function testGrepInput(): void
    {
        $i = GrepInput::fromArray(['pattern' => 'foo', 'path' => '/x', 'glob' => '*.php', 'output_mode' => 'content', '-i' => true, 'multiline' => false]);
        self::assertSame('Grep', $i->toolName());
        self::assertTrue($i->caseInsensitive);
        // New fields default to null when absent.
        self::assertNull($i->lineNumbers);
        self::assertNull($i->linesAfter);
        self::assertNull($i->linesBefore);
        self::assertNull($i->contextLines);
        self::assertNull($i->headLimit);
    }

    public function testGrepInputParsesAllRipgrepFlags(): void
    {
        // Real corpus shows Claude Code passes the ripgrep flags as literal
        // wire keys (`-n`, `-A`, `-B`, `-C`) plus a `head_limit` cap.
        // 75/139 Grep invocations carry `-n`, 18/139 carry `head_limit`.
        $i = GrepInput::fromArray([
            'pattern' => 'foo',
            '-n' => true,
            '-A' => 3,
            '-B' => 1,
            '-C' => 2,
            'head_limit' => 30,
        ]);
        self::assertTrue($i->lineNumbers);
        self::assertSame(3, $i->linesAfter);
        self::assertSame(1, $i->linesBefore);
        self::assertSame(2, $i->contextLines);
        self::assertSame(30, $i->headLimit);
    }

    public function testGrepInputIgnoresNonBoolLineNumbers(): void
    {
        $i = GrepInput::fromArray(['pattern' => 'foo', '-n' => 1]);
        self::assertNull($i->lineNumbers);
    }

    public function testGrepInputIgnoresNonIntContextFlags(): void
    {
        $i = GrepInput::fromArray([
            'pattern' => 'foo',
            '-A' => '3',     // ripgrep accepts string but we expect int from JSON.
            'head_limit' => 'thirty',
        ]);
        self::assertNull($i->linesAfter);
        self::assertNull($i->headLimit);
    }

    public function testGrepInputRejectsMalformed(): void
    {
        self::expectException(InvalidPayloadException::class);
        GrepInput::fromArray([]);
    }

    public function testWebFetchInput(): void
    {
        $i = WebFetchInput::fromArray(['url' => 'https://x', 'prompt' => 'why']);
        self::assertSame('WebFetch', $i->toolName());
    }

    public function testWebFetchRejectsMalformed(): void
    {
        self::expectException(InvalidPayloadException::class);
        WebFetchInput::fromArray(['url' => 'x']);
    }

    public function testWebSearchInput(): void
    {
        $i = WebSearchInput::fromArray(['query' => 'q', 'allowed_domains' => ['a.com'], 'blocked_domains' => ['b.com']]);
        self::assertSame('WebSearch', $i->toolName());
        self::assertSame(['a.com'], $i->allowedDomains);
    }

    public function testWebSearchRejectsMalformed(): void
    {
        self::expectException(InvalidPayloadException::class);
        WebSearchInput::fromArray([]);
    }

    public function testAgentInput(): void
    {
        $i = AgentInput::fromArray(['prompt' => 'p', 'description' => 'd', 'subagent_type' => 'Explore', 'model' => 'haiku']);
        self::assertSame('Agent', $i->toolName());
        self::assertSame('Explore', $i->subagentType);
    }

    public function testAgentRejectsMalformed(): void
    {
        self::expectException(InvalidPayloadException::class);
        AgentInput::fromArray([]);
    }

    public function testAskUserQuestionInput(): void
    {
        $i = AskUserQuestionInput::fromArray(['questions' => [['q' => '?']], 'answers' => [['a' => '!']]]);
        self::assertSame('AskUserQuestion', $i->toolName());
        self::assertCount(1, $i->questions);
    }

    public function testAskUserQuestionAcceptsEmptyArrays(): void
    {
        $i = AskUserQuestionInput::fromArray([]);
        self::assertSame([], $i->questions);
    }

    public function testRawToolInput(): void
    {
        $i = RawToolInput::fromArray('mcp__foo__bar', ['x' => 1, 2 => 'ignored']);
        self::assertSame('mcp__foo__bar', $i->toolName());
        self::assertArrayHasKey('x', $i->payload);
    }

    public function testRawToolInputHostsUntypedFirstPartyTool(): void
    {
        $i = RawToolInput::fromArray('ToolSearch', ['query' => 'x']);
        self::assertSame('ToolSearch', $i->toolName());
        self::assertSame(['query' => 'x'], $i->payload);
    }

    public function testFactoryDispatchesByEnum(): void
    {
        self::assertInstanceOf(BashInput::class, ToolInputFactory::fromArray('Bash', ['command' => 'ls']));
        self::assertInstanceOf(EditInput::class, ToolInputFactory::fromArray('Edit', ['file_path' => '/p', 'old_string' => 'a', 'new_string' => 'b']));
        self::assertInstanceOf(WriteInput::class, ToolInputFactory::fromArray('Write', ['file_path' => '/p', 'content' => 'x']));
        self::assertInstanceOf(ReadInput::class, ToolInputFactory::fromArray('Read', ['file_path' => '/p']));
        self::assertInstanceOf(GlobInput::class, ToolInputFactory::fromArray('Glob', ['pattern' => '*.php']));
        self::assertInstanceOf(GrepInput::class, ToolInputFactory::fromArray('Grep', ['pattern' => 'foo']));
        self::assertInstanceOf(WebFetchInput::class, ToolInputFactory::fromArray('WebFetch', ['url' => 'https://x', 'prompt' => 'p']));
        self::assertInstanceOf(WebSearchInput::class, ToolInputFactory::fromArray('WebSearch', ['query' => 'q']));
        self::assertInstanceOf(AgentInput::class, ToolInputFactory::fromArray('Agent', ['prompt' => 'p']));
        self::assertInstanceOf(AskUserQuestionInput::class, ToolInputFactory::fromArray('AskUserQuestion', []));
        self::assertInstanceOf(RawToolInput::class, ToolInputFactory::fromArray('ExitPlanMode', []));
        self::assertInstanceOf(RawToolInput::class, ToolInputFactory::fromArray('mcp__server__tool', ['x' => 1]));
        self::assertInstanceOf(RawToolInput::class, ToolInputFactory::fromArray('ToolSearch', ['query' => 'q']));
    }
}
