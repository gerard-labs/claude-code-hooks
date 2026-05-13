<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Event\Team;

use Gerard\ClaudeCodeHooks\Event\AbstractHookEvent;
use Gerard\ClaudeCodeHooks\Event\Family;
use Gerard\ClaudeCodeHooks\Event\PermissionMode;
use Override;

final readonly class TeammateIdleEvent extends AbstractHookEvent
{
    public function __construct(
        string $sessionId,
        string $transcriptPath,
        string $cwd,
        PermissionMode $permissionMode,
        public string $teammateName,
        public string $reason,
        ?string $agentId = null,
        ?string $agentType = null,
    ) {
        parent::__construct($sessionId, $transcriptPath, $cwd, $permissionMode, 'TeammateIdle', $agentId, $agentType);
    }

    /**
     * @param array<array-key, mixed> $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self(
            sessionId: self::requireString($payload, 'session_id', 'TeammateIdle'),
            transcriptPath: self::requireString($payload, 'transcript_path', 'TeammateIdle'),
            cwd: self::requireString($payload, 'cwd', 'TeammateIdle'),
            permissionMode: PermissionMode::fromString(self::requireString($payload, 'permission_mode', 'TeammateIdle')),
            teammateName: self::requireString($payload, 'teammate_name', 'TeammateIdle'),
            reason: self::requireString($payload, 'reason', 'TeammateIdle'),
            agentId: self::optionalString($payload, 'agent_id'),
            agentType: self::optionalString($payload, 'agent_type'),
        );
    }

    #[Override]
    public function family(): Family
    {
        return Family::Team;
    }
}
