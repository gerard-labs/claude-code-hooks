<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Tests\Integration\Conformance;

use Gerard\ClaudeCodeHooks\Conformance\AnthropicSpecExtractor;
use Gerard\ClaudeCodeHooks\Exception\ExtractionFailedException;

use function is_array;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(AnthropicSpecExtractor::class)]
final class AnthropicSpecExtractorTest extends TestCase
{
    public function test_it_extracts_event_names_from_heading_anchors(): void
    {
        $html = <<<'HTML'
            <html><body>
            <h2 id="hook-events">Hook events</h2>
            <h3 id="pretooluse">PreToolUse</h3>
            <h4 id="pretooluse-input">Input</h4>
            <h3 id="stop">Stop</h3>
            <h4 id="stop-input">Input</h4>
            <h3 id="disclaimer">Disclaimer</h3>
            </body></html>
            HTML;
        $client = new MockHttpClient([new MockResponse($html, ['response_headers' => ['content-type' => 'text/html; charset=utf-8']])]);
        $extractor = new AnthropicSpecExtractor($client);

        $spec = $extractor->extract(['https://code.claude.com/docs/en/hooks']);
        $events = $this->asArray($spec['events']);

        self::assertArrayHasKey('PreToolUse', $events);
        self::assertArrayHasKey('Stop', $events);
        self::assertArrayNotHasKey('Disclaimer', $events);
        self::assertSame('tool', $this->asArray($events['PreToolUse'])['family']);
        self::assertSame('turn', $this->asArray($events['Stop'])['family']);
    }

    public function test_it_skips_unknown_event_ids(): void
    {
        // <h3 id="madeup"> with matching <h4 id="madeup-input"> — passes the structural
        // check but is not in the EVENT_FAMILY_MAP, so resolveEventName() returns null
        // and the event is silently dropped (not in our contract).
        $html = <<<'HTML'
            <h3 id="madeup">Madeup</h3>
            <h4 id="madeup-input">Input</h4>
            <h3 id="stop">Stop</h3>
            <h4 id="stop-input">Input</h4>
            HTML;
        $client = new MockHttpClient([new MockResponse($html, ['response_headers' => ['content-type' => 'text/html']])]);
        $spec = (new AnthropicSpecExtractor($client))->extract(['https://code.claude.com/docs/en/hooks']);
        $events = $this->asArray($spec['events']);

        self::assertArrayHasKey('Stop', $events);
        self::assertArrayNotHasKey('Madeup', $events);
    }

    public function test_it_emits_snapshot_version_constant(): void
    {
        $html = <<<'HTML'
            <h2 id="hook-events"></h2>
            <h3 id="stop">Stop</h3>
            <h4 id="stop-input">Input</h4>
            HTML;
        $client = new MockHttpClient([new MockResponse($html, ['response_headers' => ['content-type' => 'text/html']])]);
        $spec = (new AnthropicSpecExtractor($client))->extract(['https://code.claude.com/docs/en/hooks']);

        self::assertSame(AnthropicSpecExtractor::SNAPSHOT_VERSION, $spec['version']);
    }

    public function test_it_rejects_redirect_to_non_allowlisted_host(): void
    {
        $client = new MockHttpClient([new MockResponse('<html></html>')]);
        $extractor = new AnthropicSpecExtractor($client);
        self::expectException(ExtractionFailedException::class);
        $extractor->extract(['https://evil.example/hooks']);
    }

    public function test_it_rejects_unexpected_content_type(): void
    {
        $client = new MockHttpClient([new MockResponse('not html', ['response_headers' => ['content-type' => 'application/json']])]);
        $extractor = new AnthropicSpecExtractor($client);
        self::expectException(ExtractionFailedException::class);
        $extractor->extract(['https://code.claude.com/docs/en/hooks']);
    }

    public function test_it_retries_then_fails_on_persistent_5xx(): void
    {
        $responses = [
            new MockResponse('', ['http_code' => 500]),
            new MockResponse('', ['http_code' => 502]),
            new MockResponse('', ['http_code' => 503]),
        ];
        $client = new MockHttpClient($responses);
        $extractor = new AnthropicSpecExtractor($client);
        self::expectException(ExtractionFailedException::class);
        $extractor->extract(['https://code.claude.com/docs/en/hooks']);
    }

    public function test_it_fails_on_permanent_4xx(): void
    {
        $client = new MockHttpClient([new MockResponse('', ['http_code' => 404])]);
        $extractor = new AnthropicSpecExtractor($client);
        self::expectException(ExtractionFailedException::class);
        $extractor->extract(['https://code.claude.com/docs/en/hooks']);
    }
    /**
     * @return array<array-key, mixed>
     */
    private function asArray(mixed $value): array
    {
        if (!is_array($value)) {
            self::fail('expected array, got ' . get_debug_type($value));
        }

        return $value;
    }
}
