<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Event\Tool\Input;

/**
 * Marker interface for the 11 typed tool inputs delivered by `PreToolUse` /
 * `PostToolUse` events.
 *
 * Concrete implementations are `readonly` DTOs created via their static
 * `fromArray()` factory.
 */
interface ToolInput
{
    public function toolName(): string;
}
