<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Tests\Integration\Scanner;

use DirectoryIterator;
use Gerard\ClaudeCodeHooks\Exception\InvalidPayloadException;
use Gerard\ClaudeCodeHooks\Scanner\Skill\Skill;
use Gerard\ClaudeCodeHooks\Scanner\Skill\SkillFrontmatterParser;
use Gerard\ClaudeCodeHooks\Scanner\Skill\SkillScanner;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SkillFrontmatterParser::class)]
#[CoversClass(SkillScanner::class)]
#[CoversClass(Skill::class)]
final class SkillScannerTest extends TestCase
{
    private string $tmpRoot;

    protected function setUp(): void
    {
        $this->tmpRoot = sys_get_temp_dir() . '/cch-skill-' . bin2hex(random_bytes(6));
        mkdir($this->tmpRoot . '/symfony-conv', 0o755, true);
    }

    protected function tearDown(): void
    {
        $this->rrm($this->tmpRoot);
    }

    public function testItParsesHooksFrontmatterSection(): void
    {
        file_put_contents(
            $this->tmpRoot . '/symfony-conv/SKILL.md',
            "---\nname: symfony-conv\ndescription: Symfony conventions\nhooks:\n  - PreToolUse\n  - PostToolUse\n---\nbody",
        );
        $skills = (new SkillScanner(new SkillFrontmatterParser()))->scan([$this->tmpRoot]);
        self::assertCount(1, $skills);
        self::assertSame('symfony-conv', $skills[0]->name);
        self::assertSame(['PreToolUse', 'PostToolUse'], $skills[0]->triggerHooks);
    }

    public function testItThrowsOnMalformedFrontmatter(): void
    {
        file_put_contents(
            $this->tmpRoot . '/symfony-conv/SKILL.md',
            "no frontmatter just text\n",
        );
        self::expectException(InvalidPayloadException::class);
        (new SkillScanner(new SkillFrontmatterParser()))->scan([$this->tmpRoot]);
    }

    public function testParserReadsDescriptionAndDefaultsName(): void
    {
        file_put_contents(
            $this->tmpRoot . '/symfony-conv/SKILL.md',
            "---\ndescription: Auto-named\n---\nbody",
        );
        $skill = (new SkillFrontmatterParser())->parse($this->tmpRoot . '/symfony-conv/SKILL.md');
        self::assertSame('symfony-conv', $skill->name);
        self::assertSame('Auto-named', $skill->description);
    }

    public function testScannerSkipsLinksAndNonDirs(): void
    {
        file_put_contents($this->tmpRoot . '/loose.md', 'not a skill');
        symlink('/etc', $this->tmpRoot . '/evil');
        $scanner = new SkillScanner(new SkillFrontmatterParser());
        // No SKILL.md in symfony-conv yet; expect 0 skills (loose.md is a file, evil is a link).
        self::assertSame([], $scanner->scan([$this->tmpRoot]));
    }

    public function testScannerIgnoresUnresolvableRoot(): void
    {
        self::assertSame([], (new SkillScanner(new SkillFrontmatterParser()))->scan(['/non-existent-99']));
    }

    public function testParserThrowsOnUnreadableFile(): void
    {
        self::expectException(InvalidPayloadException::class);
        (new SkillFrontmatterParser())->parse('/non-existent-skill-12345.md');
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
