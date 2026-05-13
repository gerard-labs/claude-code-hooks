<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Event\Ctx;

use Gerard\ClaudeCodeHooks\Event\AbstractHookEvent;
use Gerard\ClaudeCodeHooks\Event\Family;
use Gerard\ClaudeCodeHooks\Event\PermissionMode;
use Override;

final readonly class WorktreeRemoveEvent extends AbstractHookEvent
{
    public function __construct(
        string $sessionId,
        string $transcriptPath,
        string $cwd,
        PermissionMode $permissionMode,
        public string $worktreePath,
        ?string $agentId = null,
        ?string $agentType = null,
    ) {
        parent::__construct($sessionId, $transcriptPath, $cwd, $permissionMode, 'WorktreeRemove', $agentId, $agentType);
    }

    /**
     * @param array<array-key, mixed> $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self(
            sessionId: self::requireString($payload, 'session_id', 'WorktreeRemove'),
            transcriptPath: self::requireString($payload, 'transcript_path', 'WorktreeRemove'),
            cwd: self::requireString($payload, 'cwd', 'WorktreeRemove'),
            permissionMode: PermissionMode::fromString(self::requireString($payload, 'permission_mode', 'WorktreeRemove')),
            worktreePath: self::requireString($payload, 'worktree_path', 'WorktreeRemove'),
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
