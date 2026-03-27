<?php
// Admin events moderation page: list pending events for approval.

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../models/EventModel.php';

require_admin();

$pendingEvents = event_get_pending_events();
?>
<section class="admin-events">
    <h1>Pending Events</h1>
    <?php if (empty($pendingEvents)): ?>
        <p>No pending events at the moment.</p>
    <?php else: ?>
        <div class="event-grid">
            <?php foreach ($pendingEvents as $event): ?>
                <article class="event-card">
                    <header>
                        <h2><?= e($event['title']) ?></h2>
                    </header>
                    <p class="event-meta">
                        <?= e($event['event_date']) ?> &middot;
                        <?= e($event['location']) ?>
                    </p>
                    <form action="<?= e(BASE_URL . 'actions/events/toggle_approval.php') ?>" method="post" style="margin-top:0.5rem;">
                        <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">
                        <input type="hidden" name="event_id" value="<?= (int) $event['id'] ?>">
                        <input type="hidden" name="is_verified" value="1">
                        <button type="submit" class="button primary" style="font-size:0.85rem;">Approve</button>
                    </form>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

