<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Scanner\Skill;

use function array_key_exists;
use function dirname;

use Gerard\ClaudeCodeHooks\Exception\InvalidPayloadException;
use Gerard\ClaudeCodeHooks\Support\FrontmatterDecoder;

use function is_array;
use function is_string;
use function sprintf;

final readonly class SkillFrontmatterParser
{
    public function parse(string $skillMdPath): Skill
    {
        if (!is_file($skillMdPath)) {
            throw new InvalidPayloadException(sprintf('Cannot read skill file %s', $skillMdPath));
        }
        $contents = (string) file_get_contents($skillMdPath);

        $split = FrontmatterDecoder::split($contents, $skillMdPath);
        $front = $split['frontmatter'];

        $name = is_string($front['name'] ?? null) ? $front['name'] : basename(dirname($skillMdPath));
        $description = is_string($front['description'] ?? null) ? $front['description'] : '';

        $triggers = [];
        if (array_key_exists('hooks', $front) && is_array($front['hooks'])) {
            foreach ($front['hooks'] as $entry) {
                if (is_string($entry)) {
                    $triggers[] = $entry;
                }
            }
        }

        return new Skill(
            name: $name,
            path: $skillMdPath,
            description: $description,
            triggerHooks: $triggers,
        );
    }
}
