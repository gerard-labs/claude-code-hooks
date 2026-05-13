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

final readonly class InstructionsLoadedEvent extends AbstractHookEvent
{
    /**
     * @param list<string> $globs
     */
    public function __construct(
        string $sessionId,
        string $transcriptPath,
        string $cwd,
        PermissionMode $permissionMode,
        public string $filePath,
        public string $memoryType,
        public string $loadReason,
        public array $globs = [],
        public ?string $triggerFilePath = null,
        public ?string $parentFilePath = null,
        ?string $agentId = null,
        ?string $agentType = null,
    ) {
        parent::__construct($sessionId, $transcriptPath, $cwd, $permissionMode, 'InstructionsLoaded', $agentId, $agentType);
    }

    /**
     * @param array<array-key, mixed> $payload
     */
    public static function fromPayload(array $payload): self
    {
        $globs = [];
        if (array_key_exists('globs', $payload) && is_array($payload['globs'])) {
            foreach ($payload['globs'] as $glob) {
                if (is_string($glob)) {
                    $globs[] = $glob;
                }
            }
        }

        return new self(
            sessionId: self::requireString($payload, 'session_id', 'InstructionsLoaded'),
            transcriptPath: self::requireString($payload, 'transcript_path', 'InstructionsLoaded'),
            cwd: self::requireString($payload, 'cwd', 'InstructionsLoaded'),
            permissionMode: PermissionMode::fromString(self::requireString($payload, 'permission_mode', 'InstructionsLoaded')),
            filePath: self::requireString($payload, 'file_path', 'InstructionsLoaded'),
            memoryType: self::requireString($payload, 'memory_type', 'InstructionsLoaded'),
            loadReason: self::requireString($payload, 'load_reason', 'InstructionsLoaded'),
            globs: $globs,
            triggerFilePath: self::optionalString($payload, 'trigger_file_path'),
            parentFilePath: self::optionalString($payload, 'parent_file_path'),
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
