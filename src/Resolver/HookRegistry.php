<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Resolver;

final readonly class HookRegistry
{
    /**
     * @param list<HookRule> $rules
     */
    public function __construct(public array $rules = []) {}

    /**
     * @return list<HookRule>
     */
    public function forEvent(string $event): array
    {
        $matched = [];
        foreach ($this->rules as $rule) {
            if ($rule->event === $event) {
                $matched[] = $rule;
            }
        }

        return $matched;
    }
}
