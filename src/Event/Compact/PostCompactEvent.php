<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Event\Compact;

use Gerard\ClaudeCodeHooks\Event\AbstractHookEvent;
use Gerard\ClaudeCodeHooks\Event\Family;
use Gerard\ClaudeCodeHooks\Event\PermissionMode;
use Override;

final readonly class PostCompactEvent extends AbstractHookEvent
{
    public function __construct(
        string $sessionId,
        string $transcriptPath,
        string $cwd,
        PermissionMode $permissionMode,
        public string $trigger,
        ?string $agentId = null,
        ?string $agentType = null,
    ) {
        parent::__construct($sessionId, $transcriptPath, $cwd, $permissionMode, 'PostCompact', $agentId, $agentType);
    }

    /**
     * @param array<array-key, mixed> $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self(
            sessionId: self::requireString($payload, 'session_id', 'PostCompact'),
            transcriptPath: self::requireString($payload, 'transcript_path', 'PostCompact'),
            cwd: self::requireString($payload, 'cwd', 'PostCompact'),
            permissionMode: PermissionMode::fromString(self::requireString($payload, 'permission_mode', 'PostCompact')),
            trigger: self::requireString($payload, 'trigger', 'PostCompact'),
            agentId: self::optionalString($payload, 'agent_id'),
            agentType: self::optionalString($payload, 'agent_type'),
        );
    }

    #[Override]
    public function family(): Family
    {
        return Family::Compact;
    }
}
