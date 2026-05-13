<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Event\Tool\Input;

use function array_key_exists;

use Gerard\ClaudeCodeHooks\Exception\InvalidPayloadException;

use function is_bool;
use function is_int;
use function is_string;

use Override;

final readonly class BashInput implements ToolInput
{
    public function __construct(
        public string $command,
        public ?string $description = null,
        public ?int $timeout = null,
        public ?bool $runInBackground = null,
        public ?bool $dangerouslyDisableSandbox = null,
    ) {}

    /**
     * @param array<array-key, mixed> $raw
     */
    public static function fromArray(array $raw): self
    {
        if (!array_key_exists('command', $raw)) {
            throw InvalidPayloadException::missingField('Bash tool_input', '$.command');
        }

        $command = $raw['command'];

        if (!is_string($command)) {
            throw InvalidPayloadException::wrongType('Bash tool_input', '$.command', 'string');
        }

        $description = array_key_exists('description', $raw) && is_string($raw['description'])
            ? $raw['description']
            : null;

        $timeout = array_key_exists('timeout', $raw) && is_int($raw['timeout'])
            ? $raw['timeout']
            : null;

        $runInBackground = array_key_exists('run_in_background', $raw) && is_bool($raw['run_in_background'])
            ? $raw['run_in_background']
            : null;

        $dangerouslyDisableSandbox = array_key_exists('dangerouslyDisableSandbox', $raw) && is_bool($raw['dangerouslyDisableSandbox'])
            ? $raw['dangerouslyDisableSandbox']
            : null;

        return new self($command, $description, $timeout, $runInBackground, $dangerouslyDisableSandbox);
    }

    #[Override]
    public function toolName(): string
    {
        return 'Bash';
    }
}
