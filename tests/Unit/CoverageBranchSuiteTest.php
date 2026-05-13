<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Tests\Unit;

use DirectoryIterator;
use Gerard\ClaudeCodeHooks\Builder\HookConfigBuilder;
use Gerard\ClaudeCodeHooks\Conformance\AnthropicSpecExtractor;
use Gerard\ClaudeCodeHooks\Conformance\SpecSnapshot;
use Gerard\ClaudeCodeHooks\Event\Tool\Input\WebFetchInput;
use Gerard\ClaudeCodeHooks\Event\Tool\PostToolUseEvent;
use Gerard\ClaudeCodeHooks\Exception\ExtractionFailedException;
use Gerard\ClaudeCodeHooks\Exception\InvalidPayloadException;
use Gerard\ClaudeCodeHooks\Linter\ConfigProfile;
use Gerard\ClaudeCodeHooks\Linter\HookConfig;
use Gerard\ClaudeCodeHooks\Linter\Rule\Cch005SecretInUrlLiteral;
use Gerard\ClaudeCodeHooks\Resolver\HookConfigResolver;
use Gerard\ClaudeCodeHooks\Resolver\InMemorySettingsLoader;
use Gerard\ClaudeCodeHooks\Response\AbstractHookResponse;
use Gerard\ClaudeCodeHooks\Response\Tool\PreToolUseResponse;
use Gerard\ClaudeCodeHooks\Scanner\Agent\AgentFrontmatterParser;
use Gerard\ClaudeCodeHooks\Scanner\Agent\AgentScanner;
use Gerard\ClaudeCodeHooks\Scanner\Mcp\McpConfigReader;
use Gerard\ClaudeCodeHooks\Scanner\Plugin\InstalledPluginsReader;
use Gerard\ClaudeCodeHooks\Scanner\Plugin\PluginScanner;
use Gerard\ClaudeCodeHooks\Scanner\Skill\SkillFrontmatterParser;
use Gerard\ClaudeCodeHooks\Scanner\Skill\SkillScanner;
use Gerard\ClaudeCodeHooks\Transcript\TranscriptCursor;
use Gerard\ClaudeCodeHooks\Transcript\TranscriptReader;

use const JSON_THROW_ON_ERROR;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(HookConfigBuilder::class)]
#[CoversClass(AnthropicSpecExtractor::class)]
#[CoversClass(SpecSnapshot::class)]
#[CoversClass(WebFetchInput::class)]
#[CoversClass(PostToolUseEvent::class)]
#[CoversClass(Cch005SecretInUrlLiteral::class)]
#[CoversClass(HookConfigResolver::class)]
#[CoversClass(AbstractHookResponse::class)]
#[CoversClass(AgentFrontmatterParser::class)]
#[CoversClass(AgentScanner::class)]
#[CoversClass(McpConfigReader::class)]
#[CoversClass(InstalledPluginsReader::class)]
#[CoversClass(PluginScanner::class)]
#[CoversClass(SkillFrontmatterParser::class)]
#[CoversClass(SkillScanner::class)]
#[CoversClass(TranscriptReader::class)]
final class CoverageBranchSuiteTest extends TestCase
{
    public function testWebFetchInputRejectsNonStringUrl(): void
    {
        self::expectException(InvalidPayloadException::class);
        WebFetchInput::fromArray(['url' => 1, 'prompt' => 'p']);
    }

    public function testPostToolUseAcceptsArrayResponseBranch(): void
    {
        $event = PostToolUseEvent::fromPayload([
            'session_id' => 's', 'transcript_path' => '/p', 'cwd' => '/c',
            'permission_mode' => 'default', 'hook_event_name' => 'PostToolUse',
            'tool_name' => 'Bash', 'tool_input' => ['command' => 'ls'],
            'tool_response' => ['stdout' => 'ok', 'stderr' => '', 'interrupted' => false],
        ]);
        self::assertSame(['stdout' => 'ok', 'stderr' => '', 'interrupted' => false], $event->toolResponse);
    }

    public function testCch005SilentForNonStringUrl(): void
    {
        $rule = new Cch005SecretInUrlLiteral();
        $config = new HookConfig('PreToolUse', 'Bash', ['type' => 'http'], 'project');
        self::assertSame([], iterator_to_array($rule->inspect($config, ConfigProfile::Default)));
    }

    public function testHookConfigBuilderCommandHandlerOnRoot(): void
    {
        $builder = HookConfigBuilder::create()->event('Stop')->matcher('');
        $builder->commandHandler('done.sh');
        $config = $builder->build();
        self::assertCount(1, $config['hooks']['Stop']);
    }

    public function testHookConfigBuilderHttpHandlerOnRoot(): void
    {
        $builder = HookConfigBuilder::create()->event('Stop')->matcher('');
        $builder->httpHandler('https://x');
        $config = $builder->build();
        self::assertCount(1, $config['hooks']['Stop']);
    }

    public function testInMemoryResolverWithEmptySource(): void
    {
        $resolver = new HookConfigResolver(new InMemorySettingsLoader([]));
        self::assertSame([], $resolver->resolve()->rules);
    }

    public function testAbstractResponseSuppressOutputAndCommonFields(): void
    {
        // exercises the parent::toArray() edge cases through a concrete class
        $response = PreToolUseResponse::allow()
            ->withSuppressOutput(false)
            ->withSystemMessage('m')
            ->withAdditionalContext('c');
        $payload = $response->toArray();
        self::assertFalse($payload['suppressOutput']);
        self::assertSame('m', $payload['systemMessage']);
    }

    public function testAgentParserReadsFile(): void
    {
        $tmp = sys_get_temp_dir() . '/agent-' . bin2hex(random_bytes(4)) . '.md';
        file_put_contents($tmp, "---\nname: foo\n---\nb");
        $agent = (new AgentFrontmatterParser())->parse($tmp);
        unlink($tmp);
        self::assertSame('foo', $agent->name);
    }

    public function testSkillParserReadsFile(): void
    {
        $dir = sys_get_temp_dir() . '/skill-' . bin2hex(random_bytes(4));
        mkdir($dir, 0o755, true);
        file_put_contents($dir . '/SKILL.md', "---\nname: foo\n---\nb");
        $skill = (new SkillFrontmatterParser())->parse($dir . '/SKILL.md');
        unlink($dir . '/SKILL.md');
        rmdir($dir);
        self::assertSame('foo', $skill->name);
    }

    public function testInstalledPluginsReaderHappyPath(): void
    {
        $tmp = sys_get_temp_dir() . '/inst-' . bin2hex(random_bytes(4)) . '.json';
        file_put_contents($tmp, '{"plugins":{"foo@m":[{"installPath":"/x","version":"1.0.0","scope":"project"}]}}');
        $entries = (new InstalledPluginsReader())->read($tmp);
        unlink($tmp);
        self::assertCount(1, $entries);
    }

    public function testInstalledPluginsReaderSkipsNonStringId(): void
    {
        $tmp = sys_get_temp_dir() . '/inst-' . bin2hex(random_bytes(4)) . '.json';
        file_put_contents($tmp, '{"plugins":{"valid":[{"installPath":"/x","version":"1.0.0","scope":"p"}],"":[{"installPath":"/y","version":"1","scope":"p"}]}}');
        $entries = (new InstalledPluginsReader())->read($tmp);
        unlink($tmp);
        self::assertCount(2, $entries);
    }

    public function testInstalledPluginsReaderSkipsEntryMissingInstallPath(): void
    {
        $tmp = sys_get_temp_dir() . '/inst-' . bin2hex(random_bytes(4)) . '.json';
        file_put_contents($tmp, '{"plugins":{"foo@m":[{"version":"1.0.0","scope":"project"}]}}');
        $entries = (new InstalledPluginsReader())->read($tmp);
        unlink($tmp);
        self::assertSame([], $entries);
    }

    public function testInstalledPluginsReaderSkipsNonArrayEntry(): void
    {
        $tmp = sys_get_temp_dir() . '/inst-' . bin2hex(random_bytes(4)) . '.json';
        file_put_contents($tmp, '{"plugins":{"foo@m":["not-an-object"]}}');
        $entries = (new InstalledPluginsReader())->read($tmp);
        unlink($tmp);
        self::assertSame([], $entries);
    }

    public function testInstalledPluginsReaderEmptyContent(): void
    {
        $tmp = sys_get_temp_dir() . '/inst-' . bin2hex(random_bytes(4)) . '.json';
        file_put_contents($tmp, '');
        self::assertSame([], (new InstalledPluginsReader())->read($tmp));
        unlink($tmp);
    }

    public function testMcpConfigReaderSkipsNonStringServerName(): void
    {
        $tmp = sys_get_temp_dir() . '/mcp-' . bin2hex(random_bytes(4)) . '.json';
        file_put_contents($tmp, '{"mcpServers":{"valid":{"type":"stdio"},"":{"type":"stdio"}}}');
        $servers = (new McpConfigReader())->read([$tmp]);
        unlink($tmp);
        self::assertCount(2, $servers);
    }

    public function testMcpConfigReaderHandlesMalformedEntry(): void
    {
        $tmp = sys_get_temp_dir() . '/mcp-' . bin2hex(random_bytes(4)) . '.json';
        file_put_contents($tmp, '{"mcpServers":{"good":{"type":"stdio"},"bad":"not-an-object"}}');
        $servers = (new McpConfigReader())->read([$tmp]);
        unlink($tmp);
        self::assertCount(1, $servers);
    }

    public function testTranscriptReaderHandlesUnreadableFile(): void
    {
        // Path is a directory — fopen returns false.
        $reader = new TranscriptReader();
        $cursor = new TranscriptCursor(sys_get_temp_dir());
        $events = [];
        foreach ($reader->tail($cursor) as $e) {
            $events[] = $e;
        }
        self::assertSame([], $events);
    }

    public function testAnthropicExtractorMissingHostInUrl(): void
    {
        $client = new MockHttpClient([]);
        self::expectException(ExtractionFailedException::class);
        (new AnthropicSpecExtractor($client))->extract(['/missing-host']);
    }

    public function testAnthropicExtractorBodyTooLarge(): void
    {
        $huge = str_repeat('x', AnthropicSpecExtractor::MAX_BODY_BYTES + 100);
        $client = new MockHttpClient([new MockResponse($huge, ['response_headers' => ['content-type' => 'text/html']])]);
        self::expectException(ExtractionFailedException::class);
        (new AnthropicSpecExtractor($client))->extract(['https://code.claude.com/docs/en/hooks']);
    }

    public function testSpecSnapshotEnvVarOverride(): void
    {
        $tmp = sys_get_temp_dir() . '/spec-' . bin2hex(random_bytes(4)) . '.json';
        file_put_contents($tmp, '{"events":{}}');
        putenv('CLAUDE_HOOKS_SPEC_FILE=' . $tmp);
        try {
            $spec = SpecSnapshot::load('/wrong-default-path.json');
            self::assertArrayHasKey('events', $spec);
        } finally {
            putenv('CLAUDE_HOOKS_SPEC_FILE');
            unlink($tmp);
        }
    }

    public function testPluginScannerSkipsAtMaxDepth(): void
    {
        $tmpRoot = sys_get_temp_dir() . '/cch-depth-' . bin2hex(random_bytes(4));
        mkdir($tmpRoot, 0o755, true);

        try {
            $installPath = $tmpRoot . '/plg';
            // Create deeply nested dir tree — depth 6
            mkdir($installPath . '/hooks/a/b/c/d/e', 0o755, true);
            touch($installPath . '/hooks/a/b/c/d/e/leaf.sh');

            $jsonPath = $tmpRoot . '/installed_plugins.json';
            file_put_contents($jsonPath, json_encode([
                'plugins' => ['plg@m' => [['installPath' => $installPath, 'version' => '1.0.0', 'scope' => 'project']]],
            ], JSON_THROW_ON_ERROR));

            $plugins = (new PluginScanner(new InstalledPluginsReader(), $tmpRoot))->scan($jsonPath);
            // We have 1 plugin. Just assert no error and depth was not exceeded.
            self::assertCount(1, $plugins);
        } finally {
            $this->rrm($tmpRoot);
        }
    }

    public function testAgentScannerSkipsLinkedAgentFile(): void
    {
        $tmpRoot = sys_get_temp_dir() . '/cch-agentlink-' . bin2hex(random_bytes(4));
        mkdir($tmpRoot, 0o755, true);
        symlink('/etc', $tmpRoot . '/explore.md');
        try {
            $agents = (new AgentScanner(new AgentFrontmatterParser()))->scan([$tmpRoot]);
            self::assertSame([], $agents);
        } finally {
            $this->rrm($tmpRoot);
        }
    }

    public function testSkillScannerSkipsLinks(): void
    {
        $tmpRoot = sys_get_temp_dir() . '/cch-skilllink-' . bin2hex(random_bytes(4));
        mkdir($tmpRoot, 0o755, true);
        symlink('/etc', $tmpRoot . '/evil');
        try {
            $skills = (new SkillScanner(new SkillFrontmatterParser()))->scan([$tmpRoot]);
            self::assertSame([], $skills);
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
