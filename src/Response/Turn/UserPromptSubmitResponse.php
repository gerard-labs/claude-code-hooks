<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Response\Turn;

use function assert;

use Gerard\ClaudeCodeHooks\Response\AbstractHookResponse;

use function is_array;

use Override;

final class UserPromptSubmitResponse extends AbstractHookResponse
{
    private ?string $decision = null;
    private ?string $reason = null;
    private ?string $sessionTitle = null;

    public static function pass(): self
    {
        return new self();
    }

    public static function block(string $reason): self
    {
        $r = new self();
        $r->decision = 'block';
        $r->reason = $reason;

        return $r;
    }

    public function withSessionTitle(string $title): self
    {
        $clone = clone $this;
        $clone->sessionTitle = $title;

        return $clone;
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(): array
    {
        $payload = parent::toArray();
        if ($this->decision !== null) {
            $payload['decision'] = $this->decision;
        }
        if ($this->reason !== null) {
            $payload['reason'] = $this->reason;
        }
        if ($this->sessionTitle !== null) {
            $existing = $payload['hookSpecificOutput'] ?? [];
            assert(is_array($existing));
            /** @var array<string, mixed> $existing */
            $existing['sessionTitle'] = $this->sessionTitle;
            $payload['hookSpecificOutput'] = $existing;
        }

        return $payload;
    }
}
