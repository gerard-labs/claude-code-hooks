<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Event\Perm;

use Gerard\ClaudeCodeHooks\Event\AbstractHookEvent;
use Gerard\ClaudeCodeHooks\Event\Family;
use Gerard\ClaudeCodeHooks\Event\PermissionMode;
use Override;

final readonly class NotificationEvent extends AbstractHookEvent
{
    public function __construct(
        string $sessionId,
        string $transcriptPath,
        string $cwd,
        PermissionMode $permissionMode,
        public string $notificationType,
        public string $message,
        ?string $agentId = null,
        ?string $agentType = null,
    ) {
        parent::__construct($sessionId, $transcriptPath, $cwd, $permissionMode, 'Notification', $agentId, $agentType);
    }

    /**
     * @param array<array-key, mixed> $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self(
            sessionId: self::requireString($payload, 'session_id', 'Notification'),
            transcriptPath: self::requireString($payload, 'transcript_path', 'Notification'),
            cwd: self::requireString($payload, 'cwd', 'Notification'),
            permissionMode: PermissionMode::fromString(self::requireString($payload, 'permission_mode', 'Notification')),
            notificationType: self::requireString($payload, 'notification_type', 'Notification'),
            message: self::requireString($payload, 'message', 'Notification'),
            agentId: self::optionalString($payload, 'agent_id'),
            agentType: self::optionalString($payload, 'agent_type'),
        );
    }

    #[Override]
    public function family(): Family
    {
        return Family::Perm;
    }
}
