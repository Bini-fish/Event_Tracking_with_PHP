<?php
// Organizer dashboard: create, edit, delete events and view RSVPs.

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../models/EventModel.php';
require_once __DIR__ . '/../../models/RsvpModel.php';

require_organizer();

$organizerId = current_user_id() ?? 0;
$events      = $organizerId > 0 ? event_get_by_organizer($organizerId) : [];
?>
<section class="dashboard">
    <h1>My Events</h1>
    <p>Create new events and manage your existing ones.</p>

    <!-- Create Event -->
    <section style="margin-top: 1.5rem; margin-bottom: 2rem;">
        <h2>Create New Event</h2>
        <form action="<?= e(BASE_URL . 'actions/events/create_event.php') ?>" method="post" enctype="multipart/form-data" class="auth-form">
            <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">
            <label>
                Title
                <input type="text" name="title" required>
            </label>
            <label>
                Location
                <input type="text" name="location" required>
            </label>
            <label>
                Date &amp; Time
                <input type="datetime-local" name="event_date" required>
            </label>
            <label>
                Capacity
                <input type="number" name="capacity" min="1" required>
            </label>
            <label>
                Description
                <textarea name="description" rows="3" required></textarea>
            </label>
            <label>
                Image (optional)
                <input type="file" name="image" accept="image/*">
            </label>
            <button type="submit" class="button primary">Submit Event</button>
        </form>
    </section>

    <!-- ── Event List ───────────────────────────────────────────── -->
    <section>
        <h2>Your Events</h2>
        <?php if (empty($events)): ?>
            <p>No events yet. Create your first event above.</p>
        <?php else: ?>
            <?php foreach ($events as $event): ?>
                <?php $rsvps = rsvp_get_for_event((int) $event['id']); ?>
                <article class="event-card">
                    <header>
                        <h2><?= e($event['title']) ?></h2>
                        <?php if (!empty($event['is_verified'])): ?>
                            <span class="badge-verified">Verified</span>
                        <?php else: ?>
                            <span class="badge-verified" style="background:#fef3c7;color:#92400e;">Pending</span>
                        <?php endif; ?>
                    </header>
                    <p class="event-meta">
                        <?= e($event['event_date']) ?> &middot; <?= e($event['location']) ?>
                    </p>
                    <p class="event-meta">
                        Capacity: <?= (int) $event['capacity'] ?> &middot; RSVPs: <?= count($rsvps) ?>
                    </p>

                    <?php if (!empty($rsvps)): ?>
                        <p>Attendees:</p>
                        <ul style="margin-top:0.25rem;">
                            <?php foreach ($rsvps as $rsvp): ?>
                                <li><?= e($rsvp['name']) ?> (<?= e($rsvp['email']) ?>)</li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <!-- Edit form -->
                    <h3>Edit this event</h3>
                    <form action="<?= e(BASE_URL . 'actions/events/update_event.php') ?>" method="post" enctype="multipart/form-data" class="auth-form" style="margin-top: 0.75rem; padding: var(--space-md);">
                        <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">
                        <input type="hidden" name="event_id" value="<?= (int) $event['id'] ?>">
                        <label>
                            Title
                            <input type="text" name="title" value="<?= e($event['title']) ?>" required>
                        </label>
                        <label>
                            Location
                            <input type="text" name="location" value="<?= e($event['location']) ?>" required>
                        </label>
                        <label>
                            Date &amp; Time
                            <input type="datetime-local" name="event_date" value="<?= e(str_replace(' ', 'T', $event['event_date'])) ?>" required>
                        </label>
                        <label>
                            Capacity
                            <input type="number" name="capacity" min="1" value="<?= (int) $event['capacity'] ?>" required>
                        </label>
                        <label>
                            Description
                            <textarea name="description" rows="2" required><?= e($event['description']) ?></textarea>
                        </label>
                        <label>
                            Change image (optional)
                            <input type="file" name="image" accept="image/*">
                        </label>
                        <label>
                            Edit reason (optional)
                            <input type="text" name="edit_reason" maxlength="500" placeholder="Why are you updating this event?">
                        </label>
                        <button type="submit" class="button primary">Save Changes</button>
                    </form>

                    <!-- Delete button -->
                    <form action="<?= e(BASE_URL . 'actions/events/delete_event.php') ?>" method="post"
                          onsubmit="return confirm('Delete this event? This cannot be undone.');">
                        <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">
                        <input type="hidden" name="event_id" value="<?= (int) $event['id'] ?>">
                        <button type="submit" class="button"
                                style="font-size:0.8rem;border-color:#fca5a5;color:var(--color-danger);background:#fff5f5;">
                            Delete Event
                        </button>
                    </form>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
</section>
