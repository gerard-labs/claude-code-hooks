<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Event\Perm;

use function array_key_exists;

use Gerard\ClaudeCodeHooks\Event\AbstractHookEvent;
use Gerard\ClaudeCodeHooks\Event\Family;
use Gerard\ClaudeCodeHooks\Event\PermissionMode;

use function is_array;

use Override;

final readonly class ElicitationEvent extends AbstractHookEvent
{
    /**
     * @param list<array<string, mixed>> $fields
     */
    public function __construct(
        string $sessionId,
        string $transcriptPath,
        string $cwd,
        PermissionMode $permissionMode,
        public string $mcpServer,
        public string $toolName,
        public string $elicitationType,
        public array $fields = [],
        ?string $agentId = null,
        ?string $agentType = null,
    ) {
        parent::__construct($sessionId, $transcriptPath, $cwd, $permissionMode, 'Elicitation', $agentId, $agentType);
    }

    /**
     * @param array<array-key, mixed> $payload
     */
    public static function fromPayload(array $payload): self
    {
        $fields = [];
        if (array_key_exists('fields', $payload) && is_array($payload['fields'])) {
            foreach ($payload['fields'] as $entry) {
                if (is_array($entry)) {
                    /** @var array<string, mixed> $entry */
                    $fields[] = $entry;
                }
            }
        }

        return new self(
            sessionId: self::requireString($payload, 'session_id', 'Elicitation'),
            transcriptPath: self::requireString($payload, 'transcript_path', 'Elicitation'),
            cwd: self::requireString($payload, 'cwd', 'Elicitation'),
            permissionMode: PermissionMode::fromString(self::requireString($payload, 'permission_mode', 'Elicitation')),
            mcpServer: self::requireString($payload, 'mcp_server', 'Elicitation'),
            toolName: self::requireString($payload, 'tool_name', 'Elicitation'),
            elicitationType: self::requireString($payload, 'elicitation_type', 'Elicitation'),
            fields: $fields,
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
