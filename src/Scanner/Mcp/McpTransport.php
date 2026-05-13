<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Scanner\Mcp;

enum McpTransport: string
{
    case Stdio = 'stdio';
    case Http = 'http';
    case Sse = 'sse';
}
