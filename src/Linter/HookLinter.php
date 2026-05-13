<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Linter;

final readonly class HookLinter
{
    /**
     * @param list<LinterRule> $rules
     */
    public function __construct(public array $rules) {}

    /**
     * @param iterable<HookConfig> $configs
     *
     * @return list<Finding>
     */
    public function lint(iterable $configs, ConfigProfile $profile): array
    {
        $findings = [];
        foreach ($configs as $config) {
            foreach ($this->rules as $rule) {
                foreach ($rule->inspect($config, $profile) as $finding) {
                    $findings[] = $finding;
                }
            }
        }

        return $findings;
    }
}
