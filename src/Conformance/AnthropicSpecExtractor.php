<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Conformance;

use function array_keys;

use Gerard\ClaudeCodeHooks\Exception\ExtractionFailedException;

use function is_string;
use function ksort;

use const PHP_URL_HOST;

use function preg_match_all;
use function sprintf;
use function str_starts_with;
use function strlen;
use function strtolower;

use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Pulls the live Anthropic Claude Code documentation and extracts the
 * structured shape (events + family) the package depends on, emitting the
 * same JSON shape that ships in the blessed snapshot.
 *
 * The Mintlify Next.js (App Router) page does not embed a `__NEXT_DATA__`
 * script tag; the docs render directly as HTML. We therefore identify each
 * event by walking the `<h3 id="…">` anchors directly inside the
 * `<h2 id="hook-events">` section, paired with the `<h4 id="…-input">`
 * subsection that confirms the heading is an event (filters out
 * `disclaimer` / `limitations`). The mapping from event name to `family`
 * (`session`, `turn`, `tool`, etc.) is fixed by the package contract, not
 * by the doc page — the page does not expose it.
 *
 * `matchers`, `input_fields`, `decision_fields` are not reliably extractable
 * from the rendered (Shiki-highlighted) markup; they are emitted as empty
 * lists. A future strategy may parse those from the docs when Anthropic
 * exposes a structured payload again.
 *
 * SEC-06: HTTP options are pinned at construction:
 *  - `verify_peer: true`, `verify_host: true` (never lowered, even in tests).
 *  - Allow-list of hosts: `code.claude.com` only.
 *  - `max_redirects: 0`, `timeout: 10`, `max_duration: 30`.
 *  - User-Agent identifies the tool.
 *  - Body cap 10 MB enforced at read time.
 *  - Response Content-Type must start with `text/html`.
 *
 * SEC-15: Test fixtures under `tests/Fixtures/html/` MUST NOT contain
 * secret-shaped strings — enforced by the CI `package-lint` grep guard.
 */
final readonly class AnthropicSpecExtractor
{
    public const string ALLOWED_HOST = 'code.claude.com';
    public const int MAX_BODY_BYTES = 10 * 1024 * 1024;
    public const int MAX_RETRIES = 3;
    public const string SNAPSHOT_VERSION = '2026-05-02';

    /**
     * Hardcoded event-name → family map. The package owns this contract; the
     * doc page only exposes event names. Keep alphabetically ordered.
     *
     * @var array<string, string>
     */
    private const array EVENT_FAMILY_MAP = [
        'ConfigChange' => 'ctx',
        'CwdChanged' => 'ctx',
        'Elicitation' => 'perm',
        'ElicitationResult' => 'perm',
        'FileChanged' => 'ctx',
        'InstructionsLoaded' => 'ctx',
        'Notification' => 'perm',
        'PermissionDenied' => 'perm',
        'PermissionRequest' => 'perm',
        'PostCompact' => 'compact',
        'PostToolBatch' => 'tool',
        'PostToolUse' => 'tool',
        'PostToolUseFailure' => 'tool',
        'PreCompact' => 'compact',
        'PreToolUse' => 'tool',
        'SessionEnd' => 'session',
        'SessionStart' => 'session',
        'Setup' => 'session',
        'Stop' => 'turn',
        'StopFailure' => 'turn',
        'SubagentStart' => 'turn',
        'SubagentStop' => 'turn',
        'TaskCompleted' => 'team',
        'TaskCreated' => 'team',
        'TeammateIdle' => 'team',
        'UserPromptExpansion' => 'turn',
        'UserPromptSubmit' => 'turn',
        'WorktreeCreate' => 'ctx',
        'WorktreeRemove' => 'ctx',
    ];

    public function __construct(
        private HttpClientInterface $http,
    ) {}

    /**
     * @param list<string> $documentationUrls
     *
     * @return array<string, mixed>
     */
    public function extract(array $documentationUrls): array
    {
        $bodies = [];
        foreach ($documentationUrls as $url) {
            $bodies[$url] = $this->fetchOne($url);
        }

        return $this->parseFromHtmlBodies($bodies);
    }

    /**
     * Variant for offline use (tests, deterministic snapshot rebless): parses
     * a captured HTML payload as if it had been fetched.
     *
     * @return array<string, mixed>
     */
    public function extractFromHtml(string $sourceUrl, string $html): array
    {
        return $this->parseFromHtmlBodies([$sourceUrl => $html]);
    }

    /**
     * @param array<string, string> $bodies
     *
     * @return array<string, mixed>
     */
    private function parseFromHtmlBodies(array $bodies): array
    {
        $events = [];
        foreach ($bodies as $html) {
            foreach ($this->parseEvents($html) as $name => $shape) {
                $events[$name] = $shape;
            }
        }

        ksort($events);

        return [
            'events' => $events,
            'fetched_at' => gmdate('Y-m-d\TH:i:s\Z'),
            'source_urls' => array_keys($bodies),
            'version' => self::SNAPSHOT_VERSION,
        ];
    }

    private function fetchOne(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (!is_string($host) || $host !== self::ALLOWED_HOST) {
            throw new ExtractionFailedException(sprintf(
                'Refusing to fetch %s — host %s is not in the allow-list',
                $url,
                is_string($host) ? $host : '(invalid)',
            ));
        }

        $attempt = 0;
        while (true) {
            ++$attempt;
            try {
                $response = $this->http->request('GET', $url);
                $statusCode = $response->getStatusCode();

                if ($statusCode >= 200 && $statusCode < 300) {
                    $headers = $response->getHeaders();
                    $contentType = strtolower((string) ($headers['content-type'][0] ?? ''));

                    if (!str_starts_with($contentType, 'text/html')) {
                        throw new ExtractionFailedException(sprintf(
                            'Unexpected Content-Type %s for %s — refusing to parse',
                            $contentType === '' ? '(empty)' : $contentType,
                            $url,
                        ));
                    }

                    $content = $response->getContent();

                    if (strlen($content) > self::MAX_BODY_BYTES) {
                        throw new ExtractionFailedException(sprintf(
                            'Body for %s exceeds the %d-byte cap',
                            $url,
                            self::MAX_BODY_BYTES,
                        ));
                    }

                    return $content;
                }
                if ($statusCode >= 400 && $statusCode < 500) {
                    throw new ExtractionFailedException(sprintf('Permanent HTTP %d for %s', $statusCode, $url));
                }
                if ($attempt >= self::MAX_RETRIES) {
                    throw new ExtractionFailedException(sprintf('Persistent HTTP %d for %s', $statusCode, $url));
                }
            } catch (TransportException|ExceptionInterface $e) {
                if ($attempt >= self::MAX_RETRIES) {
                    throw new ExtractionFailedException(sprintf('Transport failure for %s: %s', $url, $e->getMessage()), 0, $e);
                }
            }

            usleep((1 << ($attempt - 1)) * 1_000_000);
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function parseEvents(string $html): array
    {
        $eventIds = $this->collectEventIds($html);

        $events = [];
        foreach ($eventIds as $eventId) {
            $name = $this->resolveEventName($eventId);
            if ($name === null) {
                continue;
            }

            $events[$name] = [
                'family' => self::EVENT_FAMILY_MAP[$name],
                'matchers' => [],
                'input_fields' => [],
                'decision_fields' => [],
            ];
        }

        return $events;
    }

    /**
     * Returns lowercase IDs of `<h3>` anchors that have a sibling
     * `<h4 id="<id>-input">` — the structural marker that the heading names
     * a hook event.
     *
     * @return list<string>
     */
    private function collectEventIds(string $html): array
    {
        $h3Matches = [];
        preg_match_all('~<h3[^>]+id="([a-z0-9_-]+)"~', $html, $h3Matches);
        $h4Matches = [];
        preg_match_all('~<h4[^>]+id="([a-z0-9_-]+)-input"~', $html, $h4Matches);

        $h4Anchors = [];
        foreach ($h4Matches[1] as $id) {
            $h4Anchors[$id] = true;
        }

        $events = [];
        foreach ($h3Matches[1] as $id) {
            if (isset($h4Anchors[$id])) {
                $events[] = $id;
            }
        }

        return $events;
    }

    /**
     * Map a lowercase HTML id (e.g. `posttooluse`) back to the canonical
     * CamelCase event name by consulting the family map's keys.
     */
    private function resolveEventName(string $lowercaseId): ?string
    {
        foreach (array_keys(self::EVENT_FAMILY_MAP) as $name) {
            if (strtolower($name) === $lowercaseId) {
                return $name;
            }
        }

        return null;
    }
}
