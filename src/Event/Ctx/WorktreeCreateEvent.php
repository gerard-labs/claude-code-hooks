<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Event\Ctx;

use function array_key_exists;

use Gerard\ClaudeCodeHooks\Event\AbstractHookEvent;
use Gerard\ClaudeCodeHooks\Event\Family;
use Gerard\ClaudeCodeHooks\Event\PermissionMode;
use Gerard\ClaudeCodeHooks\Exception\InvalidPayloadException;

use function is_bool;

use Override;

final readonly class WorktreeCreateEvent extends AbstractHookEvent
{
    public function __construct(
        string $sessionId,
        string $transcriptPath,
        string $cwd,
        PermissionMode $permissionMode,
        public bool $worktreeIsolation,
        public string $basePath,
        ?string $agentId = null,
        ?string $agentType = null,
    ) {
        parent::__construct($sessionId, $transcriptPath, $cwd, $permissionMode, 'WorktreeCreate', $agentId, $agentType);
    }

    /**
     * @param array<array-key, mixed> $payload
     */
    public static function fromPayload(array $payload): self
    {
        if (!array_key_exists('worktree_isolation', $payload) || !is_bool($payload['worktree_isolation'])) {
            throw InvalidPayloadException::wrongType('WorktreeCreate', '$.worktree_isolation', 'bool');
        }

        return new self(
            sessionId: self::requireString($payload, 'session_id', 'WorktreeCreate'),
            transcriptPath: self::requireString($payload, 'transcript_path', 'WorktreeCreate'),
            cwd: self::requireString($payload, 'cwd', 'WorktreeCreate'),
            permissionMode: PermissionMode::fromString(self::requireString($payload, 'permission_mode', 'WorktreeCreate')),
            worktreeIsolation: $payload['worktree_isolation'],
            basePath: self::requireString($payload, 'base_path', 'WorktreeCreate'),
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
