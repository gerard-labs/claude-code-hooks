<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Tests\Unit\Event\Tool\Input;

use Gerard\ClaudeCodeHooks\Event\Tool\Input\TodoWriteInput;
use Gerard\ClaudeCodeHooks\Exception\InvalidPayloadException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TodoWriteInput::class)]
final class TodoWriteInputTest extends TestCase
{
    public function test_it_round_trips_a_minimal_todos_payload(): void
    {
        $input = TodoWriteInput::fromArray([
            'todos' => [
                ['content' => 'A', 'status' => 'pending', 'activeForm' => 'Doing A'],
                ['content' => 'B', 'status' => 'in_progress', 'activeForm' => 'Doing B'],
            ],
        ]);

        self::assertSame('TodoWrite', $input->toolName());
        self::assertCount(2, $input->todos);
        self::assertSame('A', $input->todos[0]['content']);
        self::assertSame('in_progress', $input->todos[1]['status']);
    }

    public function test_it_rejects_payload_without_todos(): void
    {
        self::expectException(InvalidPayloadException::class);
        TodoWriteInput::fromArray([]);
    }

    public function test_it_rejects_non_array_todos(): void
    {
        self::expectException(InvalidPayloadException::class);
        TodoWriteInput::fromArray(['todos' => 'not-an-array']);
    }

    public function test_it_rejects_todo_entry_missing_status(): void
    {
        self::expectException(InvalidPayloadException::class);
        TodoWriteInput::fromArray([
            'todos' => [['content' => 'A', 'activeForm' => 'Doing A']],
        ]);
    }

    public function test_it_rejects_todo_entry_with_non_string_content(): void
    {
        self::expectException(InvalidPayloadException::class);
        TodoWriteInput::fromArray([
            'todos' => [['content' => 42, 'status' => 'pending', 'activeForm' => 'x']],
        ]);
    }

    public function test_it_rejects_non_array_todo_entry(): void
    {
        self::expectException(InvalidPayloadException::class);
        TodoWriteInput::fromArray(['todos' => ['not-an-object']]);
    }
}
