<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Event\Turn;

use Gerard\ClaudeCodeHooks\Event\AbstractHookEvent;
use Gerard\ClaudeCodeHooks\Event\Family;
use Gerard\ClaudeCodeHooks\Event\PermissionMode;
use Override;

final readonly class UserPromptExpansionEvent extends AbstractHookEvent
{
    public function __construct(
        string $sessionId,
        string $transcriptPath,
        string $cwd,
        PermissionMode $permissionMode,
        public string $expansionType,
        public string $commandName,
        public string $prompt,
        public ?string $commandArgs = null,
        public ?string $commandSource = null,
        ?string $agentId = null,
        ?string $agentType = null,
    ) {
        parent::__construct($sessionId, $transcriptPath, $cwd, $permissionMode, 'UserPromptExpansion', $agentId, $agentType);
    }

    /**
     * @param array<array-key, mixed> $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self(
            sessionId: self::requireString($payload, 'session_id', 'UserPromptExpansion'),
            transcriptPath: self::requireString($payload, 'transcript_path', 'UserPromptExpansion'),
            cwd: self::requireString($payload, 'cwd', 'UserPromptExpansion'),
            permissionMode: PermissionMode::fromString(self::requireString($payload, 'permission_mode', 'UserPromptExpansion')),
            expansionType: self::requireString($payload, 'expansion_type', 'UserPromptExpansion'),
            commandName: self::requireString($payload, 'command_name', 'UserPromptExpansion'),
            prompt: self::requireString($payload, 'prompt', 'UserPromptExpansion'),
            commandArgs: self::optionalString($payload, 'command_args'),
            commandSource: self::optionalString($payload, 'command_source'),
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
