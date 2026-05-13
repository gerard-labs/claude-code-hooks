<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Tests\Integration\Scanner;

use DirectoryIterator;
use Gerard\ClaudeCodeHooks\Exception\InvalidPayloadException;
use Gerard\ClaudeCodeHooks\Scanner\Plugin\InstalledPluginsReader;
use Gerard\ClaudeCodeHooks\Scanner\Plugin\Plugin;
use Gerard\ClaudeCodeHooks\Scanner\Plugin\PluginScanner;

use const JSON_THROW_ON_ERROR;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(InstalledPluginsReader::class)]
#[CoversClass(PluginScanner::class)]
#[CoversClass(Plugin::class)]
final class PluginScannerTest extends TestCase
{
    private string $tmpRoot;

    protected function setUp(): void
    {
        $this->tmpRoot = sys_get_temp_dir() . '/cch-plugin-' . bin2hex(random_bytes(6));
        mkdir($this->tmpRoot, 0o755, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tmpRoot)) {
            $this->recursiveRemove($this->tmpRoot);
        }
    }

    public function testItCountsPluginAssets(): void
    {
        $installPath = $this->tmpRoot . '/plg1';
        mkdir($installPath . '/hooks', 0o755, true);
        touch($installPath . '/hooks/h1.sh');
        mkdir($installPath . '/skills/s1', 0o755, true);
        mkdir($installPath . '/commands', 0o755, true);
        touch($installPath . '/commands/c1.md');
        mkdir($installPath . '/agents', 0o755, true);
        touch($installPath . '/agents/a1.md');

        $jsonPath = $this->tmpRoot . '/installed_plugins.json';
        file_put_contents($jsonPath, json_encode([
            'version' => 2,
            'plugins' => ['plg1@m' => [['scope' => 'project', 'installPath' => $installPath, 'version' => '1.0.0']]],
        ], JSON_THROW_ON_ERROR));

        $reader = new InstalledPluginsReader();
        $scanner = new PluginScanner($reader, $this->tmpRoot);
        $plugins = $scanner->scan($jsonPath);
        self::assertCount(1, $plugins);
        self::assertSame(1, $plugins[0]->hooksCount);
        self::assertSame(1, $plugins[0]->skillsCount);
        self::assertSame(1, $plugins[0]->commandsCount);
        self::assertSame(1, $plugins[0]->agentsCount);
    }

    public function testItReturnsPluginWithZeroHooks(): void
    {
        $installPath = $this->tmpRoot . '/plg2';
        mkdir($installPath, 0o755, true);

        $jsonPath = $this->tmpRoot . '/installed_plugins.json';
        file_put_contents($jsonPath, json_encode([
            'version' => 2,
            'plugins' => ['plg2@m' => [['scope' => 'project', 'installPath' => $installPath, 'version' => '1.0.0']]],
        ], JSON_THROW_ON_ERROR));

        $plugins = (new PluginScanner(new InstalledPluginsReader(), $this->tmpRoot))->scan($jsonPath);
        self::assertSame(0, $plugins[0]->hooksCount);
    }

    public function testItRejectsInstallPathTraversal(): void
    {
        $installPath = '/etc';
        $jsonPath = $this->tmpRoot . '/installed_plugins.json';
        file_put_contents($jsonPath, json_encode([
            'version' => 2,
            'plugins' => ['evil@m' => [['scope' => 'project', 'installPath' => $installPath, 'version' => '1.0.0']]],
        ], JSON_THROW_ON_ERROR));

        self::expectException(InvalidPayloadException::class);
        (new PluginScanner(new InstalledPluginsReader(), $this->tmpRoot))->scan($jsonPath);
    }

    public function testItSkipsSymlinks(): void
    {
        $installPath = $this->tmpRoot . '/plg3';
        mkdir($installPath . '/hooks', 0o755, true);
        touch($installPath . '/hooks/real.sh');
        // Create a symlink — should be skipped, only real.sh counted.
        symlink('/etc', $installPath . '/hooks/evil_link');

        $jsonPath = $this->tmpRoot . '/installed_plugins.json';
        file_put_contents($jsonPath, json_encode([
            'version' => 2,
            'plugins' => ['plg3@m' => [['scope' => 'project', 'installPath' => $installPath, 'version' => '1.0.0']]],
        ], JSON_THROW_ON_ERROR));

        $plugins = (new PluginScanner(new InstalledPluginsReader(), $this->tmpRoot))->scan($jsonPath);
        self::assertSame(1, $plugins[0]->hooksCount);
    }

    public function testReaderReturnsEmptyArrayForMissingFile(): void
    {
        self::assertSame([], (new InstalledPluginsReader())->read($this->tmpRoot . '/missing.json'));
    }

    public function testReaderRejectsMissingPluginsKey(): void
    {
        $jsonPath = $this->tmpRoot . '/no_plugins.json';
        file_put_contents($jsonPath, '{"version":2}');
        self::expectException(InvalidPayloadException::class);
        (new InstalledPluginsReader())->read($jsonPath);
    }

    public function testScannerReturnsEmptyForUnresolvableRoot(): void
    {
        $scanner = new PluginScanner(new InstalledPluginsReader(), '/non-existent-root-12345');
        self::assertSame([], $scanner->scan('/missing.json'));
    }

    private function recursiveRemove(string $path): void
    {
        if (is_link($path) || is_file($path)) {
            unlink($path);

            return;
        }
        foreach (new DirectoryIterator($path) as $entry) {
            if ($entry->isDot()) {
                continue;
            }
            $this->recursiveRemove($entry->getPathname());
        }
        rmdir($path);
    }
}
