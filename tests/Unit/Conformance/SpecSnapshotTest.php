<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Tests\Unit\Conformance;

use Gerard\ClaudeCodeHooks\Conformance\SpecSnapshot;
use Gerard\ClaudeCodeHooks\Exception\InvalidPayloadException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SpecSnapshot::class)]
final class SpecSnapshotTest extends TestCase
{
    private const string SNAPSHOT = __DIR__ . '/../../Fixtures/anthropic-spec/snapshot.json';

    protected function setUp(): void
    {
        putenv('CLAUDE_HOOKS_SPEC_FILE');
    }

    protected function tearDown(): void
    {
        putenv('CLAUDE_HOOKS_SPEC_FILE');
    }

    public function testItLoadsBlessedSnapshotAndExposesEvents(): void
    {
        $spec = SpecSnapshot::load(self::SNAPSHOT);
        self::assertArrayHasKey('events', $spec);
        self::assertCount(29, (array) $spec['events']);
    }

    public function testEnvVarOverridesPath(): void
    {
        $tmp = sys_get_temp_dir() . '/snap-' . bin2hex(random_bytes(4)) . '.json';
        file_put_contents($tmp, '{"events":{}}');
        putenv('CLAUDE_HOOKS_SPEC_FILE=' . $tmp);
        $spec = SpecSnapshot::load(self::SNAPSHOT);
        unlink($tmp);
        self::assertSame([], $spec['events']);
    }

    public function testMissingFileRaisesInvalidPayload(): void
    {
        self::expectException(InvalidPayloadException::class);
        SpecSnapshot::load('/non-existent-snapshot.json');
    }
}
