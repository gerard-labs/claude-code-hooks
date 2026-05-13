<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Event\Session;

use Gerard\ClaudeCodeHooks\Event\AbstractHookEvent;
use Gerard\ClaudeCodeHooks\Event\Family;
use Gerard\ClaudeCodeHooks\Event\PermissionMode;
use Override;

final readonly class SetupEvent extends AbstractHookEvent
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
        parent::__construct($sessionId, $transcriptPath, $cwd, $permissionMode, 'Setup', $agentId, $agentType);
    }

    /**
     * @param array<array-key, mixed> $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self(
            sessionId: self::requireString($payload, 'session_id', 'Setup'),
            transcriptPath: self::requireString($payload, 'transcript_path', 'Setup'),
            cwd: self::requireString($payload, 'cwd', 'Setup'),
            permissionMode: PermissionMode::fromString(self::requireString($payload, 'permission_mode', 'Setup')),
            trigger: self::requireString($payload, 'trigger', 'Setup'),
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
