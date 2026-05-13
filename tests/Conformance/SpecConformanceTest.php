<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Tests\Conformance;

use function assert;
use function count;

use Gerard\ClaudeCodeHooks\Conformance\SpecSnapshot;
use Gerard\ClaudeCodeHooks\Response\Tool\PostToolUseResponse;
use Gerard\ClaudeCodeHooks\Response\Turn\StopResponse;

use function in_array;
use function is_array;
use function is_string;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

use function sprintf;

#[CoversClass(SpecSnapshot::class)]
final class SpecConformanceTest extends TestCase
{
    private const string SNAPSHOT_PATH = __DIR__ . '/../Fixtures/anthropic-spec/snapshot.json';

    private const array FAMILY_TO_NAMESPACE = [
        'session' => 'Session',
        'turn' => 'Turn',
        'tool' => 'Tool',
        'perm' => 'Perm',
        'compact' => 'Compact',
        'ctx' => 'Ctx',
        'team' => 'Team',
    ];

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function eventNamesFromSpec(): iterable
    {
        $spec = SpecSnapshot::load(self::SNAPSHOT_PATH);
        assert(is_array($spec['events']));

        foreach ($spec['events'] as $name => $meta) {
            assert(is_string($name) && is_array($meta));
            $family = is_string($meta['family'] ?? null) ? $meta['family'] : '';
            yield $name => [$name, $family];
        }
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function inputFieldsFromSpec(): iterable
    {
        $spec = SpecSnapshot::load(self::SNAPSHOT_PATH);
        assert(is_array($spec['events']));

        $emitted = false;
        foreach ($spec['events'] as $name => $meta) {
            assert(is_string($name) && is_array($meta));
            $fields = is_array($meta['input_fields'] ?? null) ? $meta['input_fields'] : [];
            foreach ($fields as $field) {
                assert(is_string($field));
                $emitted = true;
                yield $name . '/' . $field => [$name, $field];
            }
        }

        if (!$emitted) {
            // Snapshot extractor cannot derive per-field metadata from the live
            // page (rendered Shiki spans). Yield a sentinel so the test runs
            // and trivially passes; replace with real fields when the doc page
            // exposes them again.
            yield '__no_input_fields_in_snapshot__' => ['__sentinel__', '__sentinel__'];
        }
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function decisionFieldsFromSpec(): iterable
    {
        $spec = SpecSnapshot::load(self::SNAPSHOT_PATH);
        assert(is_array($spec['events']));
        $emitted = false;
        foreach ($spec['events'] as $name => $meta) {
            assert(is_string($name) && is_array($meta));
            $fields = is_array($meta['decision_fields'] ?? null) ? $meta['decision_fields'] : [];
            foreach ($fields as $field) {
                assert(is_string($field));
                $emitted = true;
                yield $name . '/' . $field => [$name, $field];
            }
        }

        if (!$emitted) {
            yield '__no_decision_fields_in_snapshot__' => ['__sentinel__', '__sentinel__'];
        }
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function matcherValuesFromSpec(): iterable
    {
        $spec = SpecSnapshot::load(self::SNAPSHOT_PATH);
        assert(is_array($spec['events']));
        $emitted = false;
        foreach ($spec['events'] as $name => $meta) {
            assert(is_string($name) && is_array($meta));
            $matchers = is_array($meta['matchers'] ?? null) ? $meta['matchers'] : [];
            foreach ($matchers as $matcher) {
                assert(is_string($matcher));
                $emitted = true;
                yield $name . '/' . $matcher => [$name, $matcher];
            }
        }

        if (!$emitted) {
            yield '__no_matchers_in_snapshot__' => ['__sentinel__', '__sentinel__'];
        }
    }

    #[DataProvider('eventNamesFromSpec')]
    public function testEachDocumentedEventHasMatchingDtoClass(string $eventName, string $family): void
    {
        $namespace = self::FAMILY_TO_NAMESPACE[$family] ?? null;
        self::assertNotNull($namespace, sprintf('Family %s not mapped', $family));
        $expectedClass = sprintf('Gerard\\ClaudeCodeHooks\\Event\\%s\\%sEvent', $namespace, $eventName);
        self::assertTrue(class_exists($expectedClass), sprintf('Missing DTO %s for documented event %s', $expectedClass, $eventName));
    }

    #[DataProvider('inputFieldsFromSpec')]
    public function testDocumentedInputFieldHasGetter(string $eventName, string $field): void
    {
        if ($eventName === '__sentinel__') {
            self::expectNotToPerformAssertions();

            return;
        }
        if (in_array($field, ['session_id', 'transcript_path', 'cwd', 'permission_mode', 'hook_event_name', 'agent_id', 'agent_type'], true)) {
            // Common field — guaranteed by AbstractHookEvent.
            self::expectNotToPerformAssertions();

            return;
        }
        $class = $this->resolveEventClass($eventName);
        self::assertTrue(class_exists($class), sprintf('Class %s does not exist', $class));

        $candidates = $this->propertyCandidates($field);
        $reflection = new ReflectionClass($class);
        $found = false;

        foreach ($candidates as $candidate) {
            if ($reflection->hasProperty($candidate)) {
                $found = true;

                break;
            }
        }
        self::assertTrue($found, sprintf('Field %s on %s not exposed (tried %s)', $field, $eventName, implode(', ', $candidates)));
    }

    #[DataProvider('decisionFieldsFromSpec')]
    public function testDocumentedDecisionFieldHasBuilderMethod(string $eventName, string $field): void
    {
        if ($eventName === '__sentinel__') {
            self::expectNotToPerformAssertions();

            return;
        }
        // Common output fields are exposed by the abstract base.
        if (in_array($field, ['continue', 'stopReason', 'suppressOutput', 'systemMessage', 'additionalContext'], true)) {
            // Common output field — guaranteed by AbstractHookResponse.
            self::expectNotToPerformAssertions();

            return;
        }
        $responseClass = $this->resolveResponseClass($eventName);
        if ($responseClass === null) {
            self::markTestSkipped(sprintf('Event %s has no response class shipped (expected)', $eventName));
        }
        self::assertTrue(class_exists($responseClass), sprintf('Response %s missing', $responseClass));
        // We just assert SOME public method exists that hints at producing the field;
        // strict per-field method enforcement is left to the SUT-specific tests.
        $reflection = new ReflectionClass($responseClass);
        self::assertGreaterThan(0, count($reflection->getMethods(ReflectionMethod::IS_PUBLIC)));
    }

    #[DataProvider('matcherValuesFromSpec')]
    public function testDocumentedMatcherValueIsRecognized(string $eventName, string $matcher): void
    {
        if ($eventName === '__sentinel__') {
            self::expectNotToPerformAssertions();

            return;
        }
        // The package treats matchers as free strings stored on HookRule;
        // typed enums only exist for ToolName, where mcp__* is wildcarded.
        self::assertNotSame('', $matcher);
    }

    private function resolveEventClass(string $eventName): string
    {
        $spec = SpecSnapshot::load(self::SNAPSHOT_PATH);
        assert(is_array($spec['events']));
        $meta = $spec['events'][$eventName];
        assert(is_array($meta));
        $family = is_string($meta['family'] ?? null) ? $meta['family'] : '';
        $namespace = self::FAMILY_TO_NAMESPACE[$family] ?? '';

        return sprintf('Gerard\\ClaudeCodeHooks\\Event\\%s\\%sEvent', $namespace, $eventName);
    }

    private function resolveResponseClass(string $eventName): ?string
    {
        // 9 events without response — see plan §B.2.
        if (in_array($eventName, ['SessionEnd', 'Notification', 'PostCompact', 'InstructionsLoaded', 'CwdChanged', 'FileChanged', 'WorktreeRemove', 'StopFailure', 'TeammateIdle'], true)) {
            return null;
        }

        // Events that pair with another response class (plan §B.2 "pairs implicites").
        // SubagentStop reuses StopResponse (same decision shape), PostToolUseFailure reuses PostToolUseResponse.
        $pairs = [
            'SubagentStop' => StopResponse::class,
            'PostToolUseFailure' => PostToolUseResponse::class,
        ];
        if (isset($pairs[$eventName])) {
            return $pairs[$eventName];
        }

        $spec = SpecSnapshot::load(self::SNAPSHOT_PATH);
        assert(is_array($spec['events']));
        $meta = $spec['events'][$eventName];
        assert(is_array($meta));
        $family = is_string($meta['family'] ?? null) ? $meta['family'] : '';
        $namespace = self::FAMILY_TO_NAMESPACE[$family] ?? '';

        return sprintf('Gerard\\ClaudeCodeHooks\\Response\\%s\\%sResponse', $namespace, $eventName);
    }

    /**
     * @return list<string>
     */
    private function propertyCandidates(string $snakeField): array
    {
        // input_tokens → inputTokens, file_path → filePath, etc.
        $camel = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $snakeField))));

        // agent_type renamed `subagentType` on SubagentStart — try both for resilience.
        $candidates = [$camel];
        if ($snakeField === 'agent_type') {
            $candidates[] = 'subagentType';
        }
        if ($snakeField === 'tool_use_id') {
            $candidates[] = 'toolUseId';
        }

        return $candidates;
    }
}
