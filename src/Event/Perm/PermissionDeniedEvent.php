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

final readonly class PermissionDeniedEvent extends AbstractHookEvent
{
    /**
     * @param array<string, mixed> $toolInput
     */
    public function __construct(
        string $sessionId,
        string $transcriptPath,
        string $cwd,
        PermissionMode $permissionMode,
        public string $toolName,
        public array $toolInput,
        ?string $agentId = null,
        ?string $agentType = null,
    ) {
        parent::__construct($sessionId, $transcriptPath, $cwd, $permissionMode, 'PermissionDenied', $agentId, $agentType);
    }

    /**
     * @param array<array-key, mixed> $payload
     */
    public static function fromPayload(array $payload): self
    {
        if (!array_key_exists('tool_input', $payload) || !is_array($payload['tool_input'])) {
            throw InvalidPayloadException::wrongType('PermissionDenied', '$.tool_input', 'object');
        }
        /** @var array<string, mixed> $toolInput */
        $toolInput = $payload['tool_input'];

        return new self(
            sessionId: self::requireString($payload, 'session_id', 'PermissionDenied'),
            transcriptPath: self::requireString($payload, 'transcript_path', 'PermissionDenied'),
            cwd: self::requireString($payload, 'cwd', 'PermissionDenied'),
            permissionMode: PermissionMode::fromString(self::requireString($payload, 'permission_mode', 'PermissionDenied')),
            toolName: self::requireString($payload, 'tool_name', 'PermissionDenied'),
            toolInput: $toolInput,
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
