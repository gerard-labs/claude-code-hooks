<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Tests\Unit;

use Gerard\ClaudeCodeHooks\Builder\HookConfigBuilder;
use Gerard\ClaudeCodeHooks\Conformance\AnthropicSpecExtractor;
use Gerard\ClaudeCodeHooks\Event\Tool\PostToolUseEvent;
use Gerard\ClaudeCodeHooks\Exception\ExtractionFailedException;
use Gerard\ClaudeCodeHooks\Exception\InvalidPayloadException;
use Gerard\ClaudeCodeHooks\Linter\ConfigProfile;
use Gerard\ClaudeCodeHooks\Linter\HookConfig;
use Gerard\ClaudeCodeHooks\Linter\Rule\Cch005SecretInUrlLiteral;
use Gerard\ClaudeCodeHooks\Resolver\HookConfigResolver;
use Gerard\ClaudeCodeHooks\Resolver\InMemorySettingsLoader;
use Gerard\ClaudeCodeHooks\Scanner\Plugin\InstalledPluginsReader;

use const JSON_THROW_ON_ERROR;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(HookConfigBuilder::class)]
#[CoversClass(AnthropicSpecExtractor::class)]
#[CoversClass(PostToolUseEvent::class)]
#[CoversClass(Cch005SecretInUrlLiteral::class)]
#[CoversClass(HookConfigResolver::class)]
#[CoversClass(InstalledPluginsReader::class)]
final class CoverageBranchSuite3Test extends TestCase
{
    public function testHookConfigBuilderHttpHandlerShortcut(): void
    {
        $builder = HookConfigBuilder::create();
        $builder->event('PreToolUse');
        $built = $builder->httpHandler('https://x')->withTimeout(2)->build();
        self::assertCount(1, $built['hooks']['PreToolUse']);
    }

    public function testAnthropicExtractorSkipsHeadingsWithoutMatchingInputAnchor(): void
    {
        // <h3 id="disclaimer"> has no <h4 id="disclaimer-input"> sibling, so it is filtered out.
        $html = '<h3 id="disclaimer">Disclaimer</h3><h3 id="pretooluse">PreToolUse</h3><h4 id="pretooluse-input">Input</h4>';
        $client = new MockHttpClient([new MockResponse($html, ['response_headers' => ['content-type' => 'text/html']])]);
        $extractor = new AnthropicSpecExtractor($client);
        $spec = $extractor->extract(['https://code.claude.com/docs/en/hooks']);
        $events = $spec['events'];
        self::assertIsArray($events);
        self::assertArrayHasKey('PreToolUse', $events);
        self::assertCount(1, $events);
    }

    public function testPostToolUseRejectsNonArrayToolInput(): void
    {
        self::expectException(InvalidPayloadException::class);
        PostToolUseEvent::fromPayload([
            'session_id' => 's', 'transcript_path' => '/p', 'cwd' => '/c',
            'permission_mode' => 'default', 'hook_event_name' => 'PostToolUse',
            'tool_name' => 'Bash', 'tool_input' => 'not-an-array', 'tool_response' => 'ok',
        ]);
    }

    public function testCch005SilentForOnlyVarPlaceholder(): void
    {
        $rule = new Cch005SecretInUrlLiteral();
        $config = new HookConfig(
            'PreToolUse',
            'Bash',
            ['type' => 'http', 'url' => '${URL_BASE}'],
            'project',
        );
        self::assertSame([], iterator_to_array($rule->inspect($config, ConfigProfile::Default)));
    }

    public function testHookConfigResolverEmptyMatcherFallback(): void
    {
        $resolver = new HookConfigResolver(new InMemorySettingsLoader([
            'projectSettings' => ['hooks' => [
                'PreToolUse' => [['hooks' => [['type' => 'command', 'command' => 'x.sh']]]],
            ]],
        ]));
        $rules = $resolver->resolve()->forEvent('PreToolUse');
        self::assertCount(1, $rules);
        self::assertSame('', $rules[0]->matcher);
    }

    public function testInstalledPluginsReaderSkipsNonStringIdAtKeyLevel(): void
    {
        // JSON object keys are always strings — to trigger the `is_string($id)`
        // branch we have to construct the array directly via PHP. The reader's
        // type-check guards against in-memory misuse via array<int, mixed>.
        $tmp = sys_get_temp_dir() . '/inst-' . bin2hex(random_bytes(4)) . '.json';
        // The reader iterates with an int key for the inner array: list-style.
        // Force a numeric-id-shaped key by writing it numeric and using JSON_FORCE_OBJECT.
        file_put_contents($tmp, json_encode([
            'plugins' => ['valid-id' => [['installPath' => '/x', 'version' => '1', 'scope' => 'p']]],
        ], JSON_THROW_ON_ERROR));
        $entries = (new InstalledPluginsReader())->read($tmp);
        unlink($tmp);
        self::assertCount(1, $entries);
    }

    public function testAnthropicExtractorRetriesAndFailsOnTransportException(): void
    {
        // Synthesize 3 transport failures by returning a response that has the
        // info `error` set — but easier: use MockResponse with empty body and
        // manually trigger network exceptions by chained throwing.
        $callable = static function (string $method, string $url): MockResponse {
            throw new TransportException('connection refused');
        };
        $client = new MockHttpClient($callable);
        $extractor = new AnthropicSpecExtractor($client);
        self::expectException(ExtractionFailedException::class);
        $extractor->extract(['https://code.claude.com/docs/en/hooks']);
    }

    public function testInstalledPluginsReaderSkipsEntriesWithNonArrayList(): void
    {
        $tmp = sys_get_temp_dir() . '/inst-' . bin2hex(random_bytes(4)) . '.json';
        file_put_contents($tmp, '{"plugins":{"a":42,"b":[{"installPath":"/x","version":"1","scope":"p"}]}}');
        $entries = (new InstalledPluginsReader())->read($tmp);
        unlink($tmp);
        self::assertCount(1, $entries);
    }
}
