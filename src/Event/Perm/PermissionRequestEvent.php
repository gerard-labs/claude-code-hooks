<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Event\Perm;

use function array_key_exists;

use Gerard\ClaudeCodeHooks\Event\AbstractHookEvent;
use Gerard\ClaudeCodeHooks\Event\Family;
use Gerard\ClaudeCodeHooks\Event\PermissionMode;
use Gerard\ClaudeCodeHooks\Exception\InvalidPayloadException;

use function is_array;

use Override;

final readonly class PermissionRequestEvent extends AbstractHookEvent
{
    /**
     * @param array<string, mixed>          $toolInput
     * @param list<array<string, mixed>>    $permissionSuggestions
     */
    public function __construct(
        string $sessionId,
        string $transcriptPath,
        string $cwd,
        PermissionMode $permissionMode,
        public string $toolName,
        public array $toolInput,
        public array $permissionSuggestions = [],
        ?string $agentId = null,
        ?string $agentType = null,
    ) {
        parent::__construct($sessionId, $transcriptPath, $cwd, $permissionMode, 'PermissionRequest', $agentId, $agentType);
    }

    /**
     * @param array<array-key, mixed> $payload
     */
    public static function fromPayload(array $payload): self
    {
        if (!array_key_exists('tool_input', $payload) || !is_array($payload['tool_input'])) {
            throw InvalidPayloadException::wrongType('PermissionRequest', '$.tool_input', 'object');
        }
        /** @var array<string, mixed> $toolInput */
        $toolInput = $payload['tool_input'];

        $suggestions = [];
        if (array_key_exists('permission_suggestions', $payload) && is_array($payload['permission_suggestions'])) {
            foreach ($payload['permission_suggestions'] as $entry) {
                if (is_array($entry)) {
                    /** @var array<string, mixed> $entry */
                    $suggestions[] = $entry;
                }
            }
        }

        return new self(
            sessionId: self::requireString($payload, 'session_id', 'PermissionRequest'),
            transcriptPath: self::requireString($payload, 'transcript_path', 'PermissionRequest'),
            cwd: self::requireString($payload, 'cwd', 'PermissionRequest'),
            permissionMode: PermissionMode::fromString(self::requireString($payload, 'permission_mode', 'PermissionRequest')),
            toolName: self::requireString($payload, 'tool_name', 'PermissionRequest'),
            toolInput: $toolInput,
            permissionSuggestions: $suggestions,
            agentId: self::optionalString($payload, 'agent_id'),
            agentType: self::optionalString($payload, 'agent_type'),
        );
    }

    #[Override]
    public function family(): Family
    {
        return Family::Perm;
    }
}
