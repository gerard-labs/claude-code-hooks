<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Response\Perm;

use function assert;

use Gerard\ClaudeCodeHooks\Response\AbstractHookResponse;

use function is_array;

use Override;

final class PermissionRequestResponse extends AbstractHookResponse
{
    private ?string $message = null;
    /** @var array<string, mixed> */
    private array $decision = [];

    public static function allow(): self
    {
        $r = new self();
        $r->decision = ['behavior' => 'allow'];

        return $r;
    }

    public static function deny(): self
    {
        $r = new self();
        $r->decision = ['behavior' => 'deny'];

        return $r;
    }

    /**
     * @param array<string, mixed> $updatedInput
     */
    public function withUpdatedInput(array $updatedInput): self
    {
        $clone = clone $this;
        $clone->decision['updatedInput'] = $updatedInput;

        return $clone;
    }

    /**
     * @param list<array<string, mixed>> $updatedPermissions
     */
    public function withUpdatedPermissions(array $updatedPermissions): self
    {
        $clone = clone $this;
        $clone->decision['updatedPermissions'] = $updatedPermissions;

        return $clone;
    }

    public function withMessage(string $message): self
    {
        $clone = clone $this;
        $clone->message = $message;

        return $clone;
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
        if ($this->decision !== []) {
            $hookSpecific['decision'] = $this->decision;
        }
        if ($hookSpecific !== []) {
            $payload['hookSpecificOutput'] = $hookSpecific;
        }
        if ($this->message !== null) {
            $payload['message'] = $this->message;
        }

        return $payload;
    }
}
