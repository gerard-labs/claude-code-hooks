<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Tests\Unit\Linter;

use Gerard\ClaudeCodeHooks\Linter\ConfigProfile;
use Gerard\ClaudeCodeHooks\Linter\Finding;
use Gerard\ClaudeCodeHooks\Linter\HookConfig;
use Gerard\ClaudeCodeHooks\Linter\HookLinter;
use Gerard\ClaudeCodeHooks\Linter\Rule\Cch001BroadMatcherInPolicy;
use Gerard\ClaudeCodeHooks\Linter\Rule\Cch002UnknownEventName;
use Gerard\ClaudeCodeHooks\Linter\Rule\Cch003MissingMatcherForToolEvent;
use Gerard\ClaudeCodeHooks\Linter\Rule\Cch004HttpHandlerWithoutTimeout;
use Gerard\ClaudeCodeHooks\Linter\Rule\Cch005SecretInUrlLiteral;
use Gerard\ClaudeCodeHooks\Linter\Rule\Cch011BroadMatcherOnPromptOrAgent;
use Gerard\ClaudeCodeHooks\Linter\Severity;

use function is_array;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function strlen;

#[CoversClass(HookLinter::class)]
#[CoversClass(HookConfig::class)]
#[CoversClass(Finding::class)]
#[CoversClass(Severity::class)]
#[CoversClass(ConfigProfile::class)]
#[CoversClass(Cch001BroadMatcherInPolicy::class)]
#[CoversClass(Cch002UnknownEventName::class)]
#[CoversClass(Cch003MissingMatcherForToolEvent::class)]
#[CoversClass(Cch004HttpHandlerWithoutTimeout::class)]
#[CoversClass(Cch005SecretInUrlLiteral::class)]
#[CoversClass(Cch011BroadMatcherOnPromptOrAgent::class)]
final class LinterTest extends TestCase
{
    public function testCch001FiresOnlyInPolicyProfile(): void
    {
        $rule = new Cch001BroadMatcherInPolicy();
        $config = new HookConfig('PreToolUse', '.*', [], 'project');
        $observ = iterator_to_array($rule->inspect($config, ConfigProfile::Observability));
        $policy = iterator_to_array($rule->inspect($config, ConfigProfile::Policy));
        self::assertCount(0, $observ);
        self::assertCount(1, $policy);
        self::assertSame(Severity::Error, $policy[0]->severity);
        self::assertSame('CCH001', $policy[0]->code);
    }

    public function testCch001IsSilentForNarrowMatcher(): void
    {
        $rule = new Cch001BroadMatcherInPolicy();
        self::assertSame([], iterator_to_array($rule->inspect(new HookConfig('PreToolUse', 'Bash', [], 'project'), ConfigProfile::Policy)));
    }

    public function testCch002FlagsUnknownEventName(): void
    {
        $rule = new Cch002UnknownEventName();
        $findings = iterator_to_array($rule->inspect(new HookConfig('TotallyNew', '', [], 'project'), ConfigProfile::Default));
        self::assertCount(1, $findings);
        self::assertSame('CCH002', $findings[0]->code);
    }

    public function testCch002IsSilentForKnownEvent(): void
    {
        $rule = new Cch002UnknownEventName();
        self::assertSame([], iterator_to_array($rule->inspect(new HookConfig('PreToolUse', '', [], 'project'), ConfigProfile::Default)));
    }

    public function testCch003WarnsToolEventWithoutMatcher(): void
    {
        $rule = new Cch003MissingMatcherForToolEvent();
        $findings = iterator_to_array($rule->inspect(new HookConfig('PreToolUse', '', [], 'project'), ConfigProfile::Default));
        self::assertCount(1, $findings);
        self::assertSame('CCH003', $findings[0]->code);
    }

    public function testCch003SilentForNonToolEventOrWithMatcher(): void
    {
        $rule = new Cch003MissingMatcherForToolEvent();
        self::assertSame([], iterator_to_array($rule->inspect(new HookConfig('SessionStart', '', [], 'project'), ConfigProfile::Default)));
        self::assertSame([], iterator_to_array($rule->inspect(new HookConfig('PreToolUse', 'Bash', [], 'project'), ConfigProfile::Default)));
    }

    public function testCch004WarnsHttpWithoutTimeout(): void
    {
        $rule = new Cch004HttpHandlerWithoutTimeout();
        $findings = iterator_to_array($rule->inspect(
            new HookConfig('PreToolUse', 'Bash', ['type' => 'http', 'url' => 'https://x'], 'project'),
            ConfigProfile::Default,
        ));
        self::assertCount(1, $findings);
        self::assertSame('CCH004', $findings[0]->code);
    }

    public function testCch004SilentForCommandHandlerOrWithTimeout(): void
    {
        $rule = new Cch004HttpHandlerWithoutTimeout();
        self::assertSame([], iterator_to_array($rule->inspect(
            new HookConfig('PreToolUse', 'Bash', ['type' => 'command'], 'project'),
            ConfigProfile::Default,
        )));
        self::assertSame([], iterator_to_array($rule->inspect(
            new HookConfig('PreToolUse', 'Bash', ['type' => 'http', 'timeout' => 5], 'project'),
            ConfigProfile::Default,
        )));
    }

    public function testCch005FlagsLiteralBearerInHeader(): void
    {
        $rule = new Cch005SecretInUrlLiteral();
        $config = new HookConfig('PreToolUse', 'Bash', [
            'type' => 'http', 'url' => 'https://example.com',
            'headers' => ['Authorization' => 'Bearer eyJxxxxxxxxxxxxxxxxx'],
        ], 'project');
        $findings = iterator_to_array($rule->inspect($config, ConfigProfile::Default));
        self::assertCount(1, $findings);
        self::assertStringContainsString('Bearer', $findings[0]->message);
        self::assertStringNotContainsString('eyJxxx', $findings[0]->message);
    }

    public function testCch005SilentForPlaceholderHeader(): void
    {
        $rule = new Cch005SecretInUrlLiteral();
        $config = new HookConfig('PreToolUse', 'Bash', [
            'type' => 'http', 'url' => 'https://x',
            'headers' => ['Authorization' => 'Bearer ${TOKEN}'],
        ], 'project');
        self::assertSame([], iterator_to_array($rule->inspect($config, ConfigProfile::Default)));
    }

    public function testCch005DetectsGitlabPatGlpatAndJwtAndSkInUrl(): void
    {
        $rule = new Cch005SecretInUrlLiteral();
        $glpat = new HookConfig('PreToolUse', 'B', ['type' => 'http', 'url' => 'https://x?token=glpat-abcdefg1'], 'project');
        $jwt = new HookConfig('PreToolUse', 'B', ['type' => 'http', 'url' => 'https://x?token=eyJabcdefghijklmnop'], 'project');
        $sk = new HookConfig('PreToolUse', 'B', ['type' => 'http', 'url' => 'https://x?token=sk-abcdefghijklmnop'], 'project');
        self::assertCount(1, iterator_to_array($rule->inspect($glpat, ConfigProfile::Default)));
        self::assertCount(1, iterator_to_array($rule->inspect($jwt, ConfigProfile::Default)));
        self::assertCount(1, iterator_to_array($rule->inspect($sk, ConfigProfile::Default)));
    }

    public function testCch005FindingMessageNeverQuotesSuspectedSecret(): void
    {
        $rule = new Cch005SecretInUrlLiteral();
        $secret = 'Bearer abc' . str_repeat('X', 200);
        $config = new HookConfig('PreToolUse', 'B', [
            'type' => 'http', 'url' => 'https://x', 'headers' => ['Authorization' => $secret],
        ], 'project');
        $findings = iterator_to_array($rule->inspect($config, ConfigProfile::Default));
        $message = $findings[0]->message;
        self::assertStringNotContainsString(str_repeat('X', 9), $message, 'Finding must not embed the secret value');
        self::assertLessThanOrEqual(Finding::MAX_MESSAGE_BYTES, strlen($message));
    }

    public function testCch011WarnsBroadPromptOrAgentMatcher(): void
    {
        $rule = new Cch011BroadMatcherOnPromptOrAgent();
        $config = new HookConfig('PreToolUse', '.*', ['type' => 'prompt'], 'project');
        $findings = iterator_to_array($rule->inspect($config, ConfigProfile::Default));
        self::assertCount(1, $findings);
        self::assertSame(Severity::Warning, $findings[0]->severity);
    }

    public function testCch011SilentForCommandHandler(): void
    {
        $rule = new Cch011BroadMatcherOnPromptOrAgent();
        self::assertSame([], iterator_to_array($rule->inspect(
            new HookConfig('PreToolUse', '.*', ['type' => 'command'], 'project'),
            ConfigProfile::Default,
        )));
    }

    public function testCch011SilentForNarrowMatcherOnAgent(): void
    {
        $rule = new Cch011BroadMatcherOnPromptOrAgent();
        self::assertSame([], iterator_to_array($rule->inspect(
            new HookConfig('PreToolUse', 'Explore', ['type' => 'agent'], 'project'),
            ConfigProfile::Default,
        )));
    }

    public function testHookConfigDebugInfoRedactsAuthorizationHeader(): void
    {
        $config = new HookConfig('PreToolUse', 'Bash', [
            'type' => 'http', 'url' => 'https://x', 'headers' => ['Authorization' => 'Bearer xxx', 'Other' => 'safe'],
        ], 'project');
        $debug = $config->__debugInfo();
        $headers = $this->asArray($this->asArray($debug['handler'])['headers']);
        self::assertSame('***redacted***', $headers['Authorization']);
        self::assertSame('safe', $headers['Other']);
    }

    /**
     * The __debugInfo() output MUST expose the event name so callers (and the
     * developer reading var_dump) see which event the entry belongs to. Pins
     * the `'event' => $this->event` key against an `ArrayItemRemoval` mutant
     * that drops it from the output.
     */
    public function testHookConfigDebugInfoExposesEventMatcherAndSource(): void
    {
        $config = new HookConfig('SessionStart', 'BashMatcher', ['type' => 'command', 'command' => 'x.sh'], 'projectSettings');
        $debug = $config->__debugInfo();
        self::assertSame('SessionStart', $debug['event']);
        self::assertSame('BashMatcher', $debug['matcher']);
        self::assertSame('projectSettings', $debug['source']);
    }

    /**
     * Sanitization of `Authorization` only fires when `headers` exists AND is
     * an array. A non-array `headers` value (string, int, …) MUST be passed
     * through verbatim. Pins the `&&` against a `LogicalAnd → ||` mutation
     * that would crash on `foreach (string)` because the ||-mutant proceeds
     * even when `is_array` is false.
     */
    public function testHookConfigDebugInfoLeavesNonArrayHeadersUntouched(): void
    {
        $config = new HookConfig('PreToolUse', 'Bash', [
            'type' => 'http', 'url' => 'https://x', 'headers' => 'not-an-array',
        ], 'project');
        $debug = $config->__debugInfo();
        self::assertSame('not-an-array', $this->asArray($debug['handler'])['headers']);
    }

    public function testLinterAggregatesFromMultipleRules(): void
    {
        $linter = new HookLinter([new Cch001BroadMatcherInPolicy(), new Cch002UnknownEventName()]);
        $configs = [
            new HookConfig('PreToolUse', '.*', [], 'policy'),
            new HookConfig('Mystery', '.*', [], 'project'),
        ];
        $findings = $linter->lint($configs, ConfigProfile::Policy);
        self::assertCount(3, $findings); // CCH001 fires twice, CCH002 once
    }

    public function testFindingInvariantMessageLengthCappedAndNoLongValueSubstring(): void
    {
        $rule = new Cch005SecretInUrlLiteral();
        $longValue = 'eyJ' . str_repeat('Y', 500);
        $config = new HookConfig('PreToolUse', 'Bash', [
            'type' => 'http', 'url' => "https://x?token={$longValue}",
        ], 'project');
        $findings = iterator_to_array($rule->inspect($config, ConfigProfile::Default));
        foreach ($findings as $f) {
            self::assertLessThanOrEqual(Finding::MAX_MESSAGE_BYTES, strlen($f->message));
            // Reject any 9-char substring of the input
            for ($i = 0; $i + 9 <= strlen($longValue); $i += 50) {
                self::assertStringNotContainsString(substr($longValue, $i, 9), $f->message);
            }
        }
    }

    public function testSeverityEnum(): void
    {
        self::assertSame('error', Severity::Error->value);
        self::assertSame('warning', Severity::Warning->value);
        self::assertSame('info', Severity::Info->value);
    }

    public function testConfigProfileEnum(): void
    {
        self::assertSame('observability', ConfigProfile::Observability->value);
        self::assertSame('policy', ConfigProfile::Policy->value);
        self::assertSame('default', ConfigProfile::Default->value);
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
