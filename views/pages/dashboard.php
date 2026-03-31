<?php
// Organiser dashboard: pending RSVPs, event list with feedback summary, create/edit events.
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/policy.php';
require_once __DIR__ . '/../../models/EventModel.php';
require_once __DIR__ . '/../../models/RsvpModel.php';
require_once __DIR__ . '/../../models/FeedbackModel.php';

require_organizer();

$orgId         = current_user_id() ?? 0;
$events        = $orgId > 0 ? event_get_by_organizer($orgId) : [];
$pendingRsvps  = rsvp_get_pending_for_organizer($orgId);
$eventIds      = array_map(fn($e) => (int) $e['id'], $events);
$feedbackStats = feedback_summaries_for_events($eventIds);
?>

<div class="dashboard-header">
    <div>
        <h1 style="font-family:var(--font-display);font-size:1.8rem;">Dashboard</h1>
        <p style="color:var(--color-text-muted);margin-top:var(--sp-1);">Manage your events and RSVP requests.</p>
    </div>
</div>

<!-- ── Stat row ─────────────────────────────────────────────────────────── -->
<div class="stat-row">
    <div class="stat-box">
        <div class="stat-label">Your Events</div>
        <div class="stat-value"><?= count($events) ?></div>
    </div>
    <div class="stat-box">
        <div class="stat-label">Pending RSVPs</div>
        <div class="stat-value" style="color:var(--color-warning)"><?= count($pendingRsvps) ?></div>
    </div>
    <div class="stat-box">
        <div class="stat-label">Verified Events</div>
        <div class="stat-value" style="color:var(--color-success)"><?= count(array_filter($events, fn($e) => (int)$e['is_verified'])) ?></div>
    </div>
</div>

<!-- ── Pending RSVPs ────────────────────────────────────────────────────── -->
<section class="dashboard-section">
    <h2>⏳ Pending RSVP Requests
        <?php if (count($pendingRsvps) > 0): ?>
            <span class="badge badge-pending" style="vertical-align:middle;margin-left:var(--sp-2)"><?= count($pendingRsvps) ?></span>
        <?php endif; ?>
    </h2>

    <?php if (empty($pendingRsvps)): ?>
        <div class="empty-state" style="padding:var(--sp-8) 0">
            <div class="empty-icon">✅</div>
            <h3>All caught up!</h3>
            <p>No pending RSVPs at the moment.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Attendee</th>
                        <th>Event</th>
                        <th>Seats</th>
                        <th>Requested</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingRsvps as $r): ?>
                        <?php $isFull = (int)$r['approved_count'] >= (int)$r['capacity']; ?>
                        <tr>
                            <td>
                                <div style="font-weight:600;font-size:.88rem"><?= e($r['attendee_name']) ?></div>
                                <div style="font-size:.78rem;color:var(--color-text-muted)"><?= e($r['attendee_email']) ?></div>
                            </td>
                            <td>
                                <a href="<?= e(url_for('event_detail', ['id' => (int)$r['event_id']])) ?>" style="color:var(--color-primary);font-weight:500;font-size:.88rem">
                                    <?= e($r['event_title']) ?>
                                </a>
                                <?php if ($isFull): ?><br><span class="badge badge-full" style="margin-top:2px">Full</span><?php endif; ?>
                            </td>
                            <td style="font-size:.85rem"><?= (int)$r['approved_count'] ?> / <?= (int)$r['capacity'] ?></td>
                            <td style="font-size:.78rem;color:var(--color-text-muted)"><?= e(date('d M, H:i', strtotime($r['created_at']))) ?></td>
                            <td>
                                <div class="rsvp-actions">
                                    <?php if (!$isFull): ?>
                                    <form action="<?= e(BASE_URL . 'actions/events/approve_rsvp.php') ?>" method="post">
                                        <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">
                                        <input type="hidden" name="rsvp_id" value="<?= (int)$r['rsvp_id'] ?>">
                                        <button type="submit" class="button success sm" aria-label="Approve RSVP for <?= e($r['attendee_name']) ?>">✓ Approve</button>
                                    </form>
                                    <?php endif; ?>
                                    <form action="<?= e(BASE_URL . 'actions/events/reject_rsvp.php') ?>" method="post">
                                        <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">
                                        <input type="hidden" name="rsvp_id" value="<?= (int)$r['rsvp_id'] ?>">
                                        <button type="submit" class="button danger sm" aria-label="Reject RSVP for <?= e($r['attendee_name']) ?>">✕ Reject</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<!-- ── Create Event ─────────────────────────────────────────────────────── -->
<section class="dashboard-section">
    <h2>➕ Create New Event</h2>
    <div class="card">
        <form action="<?= e(BASE_URL . 'actions/events/create_event.php') ?>" method="post" enctype="multipart/form-data" class="auth-form" id="createForm">
            <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--sp-5);" class="form-two-col">
                <label>
                    Title <span style="color:var(--color-danger)">*</span>
                    <input type="text" name="title" maxlength="150" required placeholder="Event title">
                </label>
                <label>
                    Location <span style="color:var(--color-danger)">*</span>
                    <input type="text" name="location" maxlength="150" required placeholder="Venue or address">
                </label>
                <label>
                    Start Date &amp; Time <span style="color:var(--color-danger)">*</span>
                    <input type="datetime-local" name="event_date" required>
                    <small class="input-hint">Future dates only</small>
                </label>
                <label>
                    End Date &amp; Time <span style="color:var(--color-danger)">*</span>
                    <input type="datetime-local" name="event_end" required>
                    <small class="input-hint">Min <?= MIN_EVENT_DURATION_MINUTES ?> minutes after start</small>
                </label>
                <label>
                    Capacity <span style="color:var(--color-danger)">*</span>
                    <input type="number" name="capacity" min="1" required placeholder="Max attendees">
                </label>
                <label>
                    Image (optional, max 2 MB)
                    <input type="file" name="image" accept="image/jpeg,image/png,image/gif">
                </label>
            </div>
            <label>
                Description <span style="color:var(--color-danger)">*</span>
                <textarea name="description" rows="3" maxlength="5000" required placeholder="Describe your event…"
                          oninput="document.getElementById('createDescCount').textContent=this.value.length"></textarea>
                <small class="char-count" style="text-align:right"><span id="createDescCount">0</span> / 5000</small>
            </label>
            <button type="submit" class="button primary" id="createBtn" style="align-self:flex-start">Submit Event</button>
        </form>
    </div>
</section>

<!-- ── Your Events ──────────────────────────────────────────────────────── -->
<section class="dashboard-section">
    <h2>📋 Your Events</h2>
    <?php if (empty($events)): ?>
        <div class="empty-state" style="padding:var(--sp-8) 0">
            <div class="empty-icon">🎉</div>
            <h3>No events yet</h3>
            <p>Create your first event using the form above.</p>
        </div>
    <?php else: ?>
        <?php foreach ($events as $event): ?>
            <?php
            $rsvps     = rsvp_get_for_event((int) $event['id']);
            $approved  = array_filter($rsvps, fn($r) => $r['status'] === 'approved');
            $pending   = array_filter($rsvps, fn($r) => $r['status'] === 'pending');
            $fb        = $feedbackStats[(int)$event['id']] ?? null;
            $eventEnd  = $event['event_end'] ?? $event['event_date'];
            $hasEnded  = has_datetime_passed((string) $eventEnd);
            ?>
            <div class="card" style="margin-bottom:var(--sp-6);">
                <!-- Event summary row -->
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:var(--sp-4);flex-wrap:wrap;margin-bottom:var(--sp-4);">
                    <div>
                        <div style="display:flex;align-items:center;gap:var(--sp-3);flex-wrap:wrap;">
                            <h2 style="font-size:1.1rem;margin:0"><?= e($event['title']) ?></h2>
                            <?php if (!empty($event['is_verified'])): ?>
                                <span class="badge badge-approved">Verified</span>
                            <?php else: ?>
                                <span class="badge badge-pending">Pending Approval</span>
                            <?php endif; ?>
                            <?php if ($hasEnded): ?><span class="badge badge-ended">Ended</span><?php endif; ?>
                        </div>
                        <p class="event-meta" style="margin-top:var(--sp-2)">
                            📅 <?= e(date('d M Y, H:i', strtotime((string) $event['event_date']))) ?>
                            <?php if (!empty($event['event_end'])): ?> → <?= e(date('H:i', strtotime((string) $event['event_end']))) ?><?php endif; ?>
                            &nbsp;·&nbsp; 📍 <?= e($event['location']) ?>
                        </p>
                        <p style="font-size:.82rem;color:var(--color-text-muted)">
                            Approved: <strong><?= count($approved) ?></strong> / <?= (int)$event['capacity'] ?> &nbsp;·&nbsp;
                            Pending: <strong style="color:var(--color-warning)"><?= count($pending) ?></strong>
                            <?php if ($fb): ?>
                                &nbsp;·&nbsp; Rating: <span class="stars"><?= str_repeat('★', (int) round($fb['avg_rating'])) ?></span>
                                <?= number_format($fb['avg_rating'], 1) ?> (<?= $fb['count'] ?> reviews)
                            <?php endif; ?>
                        </p>
                    </div>
                    <a href="<?= e(url_for('event_detail', ['id' => (int)$event['id']])) ?>" class="button subtle sm">View →</a>
                </div>

                <!-- Attendee list (all RSVPs) -->
                <?php if (!empty($rsvps)): ?>
                <details style="margin-bottom:var(--sp-4);">
                    <summary style="cursor:pointer;font-size:.85rem;font-weight:600;color:var(--color-text-muted)">
                        Attendees (<?= count($approved) ?> approved, <?= count($pending) ?> pending)
                    </summary>
                    <div style="padding:var(--sp-3) 0 0;display:flex;flex-direction:column;gap:var(--sp-1)">
                        <?php foreach ($rsvps as $r): ?>
                            <?php $initials = strtoupper(mb_substr($r['name'], 0, 2)); ?>
                            <div class="attendee-row">
                                <div class="avatar-circle sm"><?= e($initials) ?></div>
                                <div class="attendee-info">
                                    <div class="name"><?= e($r['name']) ?></div>
                                    <div class="email"><?= e($r['email']) ?></div>
                                </div>
                                <div class="attendee-time"><?= e(date('d M Y, g:i A', strtotime($r['created_at']))) ?></div>
                                <?php if ($r['status'] === 'approved'): ?>
                                    <span class="badge badge-approved">Approved</span>
                                <?php elseif ($r['status'] === 'pending'): ?>
                                    <span class="badge badge-pending">Pending</span>
                                <?php else: ?>
                                    <span class="badge badge-rejected">Rejected</span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </details>
                <?php endif; ?>

                <!-- Edit form (collapsible) -->
                <details>
                    <summary style="cursor:pointer;font-size:.85rem;font-weight:600;color:var(--color-primary);margin-bottom:var(--sp-3)">
                        ✏️ Edit Event
                    </summary>
                    <form action="<?= e(BASE_URL . 'actions/events/update_event.php') ?>" method="post" enctype="multipart/form-data" class="auth-form">
                        <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">
                        <input type="hidden" name="event_id" value="<?= (int) $event['id'] ?>">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--sp-5);" class="form-two-col">
                            <label>
                                Title
                                <input type="text" name="title" value="<?= e($event['title']) ?>" maxlength="150" required>
                            </label>
                            <label>
                                Location
                                <input type="text" name="location" value="<?= e($event['location']) ?>" maxlength="150" required>
                            </label>
                            <label>
                                Start Date &amp; Time
                                <input type="datetime-local" name="event_date" value="<?= e(str_replace(' ', 'T', (string)$event['event_date'])) ?>" required>
                            </label>
                            <label>
                                End Date &amp; Time
                                <input type="datetime-local" name="event_end" value="<?= e(str_replace(' ', 'T', (string)($event['event_end'] ?? ''))) ?>" required>
                                <small class="input-hint">Min <?= MIN_EVENT_DURATION_MINUTES ?> min</small>
                            </label>
                            <label>
                                Capacity
                                <input type="number" name="capacity" min="1" value="<?= (int) $event['capacity'] ?>" required>
                            </label>
                            <label>
                                Change Image (optional)
                                <input type="file" name="image" accept="image/jpeg,image/png,image/gif">
                            </label>
                        </div>
                        <label>
                            Description
                            <textarea name="description" rows="2" maxlength="5000" required><?= e($event['description']) ?></textarea>
                        </label>
                        <label>
                            Edit Reason (optional)
                            <input type="text" name="edit_reason" maxlength="500" placeholder="Why are you updating this event?">
                        </label>
                        <button type="submit" class="button primary" style="align-self:flex-start">Save Changes</button>
                    </form>
                </details>

                <!-- Delete -->
                <form action="<?= e(BASE_URL . 'actions/events/delete_event.php') ?>" method="post"
                      onsubmit="return confirm('Permanently delete this event? This cannot be undone.');"
                      style="margin-top:var(--sp-3)">
                    <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">
                    <input type="hidden" name="event_id" value="<?= (int) $event['id'] ?>">
                    <button type="submit" class="button danger sm">🗑 Delete Event</button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

<style>
@media (max-width: 640px) {
    .form-two-col { grid-template-columns: 1fr !important; }
}
</style>
