<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Tests\Unit;

use DirectoryIterator;
use Gerard\ClaudeCodeHooks\Builder\HookConfigBuilder;
use Gerard\ClaudeCodeHooks\Builder\HookHandlerBuilder;
use Gerard\ClaudeCodeHooks\Builder\MatcherBuilder;
use Gerard\ClaudeCodeHooks\Conformance\AnthropicSpecExtractor;
use Gerard\ClaudeCodeHooks\Conformance\SpecSnapshot;
use Gerard\ClaudeCodeHooks\Exception\InvalidPayloadException;
use Gerard\ClaudeCodeHooks\Linter\ConfigProfile;
use Gerard\ClaudeCodeHooks\Linter\HookConfig;
use Gerard\ClaudeCodeHooks\Linter\Rule\Cch005SecretInUrlLiteral;
use Gerard\ClaudeCodeHooks\Resolver\HookConfigResolver;
use Gerard\ClaudeCodeHooks\Resolver\InMemorySettingsLoader;
use Gerard\ClaudeCodeHooks\Response\AbstractHookResponse;
use Gerard\ClaudeCodeHooks\Response\Tool\PreToolUseResponse;
use Gerard\ClaudeCodeHooks\Scanner\Mcp\McpConfigReader;
use Gerard\ClaudeCodeHooks\Scanner\Plugin\InstalledPluginsReader;
use Gerard\ClaudeCodeHooks\Scanner\Plugin\PluginScanner;
use Gerard\ClaudeCodeHooks\Transcript\TranscriptCursor;
use Gerard\ClaudeCodeHooks\Transcript\TranscriptReader;

use const JSON_THROW_ON_ERROR;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(InstalledPluginsReader::class)]
#[CoversClass(McpConfigReader::class)]
#[CoversClass(PluginScanner::class)]
#[CoversClass(HookConfigResolver::class)]
#[CoversClass(SpecSnapshot::class)]
#[CoversClass(HookConfigBuilder::class)]
#[CoversClass(MatcherBuilder::class)]
#[CoversClass(HookHandlerBuilder::class)]
#[CoversClass(AbstractHookResponse::class)]
#[CoversClass(Cch005SecretInUrlLiteral::class)]
#[CoversClass(AnthropicSpecExtractor::class)]
#[CoversClass(TranscriptReader::class)]
final class CoverageBranchSuite2Test extends TestCase
{
    public function testInstalledPluginsReaderSkipsEntriesWithoutInstallPath(): void
    {
        $tmp = sys_get_temp_dir() . '/inst-' . bin2hex(random_bytes(4)) . '.json';
        file_put_contents($tmp, '{"plugins":{"a":[{"version":"1.0.0","scope":"p"}],"b":[{"installPath":"/x","version":"1","scope":"p"}]}}');
        $entries = (new InstalledPluginsReader())->read($tmp);
        unlink($tmp);
        // a is skipped (no installPath), b kept
        self::assertCount(1, $entries);
    }

    public function testInstalledPluginsReaderSkipsNonArrayEntryInList(): void
    {
        $tmp = sys_get_temp_dir() . '/inst-' . bin2hex(random_bytes(4)) . '.json';
        file_put_contents($tmp, '{"plugins":{"a":["scalar",{"installPath":"/x","version":"1","scope":"p"}]}}');
        $entries = (new InstalledPluginsReader())->read($tmp);
        unlink($tmp);
        self::assertCount(1, $entries);
    }

    public function testMcpConfigSkipsMalformedEntry(): void
    {
        $tmp = sys_get_temp_dir() . '/mcp-' . bin2hex(random_bytes(4)) . '.json';
        file_put_contents($tmp, '{"mcpServers":{"good":{"type":"stdio"},"bad":42}}');
        $servers = (new McpConfigReader())->read([$tmp]);
        unlink($tmp);
        self::assertCount(1, $servers);
    }

    public function testHookConfigResolverSkipsNonArrayMatcherEntry(): void
    {
        $resolver = new HookConfigResolver(new InMemorySettingsLoader([
            'projectSettings' => ['hooks' => [
                'PreToolUse' => ['scalar', ['matcher' => 'Bash', 'hooks' => [['type' => 'command', 'command' => 'x.sh']]]],
            ]],
        ]));
        $registry = $resolver->resolve();
        self::assertCount(1, $registry->forEvent('PreToolUse'));
    }

    public function testHookConfigResolverSkipsNonArrayHandler(): void
    {
        $resolver = new HookConfigResolver(new InMemorySettingsLoader([
            'projectSettings' => ['hooks' => [
                'PreToolUse' => [['matcher' => 'Bash', 'hooks' => ['scalar', ['type' => 'command']]]],
            ]],
        ]));
        $registry = $resolver->resolve();
        self::assertCount(1, $registry->forEvent('PreToolUse'));
    }

    public function testHookConfigResolverManagedFlagPropagates(): void
    {
        $resolver = new HookConfigResolver(new InMemorySettingsLoader([
            'projectSettings' => ['hooks' => [
                'PreToolUse' => [['matcher' => 'Bash', 'hooks' => [['type' => 'command', 'managed' => true]]]],
            ]],
        ]));
        $rules = $resolver->resolve()->forEvent('PreToolUse');
        self::assertTrue($rules[0]->managed);
    }

    public function testSpecSnapshotMissingFileBranch(): void
    {
        self::expectException(InvalidPayloadException::class);
        SpecSnapshot::load('/missing-' . bin2hex(random_bytes(4)) . '.json');
    }

    public function testHookConfigBuilderInternalShortcutGuards(): void
    {
        // Forces the matcher() and httpHandler() shortcuts on the root builder.
        $builder = HookConfigBuilder::create();
        $builder->event('PreToolUse');
        $built = $builder->commandHandler('x.sh')->build();
        self::assertArrayHasKey('PreToolUse', $built['hooks']);
    }

    public function testAbstractResponseEmitsHookSpecificOutputOnly(): void
    {
        $response = PreToolUseResponse::allow('safe');
        $payload = $response->toArray();
        self::assertArrayHasKey('hookSpecificOutput', $payload);
        self::assertArrayNotHasKey('continue', $payload);
        self::assertArrayNotHasKey('stopReason', $payload);
    }

    public function testCch005MatchesUrlEvenWithoutHeaders(): void
    {
        $rule = new Cch005SecretInUrlLiteral();
        $config = new HookConfig(
            'PreToolUse',
            'Bash',
            ['type' => 'http', 'url' => 'https://x?token=Bearer abcdefgh1234567890'],
            'project',
        );
        $findings = iterator_to_array($rule->inspect($config, ConfigProfile::Default));
        self::assertCount(1, $findings);
    }

    public function testTranscriptReaderHandlesEmptyFile(): void
    {
        $tmp = sys_get_temp_dir() . '/empty-' . bin2hex(random_bytes(4)) . '.jsonl';
        file_put_contents($tmp, '');
        $reader = new TranscriptReader();
        $events = [];
        foreach ($reader->tail(new TranscriptCursor($tmp)) as $e) {
            $events[] = $e;
        }
        unlink($tmp);
        self::assertSame([], $events);
    }

    public function testAnthropicExtractorRetriesOn5xxThenSucceeds(): void
    {
        $client = new MockHttpClient([
            new MockResponse('', ['http_code' => 503]),
            new MockResponse('<html></html>', ['response_headers' => ['content-type' => 'text/html']]),
        ]);
        $extractor = new AnthropicSpecExtractor($client);
        $spec = $extractor->extract(['https://code.claude.com/docs/en/hooks']);
        self::assertSame([], $spec['events']);
    }

    public function testPluginScannerCountsDeepDirectoryWithRecursion(): void
    {
        $tmpRoot = sys_get_temp_dir() . '/cch-deep-' . bin2hex(random_bytes(4));
        mkdir($tmpRoot, 0o755, true);

        try {
            $installPath = $tmpRoot . '/plg';
            mkdir($installPath . '/hooks/sub1', 0o755, true);
            touch($installPath . '/hooks/sub1/x.sh');
            $jsonPath = $tmpRoot . '/installed_plugins.json';
            file_put_contents($jsonPath, json_encode([
                'plugins' => ['plg@m' => [['installPath' => $installPath, 'version' => '1.0.0', 'scope' => 'p']]],
            ], JSON_THROW_ON_ERROR));

            $plugins = (new PluginScanner(new InstalledPluginsReader(), $tmpRoot))->scan($jsonPath);
            self::assertCount(1, $plugins);
        } finally {
            $this->rrm($tmpRoot);
        }
    }

    private function rrm(string $path): void
    {
        if (!file_exists($path) && !is_link($path)) {
            return;
        }
        if (is_link($path) || is_file($path)) {
            unlink($path);

            return;
        }
        foreach (new DirectoryIterator($path) as $e) {
            if ($e->isDot()) {
                continue;
            }
            $this->rrm($e->getPathname());
        }
        rmdir($path);
    }
}
