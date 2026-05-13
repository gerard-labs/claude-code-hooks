<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Tests\Integration\Conformance;

use function count;
use function file_get_contents;

use Gerard\ClaudeCodeHooks\Conformance\AnthropicSpecExtractor;

use function hash_file;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;

use function trim;

/**
 * AC-2: the extractor against the captured fixture of the live
 * `code.claude.com/docs/en/hooks` page returns ≥ 25 events. The fixture is
 * pinned by SHA-256 sidecar so a stealth edit fails the build.
 */
#[CoversClass(AnthropicSpecExtractor::class)]
final class AnthropicSpecExtractorLiveShapeTest extends TestCase
{
    private const string FIXTURE_PATH = __DIR__ . '/../../Fixtures/html/code-claude-com-hooks-2026-05-02.html';
    private const string SHA256_PATH = self::FIXTURE_PATH . '.sha256';

    public function test_html_fixture_matches_pinned_sha256_sidecar(): void
    {
        $sidecar = trim((string) file_get_contents(self::SHA256_PATH));
        // sha256sum format: "<hex> <filename>"; take the first whitespace-separated token.
        $expected = explode(' ', $sidecar)[0];

        self::assertSame(
            $expected,
            hash_file('sha256', self::FIXTURE_PATH),
            'HTML fixture has been edited without re-running the capture procedure.',
        );
    }

    public function test_extractor_returns_at_least_twenty_five_events_against_captured_fixture(): void
    {
        $extractor = new AnthropicSpecExtractor(new MockHttpClient());
        $spec = $extractor->extractFromHtml(
            'https://code.claude.com/docs/en/hooks',
            (string) file_get_contents(self::FIXTURE_PATH),
        );

        self::assertIsArray($spec['events']);
        self::assertGreaterThanOrEqual(25, count($spec['events']));
    }

    public function test_extractor_sets_snapshot_version_on_output(): void
    {
        $extractor = new AnthropicSpecExtractor(new MockHttpClient());
        $spec = $extractor->extractFromHtml(
            'https://code.claude.com/docs/en/hooks',
            (string) file_get_contents(self::FIXTURE_PATH),
        );

        self::assertSame(AnthropicSpecExtractor::SNAPSHOT_VERSION, $spec['version']);
    }
}
