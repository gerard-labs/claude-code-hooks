<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Tests\Unit\Linter;

use Gerard\ClaudeCodeHooks\Linter\ConfigProfile;
use Gerard\ClaudeCodeHooks\Linter\HookConfig;
use Gerard\ClaudeCodeHooks\Linter\Rule\Cch005SecretInUrlLiteral;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Cch005SecretInUrlLiteral::class)]
final class Cch005SecretCoverageTest extends TestCase
{
    public function testNoFindingsForNoUrlAndNoHeaders(): void
    {
        $rule = new Cch005SecretInUrlLiteral();
        $config = new HookConfig('PreToolUse', 'Bash', ['type' => 'command'], 'project');
        self::assertSame([], iterator_to_array($rule->inspect($config, ConfigProfile::Default)));
    }

    public function testIgnoresNonStringHeaderValuesAndKeys(): void
    {
        $rule = new Cch005SecretInUrlLiteral();
        $config = new HookConfig('PreToolUse', 'Bash', [
            'type' => 'http', 'url' => 'https://x',
            'headers' => [42 => 'ignored', 'OK' => 'safe'],
        ], 'project');
        self::assertSame([], iterator_to_array($rule->inspect($config, ConfigProfile::Default)));
    }
}
