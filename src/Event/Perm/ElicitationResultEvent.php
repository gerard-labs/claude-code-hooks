<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Event\Perm;

use function array_key_exists;

use Gerard\ClaudeCodeHooks\Event\AbstractHookEvent;
use Gerard\ClaudeCodeHooks\Event\Family;
use Gerard\ClaudeCodeHooks\Event\PermissionMode;
use Gerard\ClaudeCodeHooks\Exception\InvalidPayloadException;

use function is_array;

use Override;

final readonly class ElicitationResultEvent extends AbstractHookEvent
{
    /**
     * @param array<string, mixed> $userResponse
     */
    public function __construct(
        string $sessionId,
        string $transcriptPath,
        string $cwd,
        PermissionMode $permissionMode,
        public string $mcpServer,
        public string $toolName,
        public array $userResponse,
        ?string $agentId = null,
        ?string $agentType = null,
    ) {
        parent::__construct($sessionId, $transcriptPath, $cwd, $permissionMode, 'ElicitationResult', $agentId, $agentType);
    }

    /**
     * @param array<array-key, mixed> $payload
     */
    public static function fromPayload(array $payload): self
    {
        if (!array_key_exists('user_response', $payload) || !is_array($payload['user_response'])) {
            throw InvalidPayloadException::wrongType('ElicitationResult', '$.user_response', 'object');
        }
        /** @var array<string, mixed> $userResponse */
        $userResponse = $payload['user_response'];

        return new self(
            sessionId: self::requireString($payload, 'session_id', 'ElicitationResult'),
            transcriptPath: self::requireString($payload, 'transcript_path', 'ElicitationResult'),
            cwd: self::requireString($payload, 'cwd', 'ElicitationResult'),
            permissionMode: PermissionMode::fromString(self::requireString($payload, 'permission_mode', 'ElicitationResult')),
            mcpServer: self::requireString($payload, 'mcp_server', 'ElicitationResult'),
            toolName: self::requireString($payload, 'tool_name', 'ElicitationResult'),
            userResponse: $userResponse,
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
