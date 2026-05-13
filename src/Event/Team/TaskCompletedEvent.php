<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Event\Team;

use Gerard\ClaudeCodeHooks\Event\AbstractHookEvent;
use Gerard\ClaudeCodeHooks\Event\Family;
use Gerard\ClaudeCodeHooks\Event\PermissionMode;
use Override;

final readonly class TaskCompletedEvent extends AbstractHookEvent
{
    public function __construct(
        string $sessionId,
        string $transcriptPath,
        string $cwd,
        PermissionMode $permissionMode,
        public string $taskId,
        public string $taskTitle,
        ?string $agentId = null,
        ?string $agentType = null,
    ) {
        parent::__construct($sessionId, $transcriptPath, $cwd, $permissionMode, 'TaskCompleted', $agentId, $agentType);
    }

    /**
     * @param array<array-key, mixed> $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self(
            sessionId: self::requireString($payload, 'session_id', 'TaskCompleted'),
            transcriptPath: self::requireString($payload, 'transcript_path', 'TaskCompleted'),
            cwd: self::requireString($payload, 'cwd', 'TaskCompleted'),
            permissionMode: PermissionMode::fromString(self::requireString($payload, 'permission_mode', 'TaskCompleted')),
            taskId: self::requireString($payload, 'task_id', 'TaskCompleted'),
            taskTitle: self::requireString($payload, 'task_title', 'TaskCompleted'),
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
