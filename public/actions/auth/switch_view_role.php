<?php
// Allow admins to switch contextual role view (admin/organizer/attendee).

declare(strict_types=1);

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/session.php';
require_once __DIR__ . '/../../../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('event_feed');
}

if (current_user_role() !== 'admin') {
    set_flash('error', 'Only admins can switch role view.');
    redirect('event_feed');
}

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    set_flash('error', 'Invalid request token.');
    redirect('admin_events');
}

$viewRole = isset($_POST['view_role']) ? (string) $_POST['view_role'] : 'admin';
if (!in_array($viewRole, ['admin', 'organizer', 'attendee'], true)) {
    $viewRole = 'admin';
}

set_admin_view_role($viewRole);

if ($viewRole === 'organizer') {
    set_flash('success', 'Switched to organizer view. You are still authenticated as admin.');
    redirect('dashboard');
}

if ($viewRole === 'attendee') {
    set_flash('success', 'Switched to attendee view. You are still authenticated as admin.');
    redirect('event_feed');
}

set_flash('success', 'Returned to admin view.');
redirect('admin_events');
