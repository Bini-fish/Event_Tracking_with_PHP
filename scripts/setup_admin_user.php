<?php
/**
 * Create or update an admin account (CLI only).
 *
 * Passwords are stored as hashes, so you set a NEW password here.
 *
 * Usage (PowerShell):
 *   $env:ADMIN_PASSWORD = "your-secret"
 *   php scripts/setup_admin_user.php --email=you@example.com --name="Your Name"
 *   Remove-Item Env:ADMIN_PASSWORD
 *
 * Or interactive:
 *   php scripts/setup_admin_user.php --email=you@example.com --name="Your Name" --prompt
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/UserModel.php';

$options = getopt('', ['email:', 'name:', 'prompt']);

$email = isset($options['email']) ? trim((string) $options['email']) : '';
$name  = isset($options['name']) ? trim((string) $options['name']) : '';
$usePrompt = array_key_exists('prompt', $options);

if ($email === '' || $name === '') {
    fwrite(STDERR, "Usage: php scripts/setup_admin_user.php --email=you@example.com --name=\"Your Name\" [--prompt]\n");
    exit(1);
}

$password = null;
if ($usePrompt) {
    echo 'Enter admin password (input hidden on Unix; on Windows it may echo): ';
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
    $pdo = get_pdo();
    $existing = user_find_by_email($email);
} catch (PDOException $e) {
    fwrite(STDERR, "Database error: " . $e->getMessage() . "\n");
    fwrite(STDERR, "Check DB settings in config/config.php (DB_HOST/DB_NAME/DB_USER/DB_PASS) and ensure MySQL is running.\n");
    exit(1);
}

if ($existing) {
    $stmt = $pdo->prepare('UPDATE users SET name = :name, password_hash = :password_hash, role = :role WHERE id = :id');
    $stmt->execute([
        ':name'          => $name,
        ':password_hash' => $hash,
        ':role'          => 'admin',
        ':id'            => (int) $existing['id'],
    ]);
    echo "Updated existing user: {$email} (role=admin)\n";
} else {
    $id = user_create($name, $email, $hash, 'admin');
    if ($id === null) {
        fwrite(STDERR, "Could not create admin user.\n");
        exit(1);
    }
    echo "Created admin: {$email} (id={$id})\n";
}

echo "Done. You can log in via the web app.\n";
