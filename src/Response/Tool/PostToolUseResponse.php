<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Response\Tool;

use Gerard\ClaudeCodeHooks\Response\AbstractHookResponse;
use Override;

/**
 * `PostToolUse` documented decision: `decision: "block"` + reason (stops Claude).
 */
final class PostToolUseResponse extends AbstractHookResponse
{
    private ?string $decision = null;
    private ?string $reason = null;

    public static function noop(): self
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
