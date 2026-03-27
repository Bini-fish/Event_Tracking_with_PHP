<?php
// Handle RSVP submissions with capacity checks.

declare(strict_types=1);

require_once __DIR__ . '/../../../includes/session.php';
require_once __DIR__ . '/../../../includes/helpers.php';
require_once __DIR__ . '/../../../includes/auth_guard.php';
require_once __DIR__ . '/../../../models/EventModel.php';
require_once __DIR__ . '/../../../models/RsvpModel.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('event_feed');
}

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    set_flash('error', 'Invalid form submission.');
    redirect('event_feed');
}

$eventId = (int) ($_POST['event_id'] ?? 0);

if ($eventId <= 0) {
    set_flash('error', 'Invalid event.');
    redirect('event_feed');
}

$event = event_get_by_id($eventId);

if (!$event) {
    set_flash('error', 'Event not found.');
    redirect('event_feed');
}

$userId = current_user_id() ?? 0;

if ($userId <= 0) {
    set_flash('error', 'You must be logged in to RSVP.');
    redirect('login', ['redirect' => url_for('event_detail', ['id' => $eventId])]);
}

$capacity = (int) $event['capacity'];

if (!rsvp_can_rsvp($eventId, $userId, $capacity)) {
    set_flash('error', 'You cannot RSVP for this event (it may be full or you are already registered).');
    redirect('event_detail', ['id' => $eventId]);
}

$ok = rsvp_add($eventId, $userId);

set_flash($ok ? 'success' : 'error', $ok ? 'RSVP confirmed!' : 'Could not complete RSVP.');
redirect('event_detail', ['id' => $eventId]);
