<?php
// Lightweight request validation helpers for action handlers.

declare(strict_types=1);

/**
 * Return a trimmed string from request data.
 */
function request_string(array $data, string $key): string
{
    return trim((string) ($data[$key] ?? ''));
}

/**
 * Return an integer from request data.
 */
function request_int(array $data, string $key): int
{
    return (int) ($data[$key] ?? 0);
}

/**
 * Validate a required string field.
 */
function validate_required_string(string $value): bool
{
    return $value !== '';
}

/**
 * Validate a positive integer field.
 */
function validate_positive_int(int $value): bool
{
    return $value > 0;
}

/**
 * Validate datetime string in 'Y-m-d H:i:s' format.
 * Accepts common HTML date/datetime-local inputs.
 */
function validate_event_datetime(string $value): bool
{
    if ($value === '') {
        return false;
    }

    $formats = ['Y-m-d H:i:s', 'Y-m-d\TH:i:s', 'Y-m-d\TH:i', 'Y-m-d'];

    foreach ($formats as $format) {
        $dt = DateTime::createFromFormat($format, $value);
        if ($dt !== false && $dt->format($format) === $value) {
            return true;
        }
    }

    return false;
}

