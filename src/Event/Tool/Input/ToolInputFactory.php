<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Event\Tool\Input;

use Gerard\ClaudeCodeHooks\Event\ToolName;

/**
 * Dispatches a raw `tool_input` payload to the matching typed input DTO.
 *
 * Unknown tool names — including `mcp__<server>__<tool>` invocations and any
 * first-party tool not yet modelled by a dedicated DTO — fall through to
 * `RawToolInput` so the public API stays total. Exotic tool names should
 * still be observable, never crash.
 */
final class ToolInputFactory
{
    /**
     * @param array<array-key, mixed> $rawInput
     */
    public static function fromArray(string $toolName, array $rawInput): ToolInput
    {
        $enum = ToolName::tryFrom($toolName);

        if ($enum === null) {
            return RawToolInput::fromArray($toolName, $rawInput);
        }

        return match ($enum) {
            ToolName::Bash => BashInput::fromArray($rawInput),
            ToolName::Edit => EditInput::fromArray($rawInput),
            ToolName::Write => WriteInput::fromArray($rawInput),
            ToolName::Read => ReadInput::fromArray($rawInput),
            ToolName::Glob => GlobInput::fromArray($rawInput),
            ToolName::Grep => GrepInput::fromArray($rawInput),
            ToolName::WebFetch => WebFetchInput::fromArray($rawInput),
            ToolName::WebSearch => WebSearchInput::fromArray($rawInput),
            ToolName::Agent => AgentInput::fromArray($rawInput),
            ToolName::AskUserQuestion => AskUserQuestionInput::fromArray($rawInput),
            ToolName::TodoWrite => TodoWriteInput::fromArray($rawInput),
            ToolName::Skill => SkillInput::fromArray($rawInput),
            ToolName::ExitPlanMode => RawToolInput::fromArray('ExitPlanMode', $rawInput),
        };
    }
}
