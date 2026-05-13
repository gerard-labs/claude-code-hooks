<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Response\Perm;

use function assert;

use Gerard\ClaudeCodeHooks\Response\AbstractHookResponse;

use function is_array;

use Override;

final class ElicitationResponse extends AbstractHookResponse
{
    private string $action = 'accept';
    /** @var array<string, mixed> */
    private array $content = [];

    /**
     * @param array<string, mixed> $content
     */
    public static function accept(array $content = []): self
    {
        $r = new self();
        $r->action = 'accept';
        $r->content = $content;

        return $r;
    }

    public static function decline(): self
    {
        $r = new self();
        $r->action = 'decline';

        return $r;
    }

    public static function cancel(): self
    {
        $r = new self();
        $r->action = 'cancel';

        return $r;
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(): array
    {
        $payload = parent::toArray();
        $hookSpecific = $payload['hookSpecificOutput'] ?? [];
        assert(is_array($hookSpecific));
        /** @var array<string, mixed> $hookSpecific */
        $hookSpecific['action'] = $this->action;
        if ($this->content !== []) {
            $hookSpecific['content'] = $this->content;
        }
        $payload['hookSpecificOutput'] = $hookSpecific;

        return $payload;
    }
}
