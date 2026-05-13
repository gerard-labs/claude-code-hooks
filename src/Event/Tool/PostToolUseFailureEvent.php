<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Event\Tool;

use function array_key_exists;

use Gerard\ClaudeCodeHooks\Event\AbstractHookEvent;
use Gerard\ClaudeCodeHooks\Event\Family;
use Gerard\ClaudeCodeHooks\Event\PermissionMode;
use Gerard\ClaudeCodeHooks\Event\Tool\Input\ToolInput;
use Gerard\ClaudeCodeHooks\Event\Tool\Input\ToolInputFactory;
use Gerard\ClaudeCodeHooks\Exception\InvalidPayloadException;

use function is_array;

use Override;

final readonly class PostToolUseFailureEvent extends AbstractHookEvent
{
    public function __construct(
        string $sessionId,
        string $transcriptPath,
        string $cwd,
        PermissionMode $permissionMode,
        public string $toolName,
        public ToolInput $toolInput,
        public string $error,
        public ?string $errorType = null,
        public ?string $toolUseId = null,
        ?string $agentId = null,
        ?string $agentType = null,
    ) {
        parent::__construct($sessionId, $transcriptPath, $cwd, $permissionMode, 'PostToolUseFailure', $agentId, $agentType);
    }

    /**
     * @param array<array-key, mixed> $payload
     */
    public static function fromPayload(array $payload): self
    {
        $toolName = self::requireString($payload, 'tool_name', 'PostToolUseFailure');

        if (!array_key_exists('tool_input', $payload) || !is_array($payload['tool_input'])) {
            throw InvalidPayloadException::wrongType('PostToolUseFailure', '$.tool_input', 'object');
        }

        return new self(
            sessionId: self::requireString($payload, 'session_id', 'PostToolUseFailure'),
            transcriptPath: self::requireString($payload, 'transcript_path', 'PostToolUseFailure'),
            cwd: self::requireString($payload, 'cwd', 'PostToolUseFailure'),
            permissionMode: PermissionMode::fromString(self::requireString($payload, 'permission_mode', 'PostToolUseFailure')),
            toolName: $toolName,
            toolInput: ToolInputFactory::fromArray($toolName, $payload['tool_input']),
            error: self::requireString($payload, 'error', 'PostToolUseFailure'),
            errorType: self::optionalString($payload, 'error_type'),
            toolUseId: self::optionalString($payload, 'tool_use_id'),
            agentId: self::optionalString($payload, 'agent_id'),
            agentType: self::optionalString($payload, 'agent_type'),
        );
    }

    #[Override]
    public function family(): Family
    {
        return Family::Tool;
    }
}
