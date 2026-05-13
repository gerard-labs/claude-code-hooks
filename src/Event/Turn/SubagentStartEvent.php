<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Event\Turn;

use Gerard\ClaudeCodeHooks\Event\AbstractHookEvent;
use Gerard\ClaudeCodeHooks\Event\Family;
use Gerard\ClaudeCodeHooks\Event\PermissionMode;
use Override;

final readonly class SubagentStartEvent extends AbstractHookEvent
{
    public function __construct(
        string $sessionId,
        string $transcriptPath,
        string $cwd,
        PermissionMode $permissionMode,
        public string $subagentType,
        public string $prompt,
        ?string $agentId = null,
        ?string $agentType = null,
    ) {
        parent::__construct($sessionId, $transcriptPath, $cwd, $permissionMode, 'SubagentStart', $agentId, $agentType);
    }

    /**
     * @param array<array-key, mixed> $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self(
            sessionId: self::requireString($payload, 'session_id', 'SubagentStart'),
            transcriptPath: self::requireString($payload, 'transcript_path', 'SubagentStart'),
            cwd: self::requireString($payload, 'cwd', 'SubagentStart'),
            permissionMode: PermissionMode::fromString(self::requireString($payload, 'permission_mode', 'SubagentStart')),
            subagentType: self::requireString($payload, 'agent_type', 'SubagentStart'),
            prompt: self::requireString($payload, 'prompt', 'SubagentStart'),
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
