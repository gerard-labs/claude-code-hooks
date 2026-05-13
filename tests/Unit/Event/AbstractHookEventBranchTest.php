<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Tests\Unit\Event;

use Gerard\ClaudeCodeHooks\Event\AbstractHookEvent;
use Gerard\ClaudeCodeHooks\Event\Family;
use Gerard\ClaudeCodeHooks\Event\PermissionMode;
use Gerard\ClaudeCodeHooks\Exception\InvalidPayloadException;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractHookEvent::class)]
final class AbstractHookEventBranchTest extends TestCase
{
    public function testRequireStringRejectsMissing(): void
    {
        self::expectException(InvalidPayloadException::class);
        AbstractHookEventTestStub::callRequireString([], 'session_id');
    }

    public function testRequireStringRejectsWrongType(): void
    {
        self::expectException(InvalidPayloadException::class);
        AbstractHookEventTestStub::callRequireString(['x' => 42], 'x');
    }

    public function testOptionalStringReturnsNullForMissingNullOrWrongType(): void
    {
        self::assertNull(AbstractHookEventTestStub::callOptionalString([], 'x'));
        self::assertNull(AbstractHookEventTestStub::callOptionalString(['x' => null], 'x'));
        self::assertNull(AbstractHookEventTestStub::callOptionalString(['x' => 42], 'x'));
        self::assertSame('hi', AbstractHookEventTestStub::callOptionalString(['x' => 'hi'], 'x'));
    }
}

/** @internal Test-only subclass to expose the protected static helpers. */
final readonly class AbstractHookEventTestStub extends AbstractHookEvent
{
    public function __construct()
    {
        parent::__construct('s', '/p', '/c', PermissionMode::Default, 'X');
    }

    /**
     * @param array<array-key, mixed> $payload
     */
    public static function callRequireString(array $payload, string $key): string
    {
        return self::requireString($payload, $key, 'test');
    }

    /**
     * @param array<array-key, mixed> $payload
     */
    public static function callOptionalString(array $payload, string $key): ?string
    {
        return self::optionalString($payload, $key);
    }

    #[Override]
    public function family(): Family
    {
        return Family::Unknown;
    }
}
