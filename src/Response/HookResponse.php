<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Response;

interface HookResponse
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
