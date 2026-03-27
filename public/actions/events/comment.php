<?php
// Handle adding comments and organizer replies.

declare(strict_types=1);

require_once __DIR__ . '/../../../includes/session.php';
require_once __DIR__ . '/../../../includes/helpers.php';
require_once __DIR__ . '/../../../includes/auth_guard.php';
require_once __DIR__ . '/../../../models/CommentModel.php';
require_once __DIR__ . '/../../../models/EventModel.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('event_feed');
}

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    set_flash('error', 'Invalid form submission.');
    redirect('event_feed');
}

$eventId  = (int) ($_POST['event_id'] ?? 0);
$body     = trim($_POST['body'] ?? '');
$parentId = isset($_POST['parent_comment_id']) && $_POST['parent_comment_id'] !== ''
    ? (int) $_POST['parent_comment_id']
    : null;

if ($eventId <= 0 || $body === '') {
    set_flash('error', 'Please provide a comment.');
    redirect('event_feed');
}

$userId = current_user_id() ?? 0;
$event  = event_get_by_id($eventId);

if ($userId <= 0) {
    set_flash('error', 'You must be logged in to comment.');
    redirect('event_feed');
}

if (!$event) {
    set_flash('error', 'Event not found.');
    redirect('event_feed');
}

$isAdmin         = current_user_role() === 'admin';
$isOrganizerOwner = (int) $event['organizer_id'] === $userId;

if ((int) $event['is_verified'] !== 1 && !$isAdmin && !$isOrganizerOwner) {
    set_flash('error', 'This event is not available for comments yet.');
    redirect('event_feed');
}

if ($parentId === null) {
    $ok = comment_add($eventId, $userId, $body);
} else {
    // Keep replies controlled: only the event organizer/admin can post replies.
    if (!$isAdmin && !$isOrganizerOwner) {
        set_flash('error', 'Only the event organizer can reply to comments.');
        redirect('event_detail', ['id' => $eventId]);
    }

    $parentComment = comment_get_by_id($parentId);
    if (!$parentComment || (int) $parentComment['event_id'] !== $eventId) {
        set_flash('error', 'Invalid parent comment for this event.');
        redirect('event_detail', ['id' => $eventId]);
    }

    $ok = comment_add_reply($eventId, $userId, $parentId, $body);
}

set_flash($ok ? 'success' : 'error', $ok ? 'Comment posted.' : 'Could not post comment.');
redirect('event_detail', ['id' => $eventId]);
