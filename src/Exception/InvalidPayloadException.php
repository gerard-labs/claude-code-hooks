<?php

declare(strict_types=1);

namespace Gerard\ClaudeCodeHooks\Exception;

use DomainException;

use function sprintf;

/**
 * Thrown when a payload supplied to a DTO factory or to a calculator is
 * structurally invalid (missing field, wrong type, unsupported enum value).
 *
 * Messages MUST NOT embed the raw payload — only the field name or JSON
 * Pointer that caused the failure (SEC-04, SEC-05, SEC-09).
 */
final class InvalidPayloadException extends DomainException implements HookException
{
    public static function missingField(string $context, string $fieldPointer): self
    {
        return new self(sprintf('Missing required field %s in %s', $fieldPointer, $context));
    }

    public static function wrongType(string $context, string $fieldPointer, string $expectedType): self
    {
        return new self(sprintf('Field %s in %s must be of type %s', $fieldPointer, $context, $expectedType));
    }

    public static function unsupportedValue(string $context, string $fieldPointer, string $allowedValuesDescription): self
    {
        return new self(sprintf(
            'Unsupported value for %s in %s — allowed: %s',
            $fieldPointer,
            $context,
            $allowedValuesDescription,
        ));
    }
}
