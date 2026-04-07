<?php
// Database connection: returns a shared PDO instance.

declare(strict_types=1);

require_once __DIR__ . '/config.php';

/**
 * Get a PDO connection to the application database.
 */
function get_pdo(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    // Add a conservative connect timeout so CLI scripts don't appear to hang
    // when MySQL isn't running or host resolution stalls.
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4;connect_timeout=5';

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_TIMEOUT            => 5,
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

    return $pdo;
}

