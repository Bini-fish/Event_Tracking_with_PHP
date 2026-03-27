<?php
// Event feed page: card grid of approved events (requires login).
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../models/EventModel.php';

require_login();

$events = event_get_approved_events();
?>

<section class="page-header">
    <h1>Discover Events</h1>
    <p>Browse approved events in Hawassa and RSVP.</p>
</section>

<?php if (empty($events)): ?>
    <p>No approved events yet. Check back soon.</p>
<?php else: ?>
    <section class="event-grid">
        <?php foreach ($events as $event): ?>
            <article class="event-card">
                <header>
                    <h2><?= e($event['title']) ?></h2>
                    <?php if (!empty($event['is_verified'])): ?>
                        <span class="badge-verified">Verified</span>
                    <?php endif; ?>
                </header>

                <p class="event-meta">
                    <?= e($event['event_date']) ?> &middot;
                    <?= e($event['location']) ?>
                </p>

                <?php if (!empty($event['image_path'])): ?>
                    <img src="<?= e(BASE_URL . $event['image_path']) ?>" alt="Event image" style="border-radius: 12px; max-height: 180px; object-fit: cover;">
                <?php endif; ?>

                <p>
                    <a class="button subtle" href="<?= e(url_for('event_detail', ['id' => (int) $event['id']])) ?>">
                        View details
                    </a>
                </p>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

