<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Exception;

use RuntimeException;

/**
 * Raised by the Anthropic spec extractor when fetching or parsing fails
 * after retries. The extractor maps this to CLI exit code 2 — distinct from
 * a "drift" (exit 1).
 */
final class ExtractionFailedException extends RuntimeException implements HookException {}
