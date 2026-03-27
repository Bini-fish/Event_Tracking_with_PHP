<?php
// User-related data access functions.

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

/**
 * Find a user by email address.
 */
function user_find_by_email(string $email): ?array
{
    $pdo = get_pdo();

    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    return $user !== false ? $user : null;
}

/**
 * Find a user by primary key ID.
 */
function user_find_by_id(int $id): ?array
{
    $pdo = get_pdo();

    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $user = $stmt->fetch();

    return $user !== false ? $user : null;
}

/**
 * Create a new user (attendee or organizer).
 *
 * Returns the new user ID on success, or null on failure.
 */
function user_create(string $name, string $email, string $passwordHash, string $role = 'organizer'): ?int
{
    $pdo = get_pdo();

    $stmt = $pdo->prepare(
        'INSERT INTO users (name, email, password_hash, role, created_at)
         VALUES (:name, :email, :password_hash, :role, NOW())'
    );

    $ok = $stmt->execute([
        ':name'          => $name,
        ':email'         => $email,
        ':password_hash' => $passwordHash,
        ':role'          => $role,
    ]);

    if (!$ok) {
        return null;
    }

    return (int) $pdo->lastInsertId();
}

/**
 * Verify user credentials. Returns user row on success, null on failure.
 */
function user_authenticate(string $email, string $plainPassword): ?array
{
    $user = user_find_by_email($email);

    if (!$user) {
        return null;
    }

    if (!isset($user['password_hash']) || !password_verify($plainPassword, $user['password_hash'])) {
        return null;
    }

    return $user;
}

