<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Linter;

/**
 * SEC-08 — `Finding::$message` MUST NEVER embed the suspected secret value.
 * The invariant test (`FindingInvariantTest`) guarantees the message stays
 * under 256 characters and never contains a substring of >8 characters from
 * the offending input.
 */
final readonly class Finding
{
    public const int MAX_MESSAGE_BYTES = 256;

    public function __construct(
        public Severity $severity,
        public string $code,
        public string $message,
        public string $jsonPointer,
    ) {}
}
