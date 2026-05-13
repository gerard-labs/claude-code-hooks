<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Tests\Unit\Event;

use Gerard\ClaudeCodeHooks\Event\AbstractHookEvent;
use Gerard\ClaudeCodeHooks\Event\Family;
use Gerard\ClaudeCodeHooks\Event\PermissionMode;
use Gerard\ClaudeCodeHooks\Event\Session\SessionStartEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractHookEvent::class)]
final class AbstractHookEventTest extends TestCase
{
    public function testItCarriesSubagentContextWhenPresent(): void
    {
        $event = SessionStartEvent::fromPayload([
            'session_id' => 's', 'transcript_path' => '/p', 'cwd' => '/c',
            'permission_mode' => 'default', 'hook_event_name' => 'SessionStart',
            'source' => 'startup', 'model' => 'claude-sonnet-4-5',
            'agent_id' => 'a-1', 'agent_type' => 'Explore',
        ]);
        self::assertSame('a-1', $event->agentId);
        self::assertSame('Explore', $event->agentType);
        self::assertSame(Family::Session, $event->family());
    }

    public function testCommonFieldsArePopulated(): void
    {
        $event = SessionStartEvent::fromPayload([
            'session_id' => 's', 'transcript_path' => '/p', 'cwd' => '/c',
            'permission_mode' => 'plan', 'hook_event_name' => 'SessionStart',
            'source' => 'resume',
        ]);
        self::assertSame('s', $event->sessionId);
        self::assertSame('/p', $event->transcriptPath);
        self::assertSame('/c', $event->cwd);
        self::assertSame(PermissionMode::Plan, $event->permissionMode);
        self::assertSame('SessionStart', $event->hookEventName);
        self::assertNull($event->agentId);
    }
}
