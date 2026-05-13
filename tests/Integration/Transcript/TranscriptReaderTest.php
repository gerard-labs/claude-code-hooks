<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Tests\Integration\Transcript;

use function count;

use Gerard\ClaudeCodeHooks\Exception\InvalidPayloadException;
use Gerard\ClaudeCodeHooks\Exception\InvalidTranscriptLineException;
use Gerard\ClaudeCodeHooks\Transcript\BoundaryEvent;
use Gerard\ClaudeCodeHooks\Transcript\SidechainGrouper;
use Gerard\ClaudeCodeHooks\Transcript\TranscriptCursor;
use Gerard\ClaudeCodeHooks\Transcript\TranscriptLine;
use Gerard\ClaudeCodeHooks\Transcript\TranscriptReader;
use Gerard\ClaudeCodeHooks\Transcript\Usage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function strlen;

#[CoversClass(TranscriptReader::class)]
#[CoversClass(TranscriptCursor::class)]
#[CoversClass(TranscriptLine::class)]
#[CoversClass(BoundaryEvent::class)]
#[CoversClass(SidechainGrouper::class)]
#[CoversClass(Usage::class)]
final class TranscriptReaderTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/cch-tr-' . bin2hex(random_bytes(6));
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

    public function testItStopsAtIncompleteTailWithoutBlocking(): void
    {
        $path = $this->tmpDir . '/t.jsonl';
        $lines = "{\"type\":\"user\",\"uuid\":\"u1\"}\n";
        $tail = '{"type":"assistant","uuid":"u2"';
        file_put_contents($path, $lines . $tail);

        $reader = new TranscriptReader();
        $cursor = new TranscriptCursor($path);
        $gen = $reader->tail($cursor);

        $events = [];
        foreach ($gen as $e) {
            $events[] = $e;
        }
        $endCursor = $gen->getReturn();

        self::assertCount(1, $events);
        self::assertInstanceOf(TranscriptLine::class, $events[0]);
        self::assertSame('user', $events[0]->type);
        // Cursor returned points to start of incomplete tail
        self::assertSame(strlen($lines), $endCursor->offset);
    }

    public function testItEmitsBoundaryEventOnCompact(): void
    {
        $path = $this->tmpDir . '/t.jsonl';
        $payload = "{\"type\":\"user\",\"uuid\":\"u1\"}\n{\"type\":\"system\",\"subtype\":\"compact_boundary\"}\n";
        file_put_contents($path, $payload);

        $reader = new TranscriptReader();
        $events = [];
        foreach ($reader->tail(new TranscriptCursor($path)) as $e) {
            $events[] = $e;
        }
        self::assertInstanceOf(TranscriptLine::class, $events[0]);
        self::assertInstanceOf(BoundaryEvent::class, $events[1]);
    }

    public function testItResumesFromPersistedOffset(): void
    {
        $path = $this->tmpDir . '/t.jsonl';
        $first = "{\"type\":\"user\",\"uuid\":\"u1\"}\n";
        $second = "{\"type\":\"assistant\",\"uuid\":\"u2\"}\n";
        file_put_contents($path, $first . $second);

        $reader = new TranscriptReader();
        $events = [];
        foreach ($reader->tail(new TranscriptCursor($path, strlen($first))) as $e) {
            $events[] = $e;
        }
        self::assertCount(1, $events);
        self::assertInstanceOf(TranscriptLine::class, $events[0]);
        self::assertSame('assistant', $events[0]->type);
    }

    public function testItTruncatesOversizedLineWithoutOom(): void
    {
        $path = $this->tmpDir . '/t.jsonl';
        $small = 1024;
        $oversized = '{"type":"user","payload":"' . str_repeat('A', $small * 4) . '"}' . "\n";
        file_put_contents($path, $oversized . "{\"type\":\"user\",\"uuid\":\"after\"}\n");

        $reader = new TranscriptReader($small);
        $events = [];
        foreach ($reader->tail(new TranscriptCursor($path)) as $e) {
            $events[] = $e;
        }
        self::assertGreaterThanOrEqual(2, count($events));
        self::assertInstanceOf(TranscriptLine::class, $events[0]);
        self::assertTrue($events[0]->truncated);
    }

    public function testItRejectsTooSmallMaxLineBytesAtConstruction(): void
    {
        self::expectException(InvalidPayloadException::class);
        new TranscriptReader(512);
    }

    public function testItReturnsCursorEarlyOnMissingFile(): void
    {
        $reader = new TranscriptReader();
        $cursor = new TranscriptCursor('/nonexistent.jsonl');
        $gen = $reader->tail($cursor);
        foreach ($gen as $_) {
            self::fail('Should not yield anything');
        }
        self::assertSame($cursor, $gen->getReturn());
    }

    public function testItRaisesInvalidTranscriptLineExceptionOnMalformedJson(): void
    {
        $path = $this->tmpDir . '/t.jsonl';
        file_put_contents($path, "{not json}\n");
        self::expectException(InvalidTranscriptLineException::class);
        $gen = (new TranscriptReader())->tail(new TranscriptCursor($path));
        foreach ($gen as $_) {
            // consume
        }
    }

    public function testItSkipsEmptyLines(): void
    {
        $path = $this->tmpDir . '/t.jsonl';
        file_put_contents($path, "\n{\"type\":\"x\",\"uuid\":\"a\"}\n");
        $events = [];
        foreach ((new TranscriptReader())->tail(new TranscriptCursor($path)) as $e) {
            $events[] = $e;
        }
        self::assertCount(1, $events);
    }

    public function testCursorWithers(): void
    {
        $cursor = new TranscriptCursor('/p', 10);
        self::assertSame(20, $cursor->withOffset(20)->offset);
        self::assertSame('/q', $cursor->withFilePath('/q')->filePath);
        self::assertSame(0, $cursor->withFilePath('/q')->offset);
    }

    public function testSidechainGrouperBuildsParentMap(): void
    {
        $lines = [
            new TranscriptLine('user', 'u1', null, false, [], 0, 10),
            new TranscriptLine('assistant', 'u2', 'u1', true, [], 10, 10),
            new TranscriptLine('assistant', 'u3', 'u1', true, [], 20, 10),
        ];
        $tree = iterator_to_array((new SidechainGrouper())->group($lines));
        self::assertArrayHasKey('u1', $tree);
        self::assertCount(2, $tree['u1']);
    }

    public function testUsageExtractsDocumentedFields(): void
    {
        $usage = Usage::fromPayload([
            'input_tokens' => 100,
            'output_tokens' => 200,
            'cache_read_input_tokens' => 50,
            'cache_creation_input_tokens' => 25,
            'service_tier' => 'priority',
        ]);
        self::assertSame(100, $usage->inputTokens);
        self::assertSame(200, $usage->outputTokens);
        self::assertSame(50, $usage->cacheReadInputTokens);
        self::assertSame(25, $usage->cacheCreationInputTokens);
        self::assertSame('priority', $usage->serviceTier);
    }

    public function testUsageDefaultsForMissingFields(): void
    {
        $usage = Usage::fromPayload([]);
        self::assertSame(0, $usage->inputTokens);
        self::assertSame('standard', $usage->serviceTier);
    }

    public function testUsageRejectsNegativeCounters(): void
    {
        self::expectException(InvalidPayloadException::class);
        new Usage(-1, 0);
    }

    public function testBoundaryEventCarriesReasonAndOffset(): void
    {
        $b = new BoundaryEvent('compact_boundary', 1024);
        self::assertSame('compact_boundary', $b->reason);
        self::assertSame(1024, $b->offset);
    }

    public function testTranscriptLineCarriesAllFields(): void
    {
        $l = new TranscriptLine('user', 'u', 'p', true, ['x' => 1], 0, 10, true);
        self::assertSame('user', $l->type);
        self::assertSame('u', $l->uuid);
        self::assertSame('p', $l->parentUuid);
        self::assertTrue($l->isSidechain);
        self::assertSame(['x' => 1], $l->payload);
        self::assertTrue($l->truncated);
    }
}
