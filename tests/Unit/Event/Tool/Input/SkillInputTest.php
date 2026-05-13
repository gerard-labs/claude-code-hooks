<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Tests\Unit\Event\Tool\Input;

use Gerard\ClaudeCodeHooks\Event\Tool\Input\SkillInput;
use Gerard\ClaudeCodeHooks\Exception\InvalidPayloadException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SkillInput::class)]
final class SkillInputTest extends TestCase
{
    public function test_it_round_trips_a_skill_invocation_with_args(): void
    {
        $input = SkillInput::fromArray(['skill' => 'superpowers-symfony:tdd-with-phpunit', 'args' => 'test-class FooTest']);

        self::assertSame('Skill', $input->toolName());
        self::assertSame('superpowers-symfony:tdd-with-phpunit', $input->skill);
        self::assertSame('test-class FooTest', $input->args);
    }

    public function test_args_is_optional_and_defaults_to_null(): void
    {
        $input = SkillInput::fromArray(['skill' => 'init']);

        self::assertNull($input->args);
    }

    public function test_it_rejects_payload_without_skill_key(): void
    {
        self::expectException(InvalidPayloadException::class);
        SkillInput::fromArray([]);
    }

    public function test_it_rejects_non_string_skill(): void
    {
        self::expectException(InvalidPayloadException::class);
        SkillInput::fromArray(['skill' => 42]);
    }

    public function test_non_string_args_is_silently_dropped(): void
    {
        $input = SkillInput::fromArray(['skill' => 'init', 'args' => ['not', 'a', 'string']]);

        self::assertNull($input->args);
    }
}
