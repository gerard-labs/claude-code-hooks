<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Event\Tool\Input;

use function array_key_exists;

use Gerard\ClaudeCodeHooks\Exception\InvalidPayloadException;

use function is_string;

use Override;
use SensitiveParameter;

/**
 * Typed input for the first-party `Skill` tool.
 *
 * @psalm-readonly do not log raw — may contain user-typed secrets
 */
final readonly class SkillInput implements ToolInput
{
    public function __construct(
        public string $skill,
        #[SensitiveParameter]
        public ?string $args = null,
    ) {}

    /**
     * @param array<array-key, mixed> $raw
     */
    public static function fromArray(array $raw): self
    {
        if (!array_key_exists('skill', $raw)) {
            throw InvalidPayloadException::missingField('Skill tool_input', '$.skill');
        }

        $skill = $raw['skill'];

        if (!is_string($skill)) {
            throw InvalidPayloadException::wrongType('Skill tool_input', '$.skill', 'string');
        }

        $args = array_key_exists('args', $raw) && is_string($raw['args'])
            ? $raw['args']
            : null;

        return new self($skill, $args);
    }

    #[Override]
    public function toolName(): string
    {
        return 'Skill';
    }
}
