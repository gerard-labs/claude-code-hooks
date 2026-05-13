<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Event\Tool\Input;

use function is_string;

use Override;

/**
 * Catch-all input shape for tools whose schema isn't modelled by a typed DTO
 * yet. Hosts both `mcp__<server>__<tool>` invocations and first-party tools
 * with no dedicated input class (e.g. `ToolSearch`, `Monitor`, `TaskStop`,
 * `ExitPlanMode`). The raw payload is exposed as an immutable map.
 */
final readonly class RawToolInput implements ToolInput
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public string $name,
        public array $payload = [],
    ) {}

    /**
     * @param array<array-key, mixed> $raw
     */
    public static function fromArray(string $toolName, array $raw): self
    {
        /** @var array<string, mixed> $normalized */
        $normalized = [];
        foreach ($raw as $key => $value) {
            if (is_string($key)) {
                $normalized[$key] = $value;
            }
        }

        return new self($toolName, $normalized);
    }

    #[Override]
    public function toolName(): string
    {
        return $this->name;
    }
}
