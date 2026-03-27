<?php
// Event detail page: show full event info, RSVP box, and simple feedback.
// Requires login to view.
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../models/EventModel.php';
require_once __DIR__ . '/../../models/RsvpModel.php';
require_once __DIR__ . '/../../models/CommentModel.php';

require_login();

$eventId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$event   = $eventId > 0 ? event_get_by_id($eventId) : null;

if (!$event) {
    http_response_code(404);
    ?>
    <section class="not-found">
        <h1>Event Not Found</h1>
        <p>The event you are looking for does not exist.</p>
        <a class="button" href="<?= e(url_for('event_feed')) ?>">Back to events</a>
    </section>
    <?php
    return;
}

$isAdmin          = current_user_role() === 'admin';
$isOrganizerOwner = (current_user_id() ?? 0) === (int) $event['organizer_id'];

if ((int) $event['is_verified'] !== 1 && !$isAdmin && !$isOrganizerOwner) {
    http_response_code(403);
    ?>
    <section class="not-found">
        <h1>Event Not Available</h1>
        <p>This event is pending approval and is only visible to the organizer or admin.</p>
        <a class="button" href="<?= e(url_for('event_feed')) ?>">Back to events</a>
    </section>
    <?php
    return;
}

$remainingSeats = rsvp_remaining_seats((int) $event['id'], (int) $event['capacity']);
$rsvpCount      = rsvp_count_for_event((int) $event['id']);
$comments       = comment_get_by_event((int) $event['id']);
?>

<section class="event-detail">
    <header class="event-detail-header">
        <h1><?= e($event['title']) ?></h1>
        <p class="event-meta">
            <?= e($event['event_date']) ?> &middot;
            <?= e($event['location']) ?>
        </p>
    </header>

    <div class="event-detail-body">
        <article class="event-description">
            <?php if (!empty($event['image_path'])): ?>
                <img src="<?= e(BASE_URL . $event['image_path']) ?>" alt="Event image" style="border-radius: 16px; max-height: 260px; object-fit: cover; margin-bottom: 1rem;">
            <?php endif; ?>
            <h2>About this event</h2>
            <p><?= nl2br(e($event['description'])) ?></p>
        </article>

        <aside class="event-sidebar">
            <div class="rsvp-box">
                <h3>RSVP</h3>
                <p>Capacity: <?= (int) $event['capacity'] ?></p>
                <p>RSVPs: <?= (int) $rsvpCount ?> &middot; Remaining seats: <?= (int) $remainingSeats ?></p>

                <?php if (current_user_id() === null): ?>
                    <a class="button primary"
                       href="<?= e(url_for('login', ['redirect' => $_SERVER['REQUEST_URI'] ?? null])) ?>">
                        Login to RSVP
                    </a>
                <?php else: ?>
                    <form action="<?= e(BASE_URL . 'actions/events/rsvp.php') ?>" method="post">
                        <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">
                        <input type="hidden" name="event_id" value="<?= (int) $event['id'] ?>">
                        <button type="submit" class="button primary">RSVP for this event</button>
                    </form>
                <?php endif; ?>
            </div>

            <div class="comments-box">
                <h3>Event feedback</h3>

                <?php if (current_user_id() === null): ?>
                    <p><a href="<?= e(url_for('login', ['redirect' => $_SERVER['REQUEST_URI'] ?? null])) ?>">Login</a> to leave feedback.</p>
                <?php else: ?>
                    <form action="<?= e(BASE_URL . 'actions/events/comment.php') ?>" method="post" style="margin-bottom: 1rem;">
                        <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">
                        <input type="hidden" name="event_id" value="<?= (int) $event['id'] ?>">
                        <label>
                            <span style="display:block;font-size:0.85rem;color:#6b7280;margin-bottom:0.25rem;">Your feedback</span>
                            <input type="text" name="body" required>
                        </label>
                        <button type="submit" class="button subtle" style="margin-top:0.5rem;">Submit feedback</button>
                    </form>
                <?php endif; ?>

                <?php if (empty($comments)): ?>
                    <p>No feedback yet.</p>
                <?php else: ?>
                    <ul style="list-style:none;padding:0;margin:0;">
                        <?php foreach ($comments as $comment): ?>
                            <li style="margin-bottom:0.5rem;font-size:0.9rem;"><?= e($comment['body']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </aside>
    </div>
</section>

