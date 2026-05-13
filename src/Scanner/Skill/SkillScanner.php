<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Scanner\Skill;

use function assert;

use const DIRECTORY_SEPARATOR;

use FilesystemIterator;
use SplFileInfo;

/**
 * SEC-01: every candidate skill directory is normalized via realpath and must
 * sit inside one of the configured roots; symlinks are skipped.
 */
final readonly class SkillScanner
{
    public function __construct(private SkillFrontmatterParser $parser) {}

    /**
     * @param list<string> $roots
     *
     * @return list<Skill>
     */
    public function scan(array $roots): array
    {
        $out = [];

        foreach ($roots as $root) {
            $rootReal = realpath($root);
            if ($rootReal === false || !is_dir($rootReal)) {
                continue;
            }

            $iter = new FilesystemIterator($rootReal, FilesystemIterator::SKIP_DOTS);
            foreach ($iter as $skillDir) {
                assert($skillDir instanceof SplFileInfo);
                if ($skillDir->isLink() || !$skillDir->isDir()) {
                    continue;
                }

                $candidate = $skillDir->getPathname() . DIRECTORY_SEPARATOR . 'SKILL.md';
                $real = realpath($candidate);

                if ($real === false) {
                    // Either no SKILL.md inside this directory or it resolves
                    // outside the root via symlink — silently skip.
                    continue;
                }

                $out[] = $this->parser->parse($real);
            }
        }

        return $out;
    }
}
