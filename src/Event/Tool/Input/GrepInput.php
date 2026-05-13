<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Event\Tool\Input;

use function array_key_exists;

use Gerard\ClaudeCodeHooks\Exception\InvalidPayloadException;

use function is_bool;
use function is_int;
use function is_string;

use Override;

final readonly class GrepInput implements ToolInput
{
    public function __construct(
        public string $pattern,
        public ?string $path = null,
        public ?string $glob = null,
        public ?string $outputMode = null,
        public ?bool $caseInsensitive = null,
        public ?bool $multiline = null,
        public ?bool $lineNumbers = null,
        public ?int $linesAfter = null,
        public ?int $linesBefore = null,
        public ?int $contextLines = null,
        public ?int $headLimit = null,
    ) {}

    /**
     * @param array<array-key, mixed> $raw
     */
    public static function fromArray(array $raw): self
    {
        if (!array_key_exists('pattern', $raw) || !is_string($raw['pattern'])) {
            throw InvalidPayloadException::wrongType('Grep tool_input', '$.pattern', 'string');
        }

        return new self(
            pattern: $raw['pattern'],
            path: array_key_exists('path', $raw) && is_string($raw['path']) ? $raw['path'] : null,
            glob: array_key_exists('glob', $raw) && is_string($raw['glob']) ? $raw['glob'] : null,
            outputMode: array_key_exists('output_mode', $raw) && is_string($raw['output_mode']) ? $raw['output_mode'] : null,
            caseInsensitive: array_key_exists('-i', $raw) && is_bool($raw['-i']) ? $raw['-i'] : null,
            multiline: array_key_exists('multiline', $raw) && is_bool($raw['multiline']) ? $raw['multiline'] : null,
            lineNumbers: array_key_exists('-n', $raw) && is_bool($raw['-n']) ? $raw['-n'] : null,
            linesAfter: array_key_exists('-A', $raw) && is_int($raw['-A']) ? $raw['-A'] : null,
            linesBefore: array_key_exists('-B', $raw) && is_int($raw['-B']) ? $raw['-B'] : null,
            contextLines: array_key_exists('-C', $raw) && is_int($raw['-C']) ? $raw['-C'] : null,
            headLimit: array_key_exists('head_limit', $raw) && is_int($raw['head_limit']) ? $raw['head_limit'] : null,
        );
    }

    #[Override]
    public function toolName(): string
    {
        return 'Grep';
    }
}
