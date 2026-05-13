<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Event\Tool;

use function array_key_exists;

use Gerard\ClaudeCodeHooks\Event\AbstractHookEvent;
use Gerard\ClaudeCodeHooks\Event\Family;
use Gerard\ClaudeCodeHooks\Event\PermissionMode;
use Gerard\ClaudeCodeHooks\Exception\InvalidPayloadException;

use function is_array;

use Override;

final readonly class PostToolBatchEvent extends AbstractHookEvent
{
    /**
     * @param list<array<string, mixed>> $toolCalls
     */
    public function __construct(
        string $sessionId,
        string $transcriptPath,
        string $cwd,
        PermissionMode $permissionMode,
        public array $toolCalls,
        ?string $agentId = null,
        ?string $agentType = null,
    ) {
        parent::__construct($sessionId, $transcriptPath, $cwd, $permissionMode, 'PostToolBatch', $agentId, $agentType);
    }

    /**
     * @param array<array-key, mixed> $payload
     */
    public static function fromPayload(array $payload): self
    {
        if (!array_key_exists('tool_calls', $payload) || !is_array($payload['tool_calls'])) {
            throw InvalidPayloadException::wrongType('PostToolBatch', '$.tool_calls', 'array');
        }

        $calls = [];
        foreach ($payload['tool_calls'] as $entry) {
            if (is_array($entry)) {
                /** @var array<string, mixed> $entry */
                $calls[] = $entry;
            }
        }

        return new self(
            sessionId: self::requireString($payload, 'session_id', 'PostToolBatch'),
            transcriptPath: self::requireString($payload, 'transcript_path', 'PostToolBatch'),
            cwd: self::requireString($payload, 'cwd', 'PostToolBatch'),
            permissionMode: PermissionMode::fromString(self::requireString($payload, 'permission_mode', 'PostToolBatch')),
            toolCalls: $calls,
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
