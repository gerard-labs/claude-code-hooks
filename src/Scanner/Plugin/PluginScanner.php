<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Scanner\Plugin;

use function assert;

use const DIRECTORY_SEPARATOR;

use FilesystemIterator;
use Gerard\ClaudeCodeHooks\Exception\InvalidPayloadException;
use SplFileInfo;

use function sprintf;

/**
 * Scans the on-disk install directories of every plugin listed in
 * `installed_plugins.json` and emits a {@see Plugin} VO with component counts.
 *
 * SEC-01: every install path is checked with {@see realpath} against the
 * configured `$pluginRoot` prefix; symlinks under the install path are skipped
 * silently. Counting is one level deep (a plugin component's first-level
 * entries — files or subdirectories — are counted; nested files are not).
 */
final readonly class PluginScanner
{
    public function __construct(
        private InstalledPluginsReader $reader,
        private string $pluginRoot,
    ) {}

    /**
     * @return list<Plugin>
     */
    public function scan(string $installedPluginsJson): array
    {
        $rootReal = realpath($this->pluginRoot);

        if ($rootReal === false) {
            return [];
        }

        $entries = $this->reader->read($installedPluginsJson);
        $out = [];

        foreach ($entries as $entry) {
            $real = realpath($entry['installPath']);

            if ($real === false || !str_starts_with($real, $rootReal . DIRECTORY_SEPARATOR) && $real !== $rootReal) {
                throw InvalidPayloadException::unsupportedValue(
                    $installedPluginsJson,
                    sprintf('$.plugins["%s"].installPath', $entry['id']),
                    'a path inside the configured plugin root',
                );
            }

            $out[] = new Plugin(
                name: $entry['id'],
                installPath: $real,
                version: $entry['version'],
                hooksCount: $this->countComponent($real, 'hooks'),
                skillsCount: $this->countComponent($real, 'skills'),
                commandsCount: $this->countComponent($real, 'commands'),
                agentsCount: $this->countComponent($real, 'agents'),
                gitCommitSha: $entry['gitCommitSha'],
            );
        }

        return $out;
    }

    private function countComponent(string $pluginRoot, string $component): int
    {
        $dir = $pluginRoot . DIRECTORY_SEPARATOR . $component;

        if (!is_dir($dir)) {
            return 0;
        }

        return $this->countDirEntries($dir);
    }

    private function countDirEntries(string $dir): int
    {
        $count = 0;
        $iter = new FilesystemIterator($dir, FilesystemIterator::SKIP_DOTS);

        foreach ($iter as $entry) {
            assert($entry instanceof SplFileInfo);

            if ($entry->isLink()) {
                continue;
            }
            if ($entry->isDir() || $entry->isFile()) {
                ++$count;
            }
        }

        return $count;
    }
}
