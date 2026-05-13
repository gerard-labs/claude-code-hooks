<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Tests\Unit\Resolver;

use Gerard\ClaudeCodeHooks\Resolver\HookConfigResolver;
use Gerard\ClaudeCodeHooks\Resolver\HookRegistry;
use Gerard\ClaudeCodeHooks\Resolver\HookRule;
use Gerard\ClaudeCodeHooks\Resolver\HookSource;
use Gerard\ClaudeCodeHooks\Resolver\InMemorySettingsLoader;
use Gerard\ClaudeCodeHooks\Resolver\ManagedHooksFilter;
use Gerard\ClaudeCodeHooks\Resolver\PrecedenceMerger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HookConfigResolver::class)]
#[CoversClass(HookRegistry::class)]
#[CoversClass(HookRule::class)]
#[CoversClass(HookSource::class)]
#[CoversClass(InMemorySettingsLoader::class)]
#[CoversClass(ManagedHooksFilter::class)]
#[CoversClass(PrecedenceMerger::class)]
final class HookConfigResolverTest extends TestCase
{
    public function testItAppliesPolicyPrecedence(): void
    {
        $loader = new InMemorySettingsLoader([
            'policySettings' => ['hooks' => [
                'PreToolUse' => [['matcher' => 'Bash', 'hooks' => [['type' => 'command', 'command' => 'policy.sh']]]],
            ]],
            'projectSettings' => ['hooks' => [
                'PreToolUse' => [['matcher' => 'Bash', 'hooks' => [['type' => 'command', 'command' => 'project.sh']]]],
            ]],
        ]);
        $registry = (new HookConfigResolver($loader))->resolve();
        $rules = $registry->forEvent('PreToolUse');
        self::assertCount(2, $rules);
        // policy must come first (lower precedence number = higher priority).
        self::assertSame(HookSource::Policy, $rules[0]->source);
        self::assertSame(HookSource::Project, $rules[1]->source);
    }

    public function testItIncludesPluginSettingsWithSourceLabel(): void
    {
        $loader = new InMemorySettingsLoader([
            'plugin' => ['hooks' => [
                'PostToolUse' => [['matcher' => '.*', 'hooks' => [['type' => 'command', 'command' => 'plugin.sh']]]],
            ]],
        ]);
        $registry = (new HookConfigResolver($loader))->resolve();
        self::assertSame(HookSource::Plugin, $registry->forEvent('PostToolUse')[0]->source);
    }

    public function testManagedHooksFilterDropsNonManagedWhenLocked(): void
    {
        $loader = new InMemorySettingsLoader([
            'policySettings' => ['allowManagedHooksOnly' => true, 'hooks' => [
                'PreToolUse' => [['matcher' => 'Bash', 'hooks' => [['type' => 'command', 'command' => 'p.sh', 'managed' => true]]]],
            ]],
            'localSettings' => ['hooks' => [
                'PreToolUse' => [['matcher' => 'Bash', 'hooks' => [['type' => 'command', 'command' => 'local.sh']]]],
            ]],
        ]);
        $registry = (new HookConfigResolver($loader))->resolve();
        $rules = $registry->forEvent('PreToolUse');
        self::assertCount(1, $rules);
        self::assertSame(HookSource::Policy, $rules[0]->source);
    }

    public function testManagedHooksFilterIsNoopWhenUnlocked(): void
    {
        $rule = new HookRule('e', 'm', [], HookSource::User);
        $filter = new ManagedHooksFilter(false);
        self::assertSame([$rule], $filter->apply([$rule]));
    }

    public function testHookSourcePrecedenceOrdering(): void
    {
        self::assertLessThan(HookSource::User->precedence(), HookSource::Policy->precedence());
        self::assertLessThan(HookSource::Project->precedence(), HookSource::User->precedence());
        self::assertLessThan(HookSource::Local->precedence(), HookSource::Project->precedence());
        self::assertLessThan(HookSource::Plugin->precedence(), HookSource::Local->precedence());
        self::assertLessThan(HookSource::Runtime->precedence(), HookSource::Plugin->precedence());
    }

    public function testPrecedenceMergerOrdersRuntimeOverridesLast(): void
    {
        $merger = new PrecedenceMerger();
        $rules = [
            new HookRule('e', 'a', [], HookSource::Runtime),
            new HookRule('e', 'b', [], HookSource::Policy),
            new HookRule('e', 'c', [], HookSource::User),
        ];
        $sorted = $merger->merge($rules);
        self::assertSame(HookSource::Policy, $sorted[0]->source);
        self::assertSame(HookSource::User, $sorted[1]->source);
        self::assertSame(HookSource::Runtime, $sorted[2]->source);
    }

    public function testRegistryReturnsEmptyForUnknownEvent(): void
    {
        self::assertSame([], (new HookRegistry())->forEvent('Unknown'));
    }

    public function testInMemoryLoaderReturnsNullForUnknownSource(): void
    {
        $loader = new InMemorySettingsLoader([]);
        self::assertNull($loader->load(HookSource::Policy));
    }

    public function testResolverIgnoresMalformedSettings(): void
    {
        $loader = new InMemorySettingsLoader([
            'projectSettings' => ['hooks' => 'not-an-array'],
        ]);
        $registry = (new HookConfigResolver($loader))->resolve();
        self::assertSame([], $registry->rules);
    }

    public function testResolverSkipsNonStringEventNamesAndNonArrayMatchers(): void
    {
        $loader = new InMemorySettingsLoader([
            'projectSettings' => ['hooks' => [
                'PreToolUse' => 'no',
                123 => [['matcher' => 'x', 'hooks' => []]],
                'PostToolUse' => [['matcher' => 'Bash', 'hooks' => 'not-an-array']],
            ]],
        ]);
        $registry = (new HookConfigResolver($loader))->resolve();
        self::assertSame([], $registry->rules);
    }
}
