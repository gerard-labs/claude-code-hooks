<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Scanner\Agent;

use function assert;

use FilesystemIterator;
use SplFileInfo;

final readonly class AgentScanner
{
    public function __construct(private AgentFrontmatterParser $parser) {}

    /**
     * @param list<string> $roots
     *
     * @return list<Agent>
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
            foreach ($iter as $entry) {
                assert($entry instanceof SplFileInfo);
                if ($entry->isLink() || !$entry->isFile() || $entry->getExtension() !== 'md') {
                    continue;
                }

                $real = realpath($entry->getPathname());
                $out[] = $this->parser->parse($real === false ? $entry->getPathname() : $real);
            }
        }

        return $out;
    }
}
