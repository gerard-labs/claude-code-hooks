<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Builder;

final class MatcherBuilder
{
    /** @var list<HookHandlerBuilder> */
    private array $handlers = [];

    public function __construct(
        private readonly HookConfigBuilder $parent,
        private readonly string $event,
        private readonly string $matcher,
    ) {}

    public function commandHandler(string $command): HookHandlerBuilder
    {
        $handler = HookHandlerBuilder::command($this, $command);
        $this->handlers[] = $handler;

        return $handler;
    }

    public function httpHandler(string $url): HookHandlerBuilder
    {
        $handler = HookHandlerBuilder::http($this, $url);
        $this->handlers[] = $handler;

        return $handler;
    }

    /**
     * @return array{hooks: array<string, list<array{matcher: string, hooks: list<array<string, mixed>>}>>}
     */
    public function build(): array
    {
        return $this->parent->build();
    }

    public function event(string $name): MatcherBuilder
    {
        return $this->parent->event($name);
    }

    public function matcher(string $matcher): self
    {
        return $this->parent->matcher($matcher);
    }

    public function eventName(): string
    {
        return $this->event;
    }

    public function matcherValue(): string
    {
        return $this->matcher;
    }

    /**
     * @return list<HookHandlerBuilder>
     */
    public function handlers(): array
    {
        return $this->handlers;
    }
}
