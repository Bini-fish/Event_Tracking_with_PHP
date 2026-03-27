<?php
// Handle login form submission and start a user session.

declare(strict_types=1);

require_once __DIR__ . '/../../../includes/session.php';
require_once __DIR__ . '/../../../includes/helpers.php';
require_once __DIR__ . '/../../../includes/validation.php';
require_once __DIR__ . '/../../../models/UserModel.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('login');
}

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    set_flash('error', 'Invalid form submission.');
    redirect('login');
}

$email    = request_string($_POST, 'email');
$password = $_POST['password'] ?? '';

if (!validate_required_string($email) || !validate_required_string((string) $password)) {
    set_flash('error', 'Email and password are required.');
    redirect('login');
}

$user = user_authenticate($email, $password);

if (!$user) {
    set_flash('error', 'Invalid credentials.');
    redirect('login');
}

login_user($user);

$redirectTo = $_POST['redirect'] ?? null;

if (is_string($redirectTo) && $redirectTo !== '') {
    $parts = parse_url($redirectTo);

    // Only allow in-app relative paths to avoid open redirects.
    $isSafeRelativePath = $parts !== false
        && !isset($parts['scheme'], $parts['host'])
        && str_starts_with($redirectTo, '/')
        && !str_starts_with($redirectTo, '//');

    if ($isSafeRelativePath) {
        header('Location: ' . $redirectTo);
        exit;
    }
}

if ($user['role'] === 'admin') {
    redirect('admin_events');
} elseif ($user['role'] === 'organizer') {
    redirect('dashboard');
} else {
    redirect('event_feed');
}
