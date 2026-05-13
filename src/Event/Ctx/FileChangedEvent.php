<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Event\Ctx;

use Gerard\ClaudeCodeHooks\Event\AbstractHookEvent;
use Gerard\ClaudeCodeHooks\Event\Family;
use Gerard\ClaudeCodeHooks\Event\PermissionMode;
use Override;

final readonly class FileChangedEvent extends AbstractHookEvent
{
    public function __construct(
        string $sessionId,
        string $transcriptPath,
        string $cwd,
        PermissionMode $permissionMode,
        public string $filePath,
        public string $changeType,
        ?string $agentId = null,
        ?string $agentType = null,
    ) {
        parent::__construct($sessionId, $transcriptPath, $cwd, $permissionMode, 'FileChanged', $agentId, $agentType);
    }

    /**
     * @param array<array-key, mixed> $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self(
            sessionId: self::requireString($payload, 'session_id', 'FileChanged'),
            transcriptPath: self::requireString($payload, 'transcript_path', 'FileChanged'),
            cwd: self::requireString($payload, 'cwd', 'FileChanged'),
            permissionMode: PermissionMode::fromString(self::requireString($payload, 'permission_mode', 'FileChanged')),
            filePath: self::requireString($payload, 'file_path', 'FileChanged'),
            changeType: self::requireString($payload, 'change_type', 'FileChanged'),
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
