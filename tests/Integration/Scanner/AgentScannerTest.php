<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Tests\Integration\Scanner;

use DirectoryIterator;
use Gerard\ClaudeCodeHooks\Exception\InvalidPayloadException;
use Gerard\ClaudeCodeHooks\Scanner\Agent\Agent;
use Gerard\ClaudeCodeHooks\Scanner\Agent\AgentFrontmatterParser;
use Gerard\ClaudeCodeHooks\Scanner\Agent\AgentScanner;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AgentScanner::class)]
#[CoversClass(AgentFrontmatterParser::class)]
#[CoversClass(Agent::class)]
final class AgentScannerTest extends TestCase
{
    private string $tmpRoot;

    protected function setUp(): void
    {
        $this->tmpRoot = sys_get_temp_dir() . '/cch-agent-' . bin2hex(random_bytes(6));
        mkdir($this->tmpRoot, 0o755, true);
    }

    protected function tearDown(): void
    {
        $this->rrm($this->tmpRoot);
    }

    public function testItParsesAgentDefinition(): void
    {
        file_put_contents(
            $this->tmpRoot . '/explore.md',
            "---\nname: explore\ndescription: Exploration agent\n---\nbody",
        );
        $agents = (new AgentScanner(new AgentFrontmatterParser()))->scan([$this->tmpRoot]);
        self::assertCount(1, $agents);
        self::assertSame('explore', $agents[0]->name);
        self::assertSame('Exploration agent', $agents[0]->description);
    }

    public function testItDefaultsNameToFilename(): void
    {
        file_put_contents($this->tmpRoot . '/researcher.md', "---\ndescription: r\n---\nbody");
        $agents = (new AgentScanner(new AgentFrontmatterParser()))->scan([$this->tmpRoot]);
        self::assertSame('researcher', $agents[0]->name);
    }

    public function testItSkipsNonMdFiles(): void
    {
        file_put_contents($this->tmpRoot . '/notes.txt', 'ignored');
        file_put_contents($this->tmpRoot . '/explore.md', "---\nname: explore\n---\nbody");
        $agents = (new AgentScanner(new AgentFrontmatterParser()))->scan([$this->tmpRoot]);
        self::assertCount(1, $agents);
    }

    public function testItIgnoresUnresolvableRoot(): void
    {
        self::assertSame([], (new AgentScanner(new AgentFrontmatterParser()))->scan(['/no-such']));
    }

    public function testParserThrowsOnUnreadable(): void
    {
        self::expectException(InvalidPayloadException::class);
        (new AgentFrontmatterParser())->parse('/no-such-agent.md');
    }

    public function testItSkipsSymlinks(): void
    {
        file_put_contents($this->tmpRoot . '/explore.md', "---\nname: explore\n---\nb");
        symlink('/etc/passwd', $this->tmpRoot . '/evil.md');
        $agents = (new AgentScanner(new AgentFrontmatterParser()))->scan([$this->tmpRoot]);
        self::assertCount(1, $agents);
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
