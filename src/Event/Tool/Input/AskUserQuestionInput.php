<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Event\Tool\Input;

use function array_key_exists;
use function is_array;

use Override;

final readonly class AskUserQuestionInput implements ToolInput
{
    /**
     * @param list<array<string, mixed>> $questions
     * @param list<array<string, mixed>> $answers
     */
    public function __construct(
        public array $questions = [],
        public array $answers = [],
    ) {}

    /**
     * @param array<array-key, mixed> $raw
     */
    public static function fromArray(array $raw): self
    {
        return new self(
            questions: self::arrayList($raw, 'questions'),
            answers: self::arrayList($raw, 'answers'),
        );
    }

    #[Override]
    public function toolName(): string
    {
        return 'AskUserQuestion';
    }

    /**
     * @param array<array-key, mixed> $raw
     *
     * @return list<array<string, mixed>>
     */
    private static function arrayList(array $raw, string $key): array
    {
        if (!array_key_exists($key, $raw) || !is_array($raw[$key])) {
            return [];
        }

        $out = [];
        foreach ($raw[$key] as $entry) {
            if (is_array($entry)) {
                /** @var array<string, mixed> $entry */
                $out[] = $entry;
            }
        }

        return $out;
    }
}
