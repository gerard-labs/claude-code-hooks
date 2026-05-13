<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Builder;

use LogicException;

/**
 * Fluent builder that emits a `settings.json`-compatible array.
 *
 * `build()` without `event()` raises `\LogicException` (AC-BLD-02).
 */
final class HookConfigBuilder
{
    /**
     * @var list<MatcherBuilder>
     */
    private array $matchers = [];
    private ?MatcherBuilder $current = null;
    private ?string $currentEvent = null;

    public static function create(): self
    {
        return new self();
    }

    public function event(string $name): MatcherBuilder
    {
        $this->currentEvent = $name;
        $this->current = new MatcherBuilder($this, $name, '');
        $this->matchers[] = $this->current;

        return $this->current;
    }

    public function matcher(string $matcher): MatcherBuilder
    {
        if ($this->currentEvent === null) {
            throw new LogicException('Call event() before matcher()');
        }
        $this->current = new MatcherBuilder($this, $this->currentEvent, $matcher);
        $this->matchers[] = $this->current;

        return $this->current;
    }

    /**
     * @return array{hooks: array<string, list<array{matcher: string, hooks: list<array<string, mixed>>}>>}
     */
    public function build(): array
    {
        if ($this->matchers === []) {
            throw new LogicException('HookConfigBuilder::build() called before event()');
        }

        /** @var array<string, list<array{matcher: string, hooks: list<array<string, mixed>>}>> $hooks */
        $hooks = [];

        foreach ($this->matchers as $matcherBuilder) {
            $event = $matcherBuilder->eventName();
            $matcher = $matcherBuilder->matcherValue();

            $handlerArrays = [];
            foreach ($matcherBuilder->handlers() as $handler) {
                $handlerArrays[] = $handler->toArray();
            }

            if ($handlerArrays === []) {
                continue;
            }

            $hooks[$event] ??= [];
            $hooks[$event][] = ['matcher' => $matcher, 'hooks' => $handlerArrays];
        }

        return ['hooks' => $hooks];
    }

    public function commandHandler(string $command): HookHandlerBuilder
    {
        if (!$this->current instanceof MatcherBuilder) {
            throw new LogicException('Call event()/matcher() before commandHandler()');
        }

        return $this->current->commandHandler($command);
    }

    public function httpHandler(string $url): HookHandlerBuilder
    {
        if (!$this->current instanceof MatcherBuilder) {
            throw new LogicException('Call event()/matcher() before httpHandler()');
        }

        return $this->current->httpHandler($url);
    }
}
