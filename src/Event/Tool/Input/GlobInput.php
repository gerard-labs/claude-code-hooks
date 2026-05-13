<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Event\Tool\Input;

use function array_key_exists;

use Gerard\ClaudeCodeHooks\Exception\InvalidPayloadException;

use function is_string;

use Override;

final readonly class GlobInput implements ToolInput
{
    public function __construct(
        public string $pattern,
        public ?string $path = null,
    ) {}

    /**
     * @param array<array-key, mixed> $raw
     */
    public static function fromArray(array $raw): self
    {
        if (!array_key_exists('pattern', $raw) || !is_string($raw['pattern'])) {
            throw InvalidPayloadException::wrongType('Glob tool_input', '$.pattern', 'string');
        }
        $path = array_key_exists('path', $raw) && is_string($raw['path']) ? $raw['path'] : null;

        return new self($raw['pattern'], $path);
    }

    #[Override]
    public function toolName(): string
    {
        return 'Glob';
    }
}
