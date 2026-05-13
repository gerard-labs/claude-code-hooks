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
use function is_int;
use function is_numeric;
use function is_string;

use Override;

final readonly class PostToolUseEvent extends AbstractHookEvent
{
    /**
     * @param string|array<string, mixed> $toolResponse
     */
    public function __construct(
        string $sessionId,
        string $transcriptPath,
        string $cwd,
        PermissionMode $permissionMode,
        public string $toolName,
        public ToolInput $toolInput,
        public string|array $toolResponse,
        public ?string $toolUseId = null,
        public ?int $durationMs = null,
        ?string $agentId = null,
        ?string $agentType = null,
    ) {
        parent::__construct($sessionId, $transcriptPath, $cwd, $permissionMode, 'PostToolUse', $agentId, $agentType);
    }

    /**
     * @param array<array-key, mixed> $payload
     */
    public static function fromPayload(array $payload): self
    {
        $toolName = self::requireString($payload, 'tool_name', 'PostToolUse');

        if (!array_key_exists('tool_input', $payload) || !is_array($payload['tool_input'])) {
            throw InvalidPayloadException::wrongType('PostToolUse', '$.tool_input', 'object');
        }
        if (!array_key_exists('tool_response', $payload)) {
            throw InvalidPayloadException::missingField('PostToolUse', '$.tool_response');
        }
        $response = $payload['tool_response'];
        if (!is_string($response) && !is_array($response)) {
            throw InvalidPayloadException::wrongType('PostToolUse', '$.tool_response', 'string|object');
        }
        if (is_array($response)) {
            // The wire shape is a JSON object — every key must be a string.
            // Rebuild the array to narrow array<mixed,mixed> to array<string,mixed>.
            $validated = [];
            foreach ($response as $key => $value) {
                if (!is_string($key)) {
                    throw InvalidPayloadException::wrongType('PostToolUse', '$.tool_response', 'object');
                }
                $validated[$key] = $value;
            }
            $response = $validated;
        }

        $durationMs = null;
        if (array_key_exists('duration_ms', $payload)) {
            $raw = $payload['duration_ms'];
            if (is_int($raw)) {
                $durationMs = $raw;
            } elseif (is_numeric($raw) && is_string($raw)) {
                $durationMs = (int) $raw;
            }
        }

        return new self(
            sessionId: self::requireString($payload, 'session_id', 'PostToolUse'),
            transcriptPath: self::requireString($payload, 'transcript_path', 'PostToolUse'),
            cwd: self::requireString($payload, 'cwd', 'PostToolUse'),
            permissionMode: PermissionMode::fromString(self::requireString($payload, 'permission_mode', 'PostToolUse')),
            toolName: $toolName,
            toolInput: ToolInputFactory::fromArray($toolName, $payload['tool_input']),
            toolResponse: $response,
            toolUseId: self::optionalString($payload, 'tool_use_id'),
            durationMs: $durationMs,
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
