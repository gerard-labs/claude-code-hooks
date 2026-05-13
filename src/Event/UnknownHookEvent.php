<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Event;

use Override;

/**
 * Forward-compatibility shim (ADR-0007). Returned by `HookEventFactory` when
 * the `hook_event_name` is not in the documented set — the calling code can
 * still inspect the common fields and decide whether to drop, log, or queue
 * the event for a later upgrade.
 */
final readonly class UnknownHookEvent extends AbstractHookEvent
{
    /**
     * @param array<string, mixed> $rawPayload
     */
    public function __construct(
        string $sessionId,
        string $transcriptPath,
        string $cwd,
        PermissionMode $permissionMode,
        string $hookEventName,
        public array $rawPayload,
        ?string $agentId = null,
        ?string $agentType = null,
    ) {
        parent::__construct(
            $sessionId,
            $transcriptPath,
            $cwd,
            $permissionMode,
            $hookEventName,
            $agentId,
            $agentType,
        );
    }

    #[Override]
    public function family(): Family
    {
        return Family::Unknown;
    }
}
