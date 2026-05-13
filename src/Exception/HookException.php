<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Exception;

use Throwable;

/**
 * Marker interface for every exception thrown by this package.
 *
 * Implementations MUST also extend an SPL exception so callers can rely on
 * the standard exception hierarchy (try/catch on \Throwable still works) while
 * being able to discriminate package-specific failures via this interface.
 */
interface HookException extends Throwable {}
