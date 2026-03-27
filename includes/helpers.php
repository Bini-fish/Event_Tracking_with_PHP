<?php
// Small helper functions shared across the application.

declare(strict_types=1);

/**
 * Escape a string for safe HTML output.
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Build an absolute URL to a given page with optional query parameters.
 */
function url_for(string $page, array $params = []): string
{
    $params = array_merge(['page' => $page], array_filter($params, static fn($v) => $v !== null));
    $query = http_build_query($params);

    return BASE_URL . 'index.php' . ($query ? '?' . $query : '');
}

/**
 * Redirect to a given page and stop further script execution.
 */
function redirect(string $page, array $params = []): void
{
    header('Location: ' . url_for($page, $params));
    exit;
}

/**
 * Store a flash message in the session to show on the next page load.
 */
function set_flash(string $type, string $message): void
{
    $_SESSION['flash'][$type][] = $message;
}

/**
 * Retrieve and clear all flash messages from the session.
 */
function get_flashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);

    return $flashes;
}

