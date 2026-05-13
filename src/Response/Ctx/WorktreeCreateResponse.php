<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Response\Ctx;

use function assert;

use Gerard\ClaudeCodeHooks\Response\AbstractHookResponse;

use function is_array;

use Override;

final class WorktreeCreateResponse extends AbstractHookResponse
{
    private ?string $worktreePath = null;

    public static function with(string $worktreePath): self
    {
        $r = new self();
        $r->worktreePath = $worktreePath;

        return $r;
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(): array
    {
        $payload = parent::toArray();
        if ($this->worktreePath !== null) {
            $hookSpecific = $payload['hookSpecificOutput'] ?? [];
            assert(is_array($hookSpecific));
            /** @var array<string, mixed> $hookSpecific */
            $hookSpecific['worktreePath'] = $this->worktreePath;
            $payload['hookSpecificOutput'] = $hookSpecific;
        }

        return $payload;
    }
}
