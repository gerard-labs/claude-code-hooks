<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Event\Ctx;

use Gerard\ClaudeCodeHooks\Event\AbstractHookEvent;
use Gerard\ClaudeCodeHooks\Event\Family;
use Gerard\ClaudeCodeHooks\Event\PermissionMode;
use Override;

final readonly class CwdChangedEvent extends AbstractHookEvent
{
    public function __construct(
        string $sessionId,
        string $transcriptPath,
        string $cwd,
        PermissionMode $permissionMode,
        public string $oldCwd,
        public string $newCwd,
        ?string $agentId = null,
        ?string $agentType = null,
    ) {
        parent::__construct($sessionId, $transcriptPath, $cwd, $permissionMode, 'CwdChanged', $agentId, $agentType);
    }

    /**
     * @param array<array-key, mixed> $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self(
            sessionId: self::requireString($payload, 'session_id', 'CwdChanged'),
            transcriptPath: self::requireString($payload, 'transcript_path', 'CwdChanged'),
            cwd: self::requireString($payload, 'cwd', 'CwdChanged'),
            permissionMode: PermissionMode::fromString(self::requireString($payload, 'permission_mode', 'CwdChanged')),
            oldCwd: self::requireString($payload, 'old_cwd', 'CwdChanged'),
            newCwd: self::requireString($payload, 'new_cwd', 'CwdChanged'),
            agentId: self::optionalString($payload, 'agent_id'),
            agentType: self::optionalString($payload, 'agent_type'),
        );
    }

    #[Override]
    public function family(): Family
    {
        return Family::Ctx;
    }
}
