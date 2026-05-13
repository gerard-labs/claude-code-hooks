<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Event\Team;

use Gerard\ClaudeCodeHooks\Event\AbstractHookEvent;
use Gerard\ClaudeCodeHooks\Event\Family;
use Gerard\ClaudeCodeHooks\Event\PermissionMode;
use Override;

final readonly class TaskCreatedEvent extends AbstractHookEvent
{
    public function __construct(
        string $sessionId,
        string $transcriptPath,
        string $cwd,
        PermissionMode $permissionMode,
        public string $taskId,
        public string $taskTitle,
        public ?string $taskDescription = null,
        ?string $agentId = null,
        ?string $agentType = null,
    ) {
        parent::__construct($sessionId, $transcriptPath, $cwd, $permissionMode, 'TaskCreated', $agentId, $agentType);
    }

    /**
     * @param array<array-key, mixed> $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self(
            sessionId: self::requireString($payload, 'session_id', 'TaskCreated'),
            transcriptPath: self::requireString($payload, 'transcript_path', 'TaskCreated'),
            cwd: self::requireString($payload, 'cwd', 'TaskCreated'),
            permissionMode: PermissionMode::fromString(self::requireString($payload, 'permission_mode', 'TaskCreated')),
            taskId: self::requireString($payload, 'task_id', 'TaskCreated'),
            taskTitle: self::requireString($payload, 'task_title', 'TaskCreated'),
            taskDescription: self::optionalString($payload, 'task_description'),
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
