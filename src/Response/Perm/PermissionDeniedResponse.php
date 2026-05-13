<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Response\Perm;

use function assert;

use Gerard\ClaudeCodeHooks\Response\AbstractHookResponse;

use function is_array;

use Override;

final class PermissionDeniedResponse extends AbstractHookResponse
{
    private bool $retry = false;

    public static function noRetry(): self
    {
        return new self();
    }

    public static function retry(): self
    {
        $r = new self();
        $r->retry = true;

        return $r;
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(): array
    {
        $payload = parent::toArray();
        if ($this->retry) {
            $hookSpecific = $payload['hookSpecificOutput'] ?? [];
            assert(is_array($hookSpecific));
            /** @var array<string, mixed> $hookSpecific */
            $hookSpecific['retry'] = true;
            $payload['hookSpecificOutput'] = $hookSpecific;
        }

        return $payload;
    }
}
