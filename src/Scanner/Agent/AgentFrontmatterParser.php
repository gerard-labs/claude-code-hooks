<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Scanner\Agent;

use Gerard\ClaudeCodeHooks\Exception\InvalidPayloadException;
use Gerard\ClaudeCodeHooks\Support\FrontmatterDecoder;

use function is_string;

use const PATHINFO_FILENAME;

use function sprintf;

final readonly class AgentFrontmatterParser
{
    public function parse(string $agentMdPath): Agent
    {
        if (!is_file($agentMdPath)) {
            throw new InvalidPayloadException(sprintf('Cannot read agent file %s', $agentMdPath));
        }
        $contents = (string) file_get_contents($agentMdPath);

        $split = FrontmatterDecoder::split($contents, $agentMdPath);
        $front = $split['frontmatter'];

        $name = is_string($front['name'] ?? null)
            ? $front['name']
            : pathinfo($agentMdPath, PATHINFO_FILENAME);
        $description = is_string($front['description'] ?? null) ? $front['description'] : '';

        return new Agent($name, $agentMdPath, $description);
    }
}
