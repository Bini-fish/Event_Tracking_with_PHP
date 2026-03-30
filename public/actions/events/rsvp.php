<?php
// Handle RSVP submissions with capacity checks.

declare(strict_types=1);

require_once __DIR__ . '/../../../includes/session.php';
require_once __DIR__ . '/../../../includes/helpers.php';
require_once __DIR__ . '/../../../includes/auth_guard.php';
require_once __DIR__ . '/../../../includes/policy.php';
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

try {
    $event = event_get_by_id($eventId);
} catch (PDOException $e) {
    log_exception($e, 'RSVP DB read error');
    set_flash('error', 'Something went wrong. Please try again.');
    redirect('event_feed');
} catch (Throwable $e) {
    log_exception($e, 'RSVP read error');
    set_flash('error', 'Something went wrong. Please try again.');
    redirect('event_feed');
}

if (!$event) {
    set_flash('error', 'Event not found.');
    redirect('event_feed');
}

$userId = current_user_id() ?? 0;

if ($userId <= 0) {
    set_flash('error', 'You must be logged in to RSVP.');
    redirect('login', ['redirect' => url_for('event_detail', ['id' => $eventId])]);
}

if (!can_rsvp_event($userId, $event, null, true)) {
    set_flash('error', 'This event is pending approval and is not open for RSVP.');
    redirect('event_feed');
}

$capacity = (int) $event['capacity'];

try {
    if (!rsvp_can_rsvp($eventId, $userId, $capacity)) {
        set_flash('error', 'You cannot RSVP for this event (it may be full or you are already registered).');
        redirect('event_detail', ['id' => $eventId]);
    }

    $ok = rsvp_add($eventId, $userId);
} catch (PDOException $e) {
    log_exception($e, 'RSVP DB write error');
    set_flash('error', 'Something went wrong. Please try again.');
    redirect('event_detail', ['id' => $eventId]);
} catch (Throwable $e) {
    log_exception($e, 'RSVP write error');
    set_flash('error', 'Something went wrong. Please try again.');
    redirect('event_detail', ['id' => $eventId]);
}

set_flash($ok ? 'success' : 'error', $ok ? 'RSVP confirmed!' : 'Could not complete RSVP.');
redirect('event_detail', ['id' => $eventId]);
