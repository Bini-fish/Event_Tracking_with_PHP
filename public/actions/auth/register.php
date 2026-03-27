<?php
// Handle registration form submission and create a new user.

declare(strict_types=1);

require_once __DIR__ . '/../../../includes/session.php';
require_once __DIR__ . '/../../../includes/helpers.php';
require_once __DIR__ . '/../../../models/UserModel.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('register');
}

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    set_flash('error', 'Invalid form submission.');
    redirect('register');
}

$name     = trim($_POST['name'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$role     = $_POST['role'] ?? 'attendee';

if ($name === '' || $email === '' || $password === '') {
    set_flash('error', 'All fields are required.');
    redirect('register');
}

if (!in_array($role, ['attendee', 'organizer'], true)) {
    $role = 'attendee';
}

if (user_find_by_email($email)) {
    set_flash('error', 'An account with that email already exists.');
    redirect('register');
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$userId       = user_create($name, $email, $passwordHash, $role);

if ($userId === null) {
    set_flash('error', 'Could not create account. Please try again.');
    redirect('register');
}

$user = user_find_by_id($userId);

if ($user) {
    login_user($user);
}

set_flash('success', 'Account created successfully. Welcome!');

if ($role === 'organizer') {
    redirect('dashboard');
} else {
    redirect('event_feed');
}
