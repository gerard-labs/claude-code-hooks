<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Event\Tool\Input;

use function array_key_exists;

use Gerard\ClaudeCodeHooks\Exception\InvalidPayloadException;

use function is_string;

use Override;

final readonly class WriteInput implements ToolInput
{
    public function __construct(
        public string $filePath,
        public string $content,
    ) {}

    /**
     * @param array<array-key, mixed> $raw
     */
    public static function fromArray(array $raw): self
    {
        if (!array_key_exists('file_path', $raw) || !is_string($raw['file_path'])) {
            throw InvalidPayloadException::wrongType('Write tool_input', '$.file_path', 'string');
        }
        if (!array_key_exists('content', $raw) || !is_string($raw['content'])) {
            throw InvalidPayloadException::wrongType('Write tool_input', '$.content', 'string');
        }

        return new self($raw['file_path'], $raw['content']);
    }

    #[Override]
    public function toolName(): string
    {
        return 'Write';
    }
}
