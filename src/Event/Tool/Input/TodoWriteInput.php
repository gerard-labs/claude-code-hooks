<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Event\Tool\Input;

use function array_key_exists;

use Gerard\ClaudeCodeHooks\Exception\InvalidPayloadException;

use function is_array;
use function is_string;

use Override;

/**
 * Typed input for the first-party `TodoWrite` tool.
 *
 * Each todo carries a human-readable `content`, a `status` (`pending`,
 * `in_progress`, `completed`), and an `activeForm` (the present-continuous
 * rendering shown while the task is executing).
 */
final readonly class TodoWriteInput implements ToolInput
{
    /**
     * @param list<array{content: string, status: string, activeForm: string}> $todos
     */
    public function __construct(
        public array $todos,
    ) {}

    /**
     * @param array<array-key, mixed> $raw
     */
    public static function fromArray(array $raw): self
    {
        if (!array_key_exists('todos', $raw) || !is_array($raw['todos'])) {
            throw InvalidPayloadException::wrongType('TodoWrite tool_input', '$.todos', 'array');
        }

        $todos = [];
        foreach ($raw['todos'] as $entry) {
            if (!is_array($entry)) {
                throw InvalidPayloadException::wrongType('TodoWrite tool_input', '$.todos[]', 'object');
            }
            if (!isset($entry['content'], $entry['status'], $entry['activeForm'])
                || !is_string($entry['content'])
                || !is_string($entry['status'])
                || !is_string($entry['activeForm'])
            ) {
                throw InvalidPayloadException::wrongType(
                    'TodoWrite tool_input',
                    '$.todos[]',
                    'object{content:string,status:string,activeForm:string}',
                );
            }
            $todos[] = [
                'content' => $entry['content'],
                'status' => $entry['status'],
                'activeForm' => $entry['activeForm'],
            ];
        }

        return new self($todos);
    }

    #[Override]
    public function toolName(): string
    {
        return 'TodoWrite';
    }
}
