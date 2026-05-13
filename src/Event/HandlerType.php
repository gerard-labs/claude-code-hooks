<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Event;

enum HandlerType: string
{
    case Command = 'command';
    case Http = 'http';
    case McpTool = 'mcp_tool';
    case Prompt = 'prompt';
    case Agent = 'agent';
}
