<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Event\Session;

use Gerard\ClaudeCodeHooks\Event\AbstractHookEvent;
use Gerard\ClaudeCodeHooks\Event\Family;
use Gerard\ClaudeCodeHooks\Event\PermissionMode;
use Override;

final readonly class SessionEndEvent extends AbstractHookEvent
{
    public function __construct(
        string $sessionId,
        string $transcriptPath,
        string $cwd,
        PermissionMode $permissionMode,
        public string $reason,
        ?string $agentId = null,
        ?string $agentType = null,
    ) {
        parent::__construct($sessionId, $transcriptPath, $cwd, $permissionMode, 'SessionEnd', $agentId, $agentType);
    }

    /**
     * @param array<array-key, mixed> $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self(
            sessionId: self::requireString($payload, 'session_id', 'SessionEnd'),
            transcriptPath: self::requireString($payload, 'transcript_path', 'SessionEnd'),
            cwd: self::requireString($payload, 'cwd', 'SessionEnd'),
            permissionMode: PermissionMode::tryFrom(self::optionalString($payload, 'permission_mode') ?? '') ?? PermissionMode::Default,
            reason: self::requireString($payload, 'reason', 'SessionEnd'),
            agentId: self::optionalString($payload, 'agent_id'),
            agentType: self::optionalString($payload, 'agent_type'),
        );
    }

    #[Override]
    public function family(): Family
    {
        return Family::Session;
    }
}
