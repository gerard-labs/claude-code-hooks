<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Tests\Unit\Event\Tool\Input;

use Gerard\ClaudeCodeHooks\Event\Tool\Input\WebFetchInput;
use Gerard\ClaudeCodeHooks\Event\Tool\Input\WriteInput;
use Gerard\ClaudeCodeHooks\Exception\InvalidPayloadException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(WriteInput::class)]
#[CoversClass(WebFetchInput::class)]
final class InputBranchCoverageTest extends TestCase
{
    public function testWriteRejectsMissingContent(): void
    {
        self::expectException(InvalidPayloadException::class);
        WriteInput::fromArray(['file_path' => '/p']);
    }

    public function testWriteRejectsNonStringFilePath(): void
    {
        self::expectException(InvalidPayloadException::class);
        WriteInput::fromArray(['file_path' => 1, 'content' => 'x']);
    }

    public function testWebFetchRejectsMissingPrompt(): void
    {
        self::expectException(InvalidPayloadException::class);
        WebFetchInput::fromArray(['url' => 'https://x']);
    }
}
