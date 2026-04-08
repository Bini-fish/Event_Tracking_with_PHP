<?php
// Database connection: returns a shared PDO instance.

declare(strict_types=1);

require_once __DIR__ . '/config.php';

/**
 * Optional SQL execution logger for audit and anomaly detection.
 */
class LoggedPDOStatement extends PDOStatement
{
    protected function __construct()
    {
    }

    public function execute(?array $params = null): bool
    {
        $startedAt = microtime(true);
        $ok = parent::execute($params);
        $elapsedMs = (microtime(true) - $startedAt) * 1000;

        if (ENABLE_SQL_QUERY_LOGGING && ($elapsedMs >= SQL_SLOW_QUERY_MS || !$ok)) {
            $state = implode(',', $this->errorInfo() ?: []);
            error_log(
                sprintf(
                    'SQL_MONITOR duration_ms=%.2f slow_threshold_ms=%d ok=%s state=%s query=%s',
                    $elapsedMs,
                    SQL_SLOW_QUERY_MS,
                    $ok ? '1' : '0',
                    $state,
                    preg_replace('/\s+/', ' ', (string) $this->queryString)
                )
            );
        }

        return $ok;
    }
}

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

    if (ENABLE_SQL_QUERY_LOGGING) {
        $options[PDO::ATTR_STATEMENT_CLASS] = [LoggedPDOStatement::class];
    }

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

    if (APP_ENV === 'production' && strtolower(DB_USER) === 'root') {
        error_log('Security warning: production DB connection uses root user. Use a least-privilege database account.');
    }

    return $pdo;
}

