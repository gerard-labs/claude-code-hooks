<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Resolver;

use function array_key_exists;
use function is_array;
use function is_bool;
use function is_string;

final readonly class HookConfigResolver
{
    public function __construct(
        private SettingsLoader $loader,
        private PrecedenceMerger $merger = new PrecedenceMerger(),
    ) {}

    public function resolve(): HookRegistry
    {
        $rules = [];
        $allowManagedHooksOnly = false;

        foreach (HookSource::cases() as $source) {
            $settings = $this->loader->load($source);

            if ($settings === null) {
                continue;
            }

            if ($source === HookSource::Policy
                && array_key_exists('allowManagedHooksOnly', $settings)
                && is_bool($settings['allowManagedHooksOnly'])
            ) {
                $allowManagedHooksOnly = $settings['allowManagedHooksOnly'];
            }
            if (!array_key_exists('hooks', $settings) || !is_array($settings['hooks'])) {
                continue;
            }

            foreach ($settings['hooks'] as $eventName => $matchers) {
                if (!is_string($eventName) || !is_array($matchers)) {
                    continue;
                }
                foreach ($matchers as $matcherEntry) {
                    if (!is_array($matcherEntry)) {
                        continue;
                    }

                    $matcherValue = is_string($matcherEntry['matcher'] ?? null)
                        ? $matcherEntry['matcher']
                        : '';

                    $handlers = is_array($matcherEntry['hooks'] ?? null) ? $matcherEntry['hooks'] : [];

                    foreach ($handlers as $handler) {
                        if (!is_array($handler)) {
                            continue;
                        }

                        $managed = array_key_exists('managed', $handler) && $handler['managed'] === true;
                        /** @var array<string, mixed> $handler */
                        $rules[] = new HookRule(
                            event: $eventName,
                            matcher: $matcherValue,
                            handler: $handler,
                            source: $source,
                            managed: $managed,
                        );
                    }
                }
            }
        }

        $filter = new ManagedHooksFilter($allowManagedHooksOnly);
        $rules = $filter->apply($rules);
        $rules = $this->merger->merge($rules);

        return new HookRegistry($rules);
    }
}
