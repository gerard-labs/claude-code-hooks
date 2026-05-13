<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Event\Tool\Input;

use function array_key_exists;

use Gerard\ClaudeCodeHooks\Exception\InvalidPayloadException;

use function is_bool;
use function is_string;

use Override;

final readonly class EditInput implements ToolInput
{
    public function __construct(
        public string $filePath,
        public string $oldString,
        public string $newString,
        public ?bool $replaceAll = null,
    ) {}

    /**
     * @param array<array-key, mixed> $raw
     */
    public static function fromArray(array $raw): self
    {
        $filePath = self::requireString($raw, 'file_path');
        $oldString = self::requireString($raw, 'old_string');
        $newString = self::requireString($raw, 'new_string');
        $replaceAll = array_key_exists('replace_all', $raw) && is_bool($raw['replace_all'])
            ? $raw['replace_all']
            : null;

        return new self($filePath, $oldString, $newString, $replaceAll);
    }

    #[Override]
    public function toolName(): string
    {
        return 'Edit';
    }

    /**
     * @param array<array-key, mixed> $raw
     */
    private static function requireString(array $raw, string $key): string
    {
        if (!array_key_exists($key, $raw)) {
            throw InvalidPayloadException::missingField('Edit tool_input', '$.' . $key);
        }
        if (!is_string($raw[$key])) {
            throw InvalidPayloadException::wrongType('Edit tool_input', '$.' . $key, 'string');
        }

        return $raw[$key];
    }
}
