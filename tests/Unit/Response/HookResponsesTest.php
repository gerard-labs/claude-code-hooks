<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Tests\Unit\Response;

use Gerard\ClaudeCodeHooks\Event\Tool\Input\BashInput;
use Gerard\ClaudeCodeHooks\Response\AbstractHookResponse;
use Gerard\ClaudeCodeHooks\Response\Compact\PreCompactResponse;
use Gerard\ClaudeCodeHooks\Response\Ctx\ConfigChangeResponse;
use Gerard\ClaudeCodeHooks\Response\Ctx\WorktreeCreateResponse;
use Gerard\ClaudeCodeHooks\Response\Perm\ElicitationResponse;
use Gerard\ClaudeCodeHooks\Response\Perm\ElicitationResultResponse;
use Gerard\ClaudeCodeHooks\Response\Perm\PermissionDeniedResponse;
use Gerard\ClaudeCodeHooks\Response\Perm\PermissionRequestResponse;
use Gerard\ClaudeCodeHooks\Response\Session\SessionStartResponse;
use Gerard\ClaudeCodeHooks\Response\Session\SetupResponse;
use Gerard\ClaudeCodeHooks\Response\Team\TaskCompletedResponse;
use Gerard\ClaudeCodeHooks\Response\Team\TaskCreatedResponse;
use Gerard\ClaudeCodeHooks\Response\Tool\PostToolBatchResponse;
use Gerard\ClaudeCodeHooks\Response\Tool\PostToolUseResponse;
use Gerard\ClaudeCodeHooks\Response\Tool\PreToolUseResponse;
use Gerard\ClaudeCodeHooks\Response\Turn\StopResponse;
use Gerard\ClaudeCodeHooks\Response\Turn\SubagentStartResponse;
use Gerard\ClaudeCodeHooks\Response\Turn\UserPromptExpansionResponse;
use Gerard\ClaudeCodeHooks\Response\Turn\UserPromptSubmitResponse;

use function is_array;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractHookResponse::class)]
#[CoversClass(PreToolUseResponse::class)]
#[CoversClass(PostToolUseResponse::class)]
#[CoversClass(PostToolBatchResponse::class)]
#[CoversClass(UserPromptSubmitResponse::class)]
#[CoversClass(UserPromptExpansionResponse::class)]
#[CoversClass(StopResponse::class)]
#[CoversClass(SubagentStartResponse::class)]
#[CoversClass(PermissionRequestResponse::class)]
#[CoversClass(PermissionDeniedResponse::class)]
#[CoversClass(ElicitationResponse::class)]
#[CoversClass(ElicitationResultResponse::class)]
#[CoversClass(PreCompactResponse::class)]
#[CoversClass(ConfigChangeResponse::class)]
#[CoversClass(WorktreeCreateResponse::class)]
#[CoversClass(TaskCreatedResponse::class)]
#[CoversClass(TaskCompletedResponse::class)]
#[CoversClass(SetupResponse::class)]
#[CoversClass(SessionStartResponse::class)]
final class HookResponsesTest extends TestCase
{
    public function testPreToolUseDenySerializesToDocumentedShape(): void
    {
        $payload = PreToolUseResponse::deny('forbidden')->toArray();
        $hook = $this->asArray($payload['hookSpecificOutput']);
        self::assertSame('deny', $hook['permissionDecision']);
        self::assertSame('forbidden', $hook['permissionDecisionReason']);
    }

    public function testPreToolUseAllowAllowsOptionalReason(): void
    {
        self::assertSame('allow', $this->asArray(PreToolUseResponse::allow()->toArray()['hookSpecificOutput'])['permissionDecision']);
        self::assertSame('allow', $this->asArray(PreToolUseResponse::allow('safe')->toArray()['hookSpecificOutput'])['permissionDecision']);
    }

    public function testPreToolUseAskAndDefer(): void
    {
        self::assertSame('ask', $this->asArray(PreToolUseResponse::ask()->toArray()['hookSpecificOutput'])['permissionDecision']);
        self::assertSame('defer', $this->asArray(PreToolUseResponse::defer()->toArray()['hookSpecificOutput'])['permissionDecision']);
    }

    public function testPreToolUseCarriesUpdatedInput(): void
    {
        $input = BashInput::fromArray(['command' => 'ls -la']);
        $payload = PreToolUseResponse::allow()->withUpdatedInput($input)->toArray();
        self::assertArrayHasKey('updatedInput', $this->asArray($payload['hookSpecificOutput']));
    }

    public function testPreToolUseInheritsCommonFields(): void
    {
        $payload = PreToolUseResponse::allow()
            ->withSystemMessage('msg')
            ->withSuppressOutput(true)
            ->withAdditionalContext('ctx')
            ->withContinue(false, 'why')
            ->toArray();
        self::assertSame('msg', $payload['systemMessage']);
        self::assertTrue($payload['suppressOutput']);
        self::assertSame('ctx', $payload['additionalContext']);
        self::assertFalse($payload['continue']);
        self::assertSame('why', $payload['stopReason']);
    }

    public function testPostToolUseBlock(): void
    {
        $payload = PostToolUseResponse::block('nope')->toArray();
        self::assertSame('block', $payload['decision']);
        self::assertSame('nope', $payload['reason']);
    }

    public function testPostToolUseNoop(): void
    {
        self::assertSame([], PostToolUseResponse::noop()->toArray());
    }

    public function testPostToolBatchBlockAndNoop(): void
    {
        self::assertSame('block', PostToolBatchResponse::block('x')->toArray()['decision']);
        self::assertSame([], PostToolBatchResponse::noop()->toArray());
    }

    public function testUserPromptSubmitFields(): void
    {
        $payload = UserPromptSubmitResponse::pass()->withSessionTitle('T')->toArray();
        self::assertSame('T', $this->asArray($payload['hookSpecificOutput'])['sessionTitle']);
        self::assertSame('block', UserPromptSubmitResponse::block('r')->toArray()['decision']);
    }

    public function testUserPromptExpansion(): void
    {
        self::assertSame([], UserPromptExpansionResponse::pass()->toArray());
        self::assertSame('block', UserPromptExpansionResponse::block('r')->toArray()['decision']);
    }

    public function testStopEmitsOnlyDocumentedFields(): void
    {
        $payload = StopResponse::block('keep going')->toArray();
        self::assertSame(['decision' => 'block', 'reason' => 'keep going'], $payload);
    }

    public function testStopPass(): void
    {
        self::assertSame([], StopResponse::pass()->toArray());
    }

    public function testSubagentStartNoop(): void
    {
        self::assertSame([], SubagentStartResponse::noop()->toArray());
    }

    public function testPermissionRequestAllowDenyMessage(): void
    {
        $allow = PermissionRequestResponse::allow()->withMessage('go')->toArray();
        $allowDecision = $this->asArray($this->asArray($allow['hookSpecificOutput'])['decision']);
        self::assertSame('allow', $allowDecision['behavior']);
        self::assertSame('go', $allow['message']);
        $denyDecision = $this->asArray($this->asArray(PermissionRequestResponse::deny()->toArray()['hookSpecificOutput'])['decision']);
        self::assertSame('deny', $denyDecision['behavior']);
    }

    public function testPermissionRequestUpdatedInputAndPermissions(): void
    {
        $payload = PermissionRequestResponse::allow()
            ->withUpdatedInput(['x' => 1])
            ->withUpdatedPermissions([['type' => 'addRules']])
            ->toArray();
        $decision = $this->asArray($this->asArray($payload['hookSpecificOutput'])['decision']);
        self::assertSame(['x' => 1], $decision['updatedInput']);
        self::assertCount(1, $this->asArray($decision['updatedPermissions']));
    }

    public function testPermissionDeniedRetry(): void
    {
        self::assertTrue($this->asArray(PermissionDeniedResponse::retry()->toArray()['hookSpecificOutput'])['retry']);
        self::assertSame([], PermissionDeniedResponse::noRetry()->toArray());
    }

    public function testElicitationFlavours(): void
    {
        self::assertSame('accept', $this->asArray(ElicitationResponse::accept(['k' => 'v'])->toArray()['hookSpecificOutput'])['action']);
        self::assertSame('decline', $this->asArray(ElicitationResponse::decline()->toArray()['hookSpecificOutput'])['action']);
        self::assertSame('cancel', $this->asArray(ElicitationResponse::cancel()->toArray()['hookSpecificOutput'])['action']);
    }

    public function testElicitationResultFlavours(): void
    {
        self::assertSame('accept', $this->asArray(ElicitationResultResponse::accept(['x' => 1])->toArray()['hookSpecificOutput'])['action']);
        self::assertSame('decline', $this->asArray(ElicitationResultResponse::decline()->toArray()['hookSpecificOutput'])['action']);
        self::assertSame('cancel', $this->asArray(ElicitationResultResponse::cancel()->toArray()['hookSpecificOutput'])['action']);
    }

    public function testPreCompactBlockAndPass(): void
    {
        self::assertSame('block', PreCompactResponse::block('keep')->toArray()['decision']);
        self::assertSame([], PreCompactResponse::pass()->toArray());
    }

    public function testConfigChangeBlockAndPass(): void
    {
        self::assertSame('block', ConfigChangeResponse::block('rev')->toArray()['decision']);
        self::assertSame([], ConfigChangeResponse::pass()->toArray());
    }

    public function testWorktreeCreatePath(): void
    {
        self::assertSame('/wt', $this->asArray(WorktreeCreateResponse::with('/wt')->toArray()['hookSpecificOutput'])['worktreePath']);
    }

    public function testTaskCreatedAndCompletedDecisions(): void
    {
        self::assertSame('block', TaskCreatedResponse::block('r')->toArray()['decision']);
        self::assertSame([], TaskCreatedResponse::pass()->toArray());
        self::assertSame('block', TaskCompletedResponse::block('r')->toArray()['decision']);
        self::assertSame([], TaskCompletedResponse::pass()->toArray());
    }

    public function testSessionAndSetupNoop(): void
    {
        self::assertSame([], SetupResponse::noop()->toArray());
        self::assertSame([], SessionStartResponse::noop()->toArray());
    }
    /**
     * Type-narrow a `mixed` value to an array so subsequent offset access
     * type-checks at PHPStan level 10. Fails the test if the value isn't an array.
     *
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
