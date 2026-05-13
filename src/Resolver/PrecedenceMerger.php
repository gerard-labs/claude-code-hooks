<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Resolver;

/**
 * Stable-sorts a flat `HookRule` list by `HookSource::precedence()`. Rules
 * within the same source preserve insertion order. The output orders sources
 * least-precedent first → most-precedent last so callers can `array_reduce`
 * with "later wins" semantics if they want a single rule per matcher.
 */
final class PrecedenceMerger
{
    /**
     * @param list<HookRule> $rules
     *
     * @return list<HookRule>
     */
    public function merge(array $rules): array
    {
        $indexed = [];
        foreach ($rules as $position => $rule) {
            $indexed[] = [$rule, $position];
        }

        usort(
            $indexed,
            static function (array $a, array $b): int {
                $precedence = $a[0]->source->precedence() <=> $b[0]->source->precedence();
                if ($precedence !== 0) {
                    return $precedence;
                }

                return $a[1] <=> $b[1];
            },
        );

        $sorted = [];
        foreach ($indexed as [$rule, $_]) {
            $sorted[] = $rule;
        }

        return $sorted;
    }
}
