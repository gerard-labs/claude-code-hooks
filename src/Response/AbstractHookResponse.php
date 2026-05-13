<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Response;

use Override;

/**
 * Carries the five common response fields documented for every hook
 * (OBSERVABILITY_SPEC §3.1) plus the `hookSpecificOutput` envelope.
 *
 * The class is non-readonly so concrete responses can use the `with*` builder
 * pattern to return a new instance with one field changed (immutable in
 * effect — every `with*` produces a clone).
 */
abstract class AbstractHookResponse implements HookResponse
{
    protected ?bool $continue = null;
    protected ?string $stopReason = null;
    protected ?bool $suppressOutput = null;
    protected ?string $systemMessage = null;
    protected ?string $additionalContext = null;

    /**
     * @var array<string, mixed>
     */
    protected array $hookSpecificOutput = [];

    final public function withContinue(bool $continue, ?string $stopReason = null): static
    {
        $clone = clone $this;
        $clone->continue = $continue;
        if ($stopReason !== null) {
            $clone->stopReason = $stopReason;
        }

        return $clone;
    }

    final public function withSuppressOutput(bool $suppress): static
    {
        $clone = clone $this;
        $clone->suppressOutput = $suppress;

        return $clone;
    }

    final public function withSystemMessage(string $message): static
    {
        $clone = clone $this;
        $clone->systemMessage = $message;

        return $clone;
    }

    final public function withAdditionalContext(string $context): static
    {
        $clone = clone $this;
        $clone->additionalContext = $context;

        return $clone;
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(): array
    {
        $payload = [];

        if ($this->continue !== null) {
            $payload['continue'] = $this->continue;
        }
        if ($this->stopReason !== null) {
            $payload['stopReason'] = $this->stopReason;
        }
        if ($this->suppressOutput !== null) {
            $payload['suppressOutput'] = $this->suppressOutput;
        }
        if ($this->systemMessage !== null) {
            $payload['systemMessage'] = $this->systemMessage;
        }
        if ($this->additionalContext !== null) {
            $payload['additionalContext'] = $this->additionalContext;
        }
        if ($this->hookSpecificOutput !== []) {
            $payload['hookSpecificOutput'] = $this->hookSpecificOutput;
        }

        return $payload;
    }

}
