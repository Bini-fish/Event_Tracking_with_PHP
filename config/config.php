<?php
// Application-wide configuration values (paths, environment, base URL).

declare(strict_types=1);

// Basic app settings.
const APP_NAME = 'City-Wide Event Tracking System';
const APP_ENV = 'development';

// Database settings – replace with real values in your local setup.
const DB_HOST = 'localhost';
const DB_NAME = 'city_events';
const DB_USER = 'root';
const DB_PASS = '';

// Base URL: always resolves to the public/ folder regardless of which
// PHP file is the entry point (index.php or an action file).
if (!defined('BASE_URL')) {
    $scheme     = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host       = $_SERVER['HTTP_HOST'] ?? 'localhost';

    // config.php lives in config/, so ../public is always the web root.
    $publicReal  = str_replace('\\', '/', (string) realpath(__DIR__ . '/../public'));
    $docRootReal = str_replace('\\', '/', (string) realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));

    if ($publicReal !== '' && $docRootReal !== '' && str_starts_with($publicReal, $docRootReal)) {
        $basePath = substr($publicReal, strlen($docRootReal));
    } else {
        // Fallback: derive from SCRIPT_NAME (only reliable when running through index.php).
        $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    }

    define('BASE_URL', $scheme . '://' . $host . $basePath . '/');
}

