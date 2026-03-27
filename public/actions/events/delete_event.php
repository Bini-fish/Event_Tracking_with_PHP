<?php
// Handle organizer event deletion (owner only).

declare(strict_types=1);

require_once __DIR__ . '/../../../includes/session.php';
require_once __DIR__ . '/../../../includes/helpers.php';
require_once __DIR__ . '/../../../includes/auth_guard.php';
require_once __DIR__ . '/../../../models/EventModel.php';

require_organizer();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('dashboard');
}

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    set_flash('error', 'Invalid form submission.');
    redirect('dashboard');
}

$eventId = (int) ($_POST['event_id'] ?? 0);

if ($eventId <= 0) {
    set_flash('error', 'Invalid event.');
    redirect('dashboard');
}

$ok = event_delete($eventId, current_user_id() ?? 0);

set_flash($ok ? 'success' : 'error', $ok ? 'Event deleted.' : 'Could not delete event or you do not own it.');
redirect('dashboard');
