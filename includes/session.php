<?php
// Session helpers: start session and expose simple auth utilities.

declare(strict_types=1);

/**
 * Start the PHP session if it has not started yet.
 */
function start_session_if_needed(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

start_session_if_needed();

/**
 * Log in a user by storing their id and role in the session.
 */
function login_user(array $user): void
{
    $_SESSION['user_id'] = $user['id'] ?? null;
    $_SESSION['user_role'] = $user['role'] ?? 'attendee';
}

/**
 * Log out the current user and clear their session data.
 */
function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

/**
 * Get the current logged-in user id or null if not logged in.
 */
function current_user_id(): ?int
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

/**
 * Get the current logged-in user role or null if not logged in.
 */
function current_user_role(): ?string
{
    return $_SESSION['user_role'] ?? null;
}

/**
 * Check if the current user has the given role.
 */
function user_has_role(string $role): bool
{
    return current_user_role() === $role;
}

/**
 * Generate and return a CSRF token for this session.
 */
function get_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Verify that the given CSRF token matches the session token.
 */
function verify_csrf_token(?string $token): bool
{
    if (empty($_SESSION['csrf_token']) || $token === null) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

