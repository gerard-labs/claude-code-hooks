<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Event\Ctx;

use function array_key_exists;

use Gerard\ClaudeCodeHooks\Event\AbstractHookEvent;
use Gerard\ClaudeCodeHooks\Event\Family;
use Gerard\ClaudeCodeHooks\Event\PermissionMode;

use function is_array;
use function is_string;

use Override;

final readonly class ConfigChangeEvent extends AbstractHookEvent
{
    /**
     * @param list<string> $changedKeys
     */
    public function __construct(
        string $sessionId,
        string $transcriptPath,
        string $cwd,
        PermissionMode $permissionMode,
        public string $configSource,
        public array $changedKeys = [],
        ?string $agentId = null,
        ?string $agentType = null,
    ) {
        parent::__construct($sessionId, $transcriptPath, $cwd, $permissionMode, 'ConfigChange', $agentId, $agentType);
    }

    /**
     * @param array<array-key, mixed> $payload
     */
    public static function fromPayload(array $payload): self
    {
        $keys = [];
        if (array_key_exists('changed_keys', $payload) && is_array($payload['changed_keys'])) {
            foreach ($payload['changed_keys'] as $key) {
                if (is_string($key)) {
                    $keys[] = $key;
                }
            }
        }

        return new self(
            sessionId: self::requireString($payload, 'session_id', 'ConfigChange'),
            transcriptPath: self::requireString($payload, 'transcript_path', 'ConfigChange'),
            cwd: self::requireString($payload, 'cwd', 'ConfigChange'),
            permissionMode: PermissionMode::fromString(self::requireString($payload, 'permission_mode', 'ConfigChange')),
            configSource: self::requireString($payload, 'config_source', 'ConfigChange'),
            changedKeys: $keys,
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
