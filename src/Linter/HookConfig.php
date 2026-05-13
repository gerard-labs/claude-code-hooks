<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Linter;

use function array_key_exists;
use function is_array;
use function is_string;

/**
 * Snapshot of a single hook configuration entry that the linter inspects.
 *
 * SEC-09 — `__debugInfo()` redacts `headers.Authorization`. The raw map is
 * still available via the public properties for legitimate inspection by
 * other linter rules — but printing the object never leaks the value.
 */
final readonly class HookConfig
{
    /**
     * @param array<string, mixed> $handler
     */
    public function __construct(
        public string $event,
        public string $matcher,
        public array $handler,
        public string $source,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        $handler = $this->handler;

        if (array_key_exists('headers', $handler) && is_array($handler['headers'])) {
            $sanitized = [];
            foreach ($handler['headers'] as $name => $value) {
                if (is_string($name) && strcasecmp($name, 'Authorization') === 0) {
                    $sanitized[$name] = '***redacted***';

                    continue;
                }
                $sanitized[$name] = $value;
            }
            $handler['headers'] = $sanitized;
        }

        return [
            'event' => $this->event,
            'matcher' => $this->matcher,
            'source' => $this->source,
            'handler' => $handler,
        ];
    }
}
