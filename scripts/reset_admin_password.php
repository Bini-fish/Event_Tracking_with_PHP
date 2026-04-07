<?php
/**
 * Reset the admin user's password (CLI only).
 *
 * Passwords are stored as hashes, so they cannot be "retrieved".
 * This script updates the stored hash to a new value.
 *
 * Usage (PowerShell):
 *   $env:ADMIN_PASSWORD = "new-secret"
 *   php scripts/reset_admin_password.php
 *   Remove-Item Env:ADMIN_PASSWORD
 *
 * Or interactive:
 *   php scripts/reset_admin_password.php --prompt
 *
 * Optional:
 *   php scripts/reset_admin_password.php --email=admin@cityevents.local --prompt
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/UserModel.php';

const DEFAULT_ADMIN_EMAIL = 'admin@cityevents.local';

$options = getopt('', ['email::', 'prompt']);

$email = isset($options['email']) && trim((string) $options['email']) !== ''
    ? trim((string) $options['email'])
    : DEFAULT_ADMIN_EMAIL;

$usePrompt = array_key_exists('prompt', $options);

$password = null;
if ($usePrompt) {
    echo 'Enter NEW admin password (input hidden on Unix; on Windows it may echo): ';
    $password = rtrim((string) fgets(STDIN), "\r\n");
} else {
    $password = getenv('ADMIN_PASSWORD');
    if ($password === false || $password === '') {
        fwrite(STDERR, "Set ADMIN_PASSWORD in the environment, or run with --prompt\n");
        exit(1);
    }
}

if ($password === '') {
    fwrite(STDERR, "Password cannot be empty.\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
if ($hash === false) {
    fwrite(STDERR, "Could not hash password.\n");
    exit(1);
}

try {
    echo "Connecting to database...\n";
    $user = user_find_by_email($email);
} catch (PDOException $e) {
    fwrite(STDERR, "Database error: " . $e->getMessage() . "\n");
    fwrite(STDERR, "Check DB settings in config/config.php (DB_HOST/DB_NAME/DB_USER/DB_PASS) and ensure MySQL is running.\n");
    exit(1);
}
if (!$user) {
    fwrite(STDERR, "No user found with email: {$email}\n");
    fwrite(STDERR, "If you just imported seed data, ensure the database exists and run your seed/migrations first.\n");
    exit(1);
}

$pdo = get_pdo();
$stmt = $pdo->prepare('UPDATE users SET password_hash = :password_hash, role = :role WHERE id = :id');
$stmt->execute([
    ':password_hash' => $hash,
    ':role'          => 'admin',
    ':id'            => (int) $user['id'],
]);

echo "Reset password for {$email} (id=" . (int) $user['id'] . ")\n";
echo "Done. You can log in via the web app.\n";
