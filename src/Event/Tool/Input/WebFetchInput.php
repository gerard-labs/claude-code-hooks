<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Event\Tool\Input;

use function array_key_exists;

use Gerard\ClaudeCodeHooks\Exception\InvalidPayloadException;

use function is_string;

use Override;

final readonly class WebFetchInput implements ToolInput
{
    public function __construct(
        public string $url,
        public string $prompt,
    ) {}

    /**
     * @param array<array-key, mixed> $raw
     */
    public static function fromArray(array $raw): self
    {
        if (!array_key_exists('url', $raw) || !is_string($raw['url'])) {
            throw InvalidPayloadException::wrongType('WebFetch tool_input', '$.url', 'string');
        }
        if (!array_key_exists('prompt', $raw) || !is_string($raw['prompt'])) {
            throw InvalidPayloadException::wrongType('WebFetch tool_input', '$.prompt', 'string');
        }

        return new self($raw['url'], $raw['prompt']);
    }

    #[Override]
    public function toolName(): string
    {
        return 'WebFetch';
    }
}
