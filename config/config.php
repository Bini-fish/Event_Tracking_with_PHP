<?php
// Application-wide configuration values (paths, environment, base URL).

declare(strict_types=1);

// Basic app settings.
const APP_NAME = 'City-Wide Event Tracking System';
const APP_ENV  = 'development';

// Security flags (override with env vars in deployment).
defined('FORCE_HTTPS') || define('FORCE_HTTPS', (getenv('FORCE_HTTPS') ?: '0') === '1');
defined('ENABLE_SECURITY_HEADERS') || define('ENABLE_SECURITY_HEADERS', (getenv('ENABLE_SECURITY_HEADERS') ?: '1') === '1');
defined('ENABLE_SQL_QUERY_LOGGING') || define('ENABLE_SQL_QUERY_LOGGING', (getenv('ENABLE_SQL_QUERY_LOGGING') ?: '0') === '1');
defined('SQL_SLOW_QUERY_MS') || define('SQL_SLOW_QUERY_MS', (int) (getenv('SQL_SLOW_QUERY_MS') ?: 500));

// ── Event time constraints ────────────────────────────────────────────────────
// Minimum event duration in minutes (start → end must be at least this long).
const MIN_EVENT_DURATION_MINUTES = 30;
// Minimum gap (minutes) the same organiser must leave between their own events.
// Set to 0 to disable the buffer.
const ORGANIZER_BUFFER_MINUTES = 15;

// ── Rate limiting ─────────────────────────────────────────────────────────────
// Max login attempts per IP+user within RATE_LIMIT_WINDOW_SECONDS.
const RATE_LIMIT_MAX_LOGIN   = 10;
// Max RSVP/comment attempts per user per window.
const RATE_LIMIT_MAX_ACTION  = 20;
const RATE_LIMIT_WINDOW_SECONDS = 300; // 5 minutes

// Database settings – env vars take precedence; fallback to constants for local dev.
defined('DB_HOST') || define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
defined('DB_NAME') || define('DB_NAME', getenv('DB_NAME') ?: 'city_events');
defined('DB_USER') || define('DB_USER', getenv('DB_USER') ?: 'root');
defined('DB_PASS') || define('DB_PASS', getenv('DB_PASS') ?: '');

// Base URL: always resolves to the public/ folder regardless of which
// PHP file is the entry point (index.php or an action file).
if (!defined('BASE_URL')) {
    $forwardedProto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $forwardedProto === 'https';
    $scheme     = $isHttps ? 'https' : 'http';
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

/**
 * Apply transport and browser-level security controls for web requests.
 */
function bootstrap_request_security(): void
{
    if (PHP_SAPI === 'cli') {
        return;
    }

    $forwardedProto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $forwardedProto === 'https';

    if (FORCE_HTTPS && !$isHttps) {
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        header('Location: https://' . $host . $requestUri, true, 301);
        exit;
    }

    if (!ENABLE_SECURITY_HEADERS || headers_sent()) {
        return;
    }

    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Permissions-Policy: camera=(), microphone=(), geolocation=()");

    if ($isHttps) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

bootstrap_request_security();

