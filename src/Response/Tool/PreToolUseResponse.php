<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Response\Tool;

use Gerard\ClaudeCodeHooks\Event\Tool\Input\ToolInput;
use Gerard\ClaudeCodeHooks\Response\AbstractHookResponse;

use function is_array;

use JsonSerializable;

/**
 * `PreToolUse` documented decisions: `allow|deny|ask|defer` (cf. OBSERVABILITY_SPEC §3.1).
 *
 * The decision lives under `hookSpecificOutput` per the spec:
 *
 * ```json
 * {"hookSpecificOutput": {"permissionDecision": "deny", "permissionDecisionReason": "..."}}
 * ```
 */
final class PreToolUseResponse extends AbstractHookResponse
{
    private function __construct(string $decision, ?string $reason)
    {
        $output = ['permissionDecision' => $decision];
        if ($reason !== null) {
            $output['permissionDecisionReason'] = $reason;
        }
        $this->hookSpecificOutput = $output;
    }

    public static function allow(?string $reason = null): self
    {
        return new self('allow', $reason);
    }

    public static function deny(string $reason): self
    {
        return new self('deny', $reason);
    }

    public static function ask(?string $reason = null): self
    {
        return new self('ask', $reason);
    }

    public static function defer(?string $reason = null): self
    {
        return new self('defer', $reason);
    }

    public function withUpdatedInput(ToolInput $input): self
    {
        $clone = clone $this;
        $output = $clone->hookSpecificOutput;
        $payload = $input instanceof JsonSerializable ? $input->jsonSerialize() : (array) $input;
        if (is_array($payload)) {
            /** @var array<string, mixed> $payload */
            $output['updatedInput'] = $payload;
        }
        $clone->hookSpecificOutput = $output;

        return $clone;
    }
}
