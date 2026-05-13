<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Tests\Unit\Resolver;

use Gerard\ClaudeCodeHooks\Resolver\HookConfigResolver;
use Gerard\ClaudeCodeHooks\Resolver\HookRule;
use Gerard\ClaudeCodeHooks\Resolver\HookSource;
use Gerard\ClaudeCodeHooks\Resolver\InMemorySettingsLoader;
use Gerard\ClaudeCodeHooks\Resolver\ManagedHooksFilter;
use Gerard\ClaudeCodeHooks\Resolver\PrecedenceMerger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HookConfigResolver::class)]
#[CoversClass(HookRule::class)]
#[CoversClass(HookSource::class)]
#[CoversClass(InMemorySettingsLoader::class)]
#[CoversClass(ManagedHooksFilter::class)]
#[CoversClass(PrecedenceMerger::class)]
final class AdditionalResolverCoverageTest extends TestCase
{
    public function testMergerPreservesInsertionOrderWithinSameSource(): void
    {
        $a = new HookRule('e', 'a', [], HookSource::User);
        $b = new HookRule('e', 'b', [], HookSource::User);
        $sorted = (new PrecedenceMerger())->merge([$a, $b]);
        self::assertSame('a', $sorted[0]->matcher);
        self::assertSame('b', $sorted[1]->matcher);
    }

    public function testMergerAcceptsEmptyList(): void
    {
        self::assertSame([], (new PrecedenceMerger())->merge([]));
    }

    /**
     * Asserts the exact integer precedence values: Policy=0 .. Runtime=5.
     * Pins the contract that Policy is the lowest (= highest priority) and
     * Runtime is the highest. Catches off-by-one drifts (Policy → -1,
     * Runtime → 6) that ordering-only assertions miss.
     */
    public function testHookSourcePrecedenceExactValues(): void
    {
        self::assertSame(0, HookSource::Policy->precedence());
        self::assertSame(1, HookSource::User->precedence());
        self::assertSame(2, HookSource::Project->precedence());
        self::assertSame(3, HookSource::Local->precedence());
        self::assertSame(4, HookSource::Plugin->precedence());
        self::assertSame(5, HookSource::Runtime->precedence());
    }

    /**
     * Two rules with the same source must compare equal on precedence (delta 0)
     * AND must keep insertion order. Pins the precedence-equality branch
     * against `!== 0` mutating to `!== -1`/`!== 1`.
     */
    public function testMergerComparatorReturnsZeroOnEqualPrecedence(): void
    {
        $a = new HookRule('e', 'a', [], HookSource::User);
        $b = new HookRule('e', 'b', [], HookSource::User);
        $c = new HookRule('e', 'c', [], HookSource::User);
        $sorted = (new PrecedenceMerger())->merge([$a, $b, $c]);
        self::assertSame(['a', 'b', 'c'], [$sorted[0]->matcher, $sorted[1]->matcher, $sorted[2]->matcher]);
    }

    /**
     * Resolver iterates over every HookSource case. If the FIRST source has
     * malformed `hooks` data and the SECOND has valid data, the second must
     * still be processed. Pins the `continue` semantics in the source loop
     * against a `break` mutation that would short-circuit the iteration.
     */
    public function testResolverContinuesAcrossSourcesWhenOneIsMalformed(): void
    {
        $loader = new InMemorySettingsLoader([
            // policySettings is the FIRST case in HookSource::cases() —
            // its missing 'hooks' key must NOT abort the loop.
            'policySettings' => ['allowManagedHooksOnly' => false],
            'projectSettings' => ['hooks' => [
                'PreToolUse' => [['matcher' => 'Bash', 'hooks' => [['type' => 'command', 'command' => 'p.sh']]]],
            ]],
        ]);
        $registry = (new HookConfigResolver($loader))->resolve();
        $rules = $registry->forEvent('PreToolUse');
        self::assertCount(1, $rules);
        self::assertSame(HookSource::Project, $rules[0]->source);
    }

    /**
     * Inside a single source, a malformed event entry must `continue` (not
     * `break`) so later valid entries are still picked up. Pins the inner
     * `continue` against the same break-mutation.
     */
    public function testResolverContinuesAcrossEventsWhenOneIsMalformed(): void
    {
        $loader = new InMemorySettingsLoader([
            'projectSettings' => ['hooks' => [
                // First entry: malformed (matchers is not an array).
                'PreToolUse' => 'not-an-array',
                // Second entry: valid — must still produce a rule.
                'PostToolUse' => [['matcher' => 'Bash', 'hooks' => [['type' => 'command', 'command' => 'p.sh']]]],
            ]],
        ]);
        $registry = (new HookConfigResolver($loader))->resolve();
        self::assertSame([], $registry->forEvent('PreToolUse'));
        self::assertCount(1, $registry->forEvent('PostToolUse'));
    }

    /**
     * `HookRule::$managed` defaults to `false`. Pins the default against a
     * `FalseValue` mutant flipping it to `true` (which would leak unmanaged
     * rules through the managed-hooks filter).
     */
    public function testHookRuleManagedDefaultsToFalse(): void
    {
        $rule = new HookRule('PreToolUse', 'Bash', [], HookSource::User);
        self::assertFalse($rule->managed);
    }

    /**
     * Under `allowManagedHooksOnly: true`, rules are kept when their source
     * is Policy OR they carry the explicit `managed` flag. Pins the `||`
     * against a `LogicalOr → &&` mutation: a managed-but-non-Policy rule
     * (e.g. a user setting blessed by an MDM bundle) MUST survive the filter.
     */
    public function testManagedFilterKeepsNonPolicyManagedRule(): void
    {
        $managedUserRule = new HookRule('e', 'm', [], HookSource::User, managed: true);
        $unmanagedUserRule = new HookRule('e', 'u', [], HookSource::User);
        $filter = new ManagedHooksFilter(true);
        $kept = $filter->apply([$managedUserRule, $unmanagedUserRule]);
        self::assertSame([$managedUserRule], $kept);
    }
}
