<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Event\Turn;

use Gerard\ClaudeCodeHooks\Event\AbstractHookEvent;
use Gerard\ClaudeCodeHooks\Event\Family;
use Gerard\ClaudeCodeHooks\Event\PermissionMode;
use Override;

final readonly class StopFailureEvent extends AbstractHookEvent
{
    public function __construct(
        string $sessionId,
        string $transcriptPath,
        string $cwd,
        PermissionMode $permissionMode,
        public string $errorType,
        public string $errorMessage,
        ?string $agentId = null,
        ?string $agentType = null,
    ) {
        parent::__construct($sessionId, $transcriptPath, $cwd, $permissionMode, 'StopFailure', $agentId, $agentType);
    }

    /**
     * @param array<array-key, mixed> $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self(
            sessionId: self::requireString($payload, 'session_id', 'StopFailure'),
            transcriptPath: self::requireString($payload, 'transcript_path', 'StopFailure'),
            cwd: self::requireString($payload, 'cwd', 'StopFailure'),
            permissionMode: PermissionMode::fromString(self::requireString($payload, 'permission_mode', 'StopFailure')),
            errorType: self::requireString($payload, 'error_type', 'StopFailure'),
            errorMessage: self::requireString($payload, 'error_message', 'StopFailure'),
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
