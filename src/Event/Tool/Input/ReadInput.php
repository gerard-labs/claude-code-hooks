<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Event\Tool\Input;

use function array_key_exists;

use Gerard\ClaudeCodeHooks\Exception\InvalidPayloadException;

use function is_int;
use function is_string;

use Override;

final readonly class ReadInput implements ToolInput
{
    public function __construct(
        public string $filePath,
        public ?int $offset = null,
        public ?int $limit = null,
    ) {}

    /**
     * @param array<array-key, mixed> $raw
     */
    public static function fromArray(array $raw): self
    {
        if (!array_key_exists('file_path', $raw) || !is_string($raw['file_path'])) {
            throw InvalidPayloadException::wrongType('Read tool_input', '$.file_path', 'string');
        }

        $offset = array_key_exists('offset', $raw) && is_int($raw['offset']) ? $raw['offset'] : null;
        $limit = array_key_exists('limit', $raw) && is_int($raw['limit']) ? $raw['limit'] : null;

        return new self($raw['file_path'], $offset, $limit);
    }

    #[Override]
    public function toolName(): string
    {
        return 'Read';
    }
}
