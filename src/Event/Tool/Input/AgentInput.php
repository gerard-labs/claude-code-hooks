<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Event\Tool\Input;

use function array_key_exists;

use Gerard\ClaudeCodeHooks\Exception\InvalidPayloadException;

use function is_string;

use Override;

final readonly class AgentInput implements ToolInput
{
    public function __construct(
        public string $prompt,
        public ?string $description = null,
        public ?string $subagentType = null,
        public ?string $model = null,
    ) {}

    /**
     * @param array<array-key, mixed> $raw
     */
    public static function fromArray(array $raw): self
    {
        if (!array_key_exists('prompt', $raw) || !is_string($raw['prompt'])) {
            throw InvalidPayloadException::wrongType('Agent tool_input', '$.prompt', 'string');
        }

        return new self(
            prompt: $raw['prompt'],
            description: array_key_exists('description', $raw) && is_string($raw['description']) ? $raw['description'] : null,
            subagentType: array_key_exists('subagent_type', $raw) && is_string($raw['subagent_type']) ? $raw['subagent_type'] : null,
            model: array_key_exists('model', $raw) && is_string($raw['model']) ? $raw['model'] : null,
        );
    }

    #[Override]
    public function toolName(): string
    {
        return 'Agent';
    }
}
