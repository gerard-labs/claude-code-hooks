<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Event;

use function array_key_exists;

use Gerard\ClaudeCodeHooks\Exception\InvalidPayloadException;

use function is_string;

/**
 * Common shape carried by every hook event payload (OBSERVABILITY_SPEC §3.1).
 *
 * The class is `readonly` and intentionally non-final so the 29 concrete event
 * DTOs can extend it (whitelisted in `ergebnis/phpstan-rules` via
 * `classesAllowedToBeExtended`).
 */
abstract readonly class AbstractHookEvent
{
    public function __construct(
        public string $sessionId,
        public string $transcriptPath,
        public string $cwd,
        public PermissionMode $permissionMode,
        public string $hookEventName,
        public ?string $agentId = null,
        public ?string $agentType = null,
    ) {}

    abstract public function family(): Family;

    /**
     * Pull a required string value from a payload array, raising
     * `InvalidPayloadException` with a JSON-Pointer-style message on miss.
     *
     * @param array<array-key, mixed> $payload
     */
    final protected static function requireString(array $payload, string $key, string $context): string
    {
        if (!array_key_exists($key, $payload)) {
            throw InvalidPayloadException::missingField($context, '$.' . $key);
        }

        $value = $payload[$key];

        if (!is_string($value)) {
            throw InvalidPayloadException::wrongType($context, '$.' . $key, 'string');
        }

        return $value;
    }

    /**
     * @param array<array-key, mixed> $payload
     */
    final protected static function optionalString(array $payload, string $key): ?string
    {
        if (!array_key_exists($key, $payload)) {
            return null;
        }

        $value = $payload[$key];

        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            return null;
        }

        return $value;
    }
}
