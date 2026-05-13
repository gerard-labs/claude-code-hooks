<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Event\Tool\Input;

use function array_key_exists;

use Gerard\ClaudeCodeHooks\Exception\InvalidPayloadException;

use function is_array;
use function is_string;

use Override;

final readonly class WebSearchInput implements ToolInput
{
    /**
     * @param list<string> $allowedDomains
     * @param list<string> $blockedDomains
     */
    public function __construct(
        public string $query,
        public array $allowedDomains = [],
        public array $blockedDomains = [],
    ) {}

    /**
     * @param array<array-key, mixed> $raw
     */
    public static function fromArray(array $raw): self
    {
        if (!array_key_exists('query', $raw) || !is_string($raw['query'])) {
            throw InvalidPayloadException::wrongType('WebSearch tool_input', '$.query', 'string');
        }

        return new self(
            query: $raw['query'],
            allowedDomains: self::stringList($raw, 'allowed_domains'),
            blockedDomains: self::stringList($raw, 'blocked_domains'),
        );
    }

    #[Override]
    public function toolName(): string
    {
        return 'WebSearch';
    }

    /**
     * @param array<array-key, mixed> $raw
     *
     * @return list<string>
     */
    private static function stringList(array $raw, string $key): array
    {
        if (!array_key_exists($key, $raw) || !is_array($raw[$key])) {
            return [];
        }

        $out = [];
        foreach ($raw[$key] as $entry) {
            if (is_string($entry)) {
                $out[] = $entry;
            }
        }

        return $out;
    }
}
