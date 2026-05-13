<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Response\Turn;

use Gerard\ClaudeCodeHooks\Response\AbstractHookResponse;
use Override;

final class UserPromptExpansionResponse extends AbstractHookResponse
{
    private ?string $decision = null;
    private ?string $reason = null;

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

        return $payload;
    }
}
