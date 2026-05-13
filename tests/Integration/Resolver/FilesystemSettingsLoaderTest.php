<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Tests\Integration\Resolver;

use Gerard\ClaudeCodeHooks\Resolver\FilesystemSettingsLoader;
use Gerard\ClaudeCodeHooks\Resolver\HookSource;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FilesystemSettingsLoader::class)]
final class FilesystemSettingsLoaderTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/cch-fs-' . bin2hex(random_bytes(6));
        mkdir($this->tmpDir, 0o755, true);
    }

    protected function tearDown(): void
    {
        $files = glob($this->tmpDir . '/*');
        foreach ($files === false ? [] : $files as $f) {
            unlink($f);
        }
        rmdir($this->tmpDir);
    }

    public function testItLoadsSettingsFromFilesystem(): void
    {
        $path = $this->tmpDir . '/policy.json';
        file_put_contents($path, '{"hooks":{"PreToolUse":[]}}');
        $loader = new FilesystemSettingsLoader([HookSource::Policy->value => $path]);
        $settings = $loader->load(HookSource::Policy);
        self::assertNotNull($settings);
        self::assertArrayHasKey('hooks', $settings);
    }

    public function testItReturnsNullForUnconfiguredSource(): void
    {
        $loader = new FilesystemSettingsLoader([]);
        self::assertNull($loader->load(HookSource::User));
    }

    public function testItReturnsNullForMissingFile(): void
    {
        $loader = new FilesystemSettingsLoader([HookSource::User->value => $this->tmpDir . '/missing.json']);
        self::assertNull($loader->load(HookSource::User));
    }

    public function testItReturnsNullForEmptyFile(): void
    {
        $path = $this->tmpDir . '/empty.json';
        file_put_contents($path, '');
        $loader = new FilesystemSettingsLoader([HookSource::User->value => $path]);
        self::assertNull($loader->load(HookSource::User));
    }
}
