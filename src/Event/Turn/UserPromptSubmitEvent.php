<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Event\Turn;

use Gerard\ClaudeCodeHooks\Event\AbstractHookEvent;
use Gerard\ClaudeCodeHooks\Event\Family;
use Gerard\ClaudeCodeHooks\Event\PermissionMode;
use Override;

final readonly class UserPromptSubmitEvent extends AbstractHookEvent
{
    public function __construct(
        string $sessionId,
        string $transcriptPath,
        string $cwd,
        PermissionMode $permissionMode,
        public string $prompt,
        public ?string $sessionTitle = null,
        ?string $agentId = null,
        ?string $agentType = null,
    ) {
        parent::__construct($sessionId, $transcriptPath, $cwd, $permissionMode, 'UserPromptSubmit', $agentId, $agentType);
    }

    /**
     * @param array<array-key, mixed> $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self(
            sessionId: self::requireString($payload, 'session_id', 'UserPromptSubmit'),
            transcriptPath: self::requireString($payload, 'transcript_path', 'UserPromptSubmit'),
            cwd: self::requireString($payload, 'cwd', 'UserPromptSubmit'),
            permissionMode: PermissionMode::fromString(self::requireString($payload, 'permission_mode', 'UserPromptSubmit')),
            prompt: self::requireString($payload, 'prompt', 'UserPromptSubmit'),
            sessionTitle: self::optionalString($payload, 'session_title'),
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
