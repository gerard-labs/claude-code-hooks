<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Event\Turn;

use function array_key_exists;

use Gerard\ClaudeCodeHooks\Event\AbstractHookEvent;
use Gerard\ClaudeCodeHooks\Event\Family;
use Gerard\ClaudeCodeHooks\Event\PermissionMode;
use Gerard\ClaudeCodeHooks\Exception\InvalidPayloadException;

use function is_bool;

use Override;

final readonly class StopEvent extends AbstractHookEvent
{
    public function __construct(
        string $sessionId,
        string $transcriptPath,
        string $cwd,
        PermissionMode $permissionMode,
        public bool $stopHookActive,
        public ?string $lastAssistantMessage = null,
        ?string $agentId = null,
        ?string $agentType = null,
    ) {
        parent::__construct($sessionId, $transcriptPath, $cwd, $permissionMode, 'Stop', $agentId, $agentType);
    }

    /**
     * @param array<array-key, mixed> $payload
     */
    public static function fromPayload(array $payload): self
    {
        if (!array_key_exists('stop_hook_active', $payload) || !is_bool($payload['stop_hook_active'])) {
            throw InvalidPayloadException::wrongType('Stop', '$.stop_hook_active', 'bool');
        }

        return new self(
            sessionId: self::requireString($payload, 'session_id', 'Stop'),
            transcriptPath: self::requireString($payload, 'transcript_path', 'Stop'),
            cwd: self::requireString($payload, 'cwd', 'Stop'),
            permissionMode: PermissionMode::tryFrom(self::optionalString($payload, 'permission_mode') ?? '') ?? PermissionMode::Default,
            stopHookActive: $payload['stop_hook_active'],
            lastAssistantMessage: self::optionalString($payload, 'last_assistant_message'),
            agentId: self::optionalString($payload, 'agent_id'),
            agentType: self::optionalString($payload, 'agent_type'),
        );
    }

    #[Override]
    public function family(): Family
    {
        return Family::Turn;
    }
}
