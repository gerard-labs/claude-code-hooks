<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Event;

enum ToolName: string
{
    case Bash = 'Bash';
    case Edit = 'Edit';
    case Write = 'Write';
    case Read = 'Read';
    case Glob = 'Glob';
    case Grep = 'Grep';
    case WebFetch = 'WebFetch';
    case WebSearch = 'WebSearch';
    case Agent = 'Agent';
    case AskUserQuestion = 'AskUserQuestion';
    case ExitPlanMode = 'ExitPlanMode';
    case TodoWrite = 'TodoWrite';
    case Skill = 'Skill';

    /**
     * Returns true for the `mcp__<server>__<tool>` family — those tool names
     * are dynamically defined by MCP servers and are NOT enum cases.
     */
    public static function isMcpToolName(string $name): bool
    {
        return str_starts_with($name, 'mcp__');
    }
}
