<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Resolver;

/**
 * When the policy settings declare `allowManagedHooksOnly: true`, the
 * resolver must drop every rule whose source is not `Policy` and whose
 * `managed` flag is not set.
 */
final readonly class ManagedHooksFilter
{
    public function __construct(private bool $allowManagedHooksOnly) {}

    /**
     * @param list<HookRule> $rules
     *
     * @return list<HookRule>
     */
    public function apply(array $rules): array
    {
        if (!$this->allowManagedHooksOnly) {
            return $rules;
        }

        $kept = [];
        foreach ($rules as $rule) {
            if ($rule->source === HookSource::Policy || $rule->managed) {
                $kept[] = $rule;
            }
        }

        return $kept;
    }
}
