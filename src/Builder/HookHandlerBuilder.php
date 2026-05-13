<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Builder;

use Gerard\ClaudeCodeHooks\Exception\InvalidPayloadException;

use function is_array;
use function sprintf;

final class HookHandlerBuilder
{
    /**
     * @param array<string, mixed> $entry
     */
    private function __construct(private readonly MatcherBuilder $parent, private array $entry) {}

    public static function command(MatcherBuilder $parent, string $command): self
    {
        return new self($parent, ['type' => 'command', 'command' => $command]);
    }

    public static function http(MatcherBuilder $parent, string $url): self
    {
        return new self($parent, ['type' => 'http', 'url' => $url]);
    }

    /**
     * SEC-18 — `withHeader` only accepts `${VAR}` placeholders. Pass real
     * secrets through the future bundle layer (with `#[\SensitiveParameter]`).
     */
    public function withHeader(string $name, string $valuePlaceholder): self
    {
        if (preg_match('/^\s*(\$\{[A-Z_][A-Z0-9_]*\}\s*)+$/', $valuePlaceholder) !== 1) {
            throw InvalidPayloadException::unsupportedValue(
                'HookHandlerBuilder',
                sprintf('$.headers.%s', $name),
                'a "${VAR}" placeholder (real secrets belong in the bundle layer)',
            );
        }

        $headers = is_array($this->entry['headers'] ?? null) ? $this->entry['headers'] : [];
        $headers[$name] = $valuePlaceholder;
        $this->entry['headers'] = $headers;

        return $this;
    }

    public function withTimeout(int $seconds): self
    {
        $this->entry['timeout'] = $seconds;

        return $this;
    }

    public function async(bool $async = true): self
    {
        $this->entry['async'] = $async;

        return $this;
    }

    public function event(string $name): MatcherBuilder
    {
        return $this->parent->event($name);
    }

    public function matcher(string $matcher): MatcherBuilder
    {
        return $this->parent->matcher($matcher);
    }

    /**
     * @return array{hooks: array<string, list<array{matcher: string, hooks: list<array<string, mixed>>}>>}
     */
    public function build(): array
    {
        return $this->parent->build();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->entry;
    }
}
