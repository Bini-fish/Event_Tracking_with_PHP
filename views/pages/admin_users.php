<?php
// Admin user management: list, role change, delete.
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../models/UserModel.php';

require_admin();

$roleFilter = isset($_GET['role']) && in_array($_GET['role'], ['admin', 'organizer', 'attendee'], true)
    ? $_GET['role'] : null;
$users   = user_get_all($roleFilter);
$counts  = user_count_by_role();
$actorId = current_user_id() ?? 0;

$roleLabels = ['admin' => '🛡️ Admin', 'organizer' => '📋 Organiser', 'attendee' => '👤 Attendee'];
?>

<div class="dashboard-header">
    <div>
        <h1 style="font-family:var(--font-display);font-size:1.8rem;">User Management</h1>
        <p style="color:var(--color-text-muted);margin-top:var(--sp-1);">View, edit roles, and manage all platform accounts.</p>
    </div>
    <div style="display:flex;gap:var(--sp-3);">
        <a href="<?= e(url_for('admin_events')) ?>" class="button subtle sm">← Events</a>
    </div>
</div>

<!-- Stat boxes -->
<div class="stat-row">
    <div class="stat-box">
        <div class="stat-label">Total Users</div>
        <div class="stat-value"><?= $counts['total'] ?></div>
    </div>
    <div class="stat-box">
        <div class="stat-label">Organisers</div>
        <div class="stat-value" style="color:var(--color-success)"><?= $counts['organizer'] ?></div>
    </div>
    <div class="stat-box">
        <div class="stat-label">Attendees</div>
        <div class="stat-value" style="color:var(--color-primary)"><?= $counts['attendee'] ?></div>
    </div>
    <div class="stat-box">
        <div class="stat-label">Admins</div>
        <div class="stat-value" style="color:var(--color-warning)"><?= $counts['admin'] ?></div>
    </div>
</div>

<!-- Role filter tabs -->
<div style="display:flex;gap:var(--sp-2);margin-bottom:var(--sp-6);flex-wrap:wrap;">
    <a href="<?= e(url_for('admin_users')) ?>"
       class="button sm <?= $roleFilter === null ? 'primary' : 'subtle' ?>">All (<?= $counts['total'] ?>)</a>
    <?php foreach (['organizer' => 'Organisers', 'attendee' => 'Attendees', 'admin' => 'Admins'] as $r => $label): ?>
        <a href="<?= e(url_for('admin_users', ['role' => $r])) ?>"
           class="button sm <?= $roleFilter === $r ? 'primary' : 'subtle' ?>"><?= $label ?> (<?= $counts[$r] ?>)</a>
    <?php endforeach; ?>
</div>

<!-- Users table -->
<?php if (empty($users)): ?>
    <div class="empty-state" style="padding:var(--sp-8) 0">
        <div class="empty-icon">👥</div>
        <h3>No users found</h3>
    </div>
<?php else: ?>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>User</th>
                <th>Role</th>
                <th>Events</th>
                <th>RSVPs</th>
                <th>Joined</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
                <?php $isSelf = (int)$u['id'] === $actorId; ?>
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:var(--sp-3);">
                            <div class="avatar-circle" data-initials="<?= e(strtoupper(mb_substr($u['name'], 0, 2))) ?>">
                                <?= e(strtoupper(mb_substr($u['name'], 0, 2))) ?>
                            </div>
                            <div>
                                <div style="font-weight:600;font-size:.88rem;"><?= e($u['name']) ?></div>
                                <div style="font-size:.78rem;color:var(--color-text-muted);"><?= e($u['email']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <?php if ($isSelf): ?>
                            <span class="badge badge-approved"><?= $roleLabels[$u['role']] ?? $u['role'] ?></span>
                            <div style="font-size:.7rem;color:var(--color-text-light);margin-top:2px;">You</div>
                        <?php else: ?>
                            <form action="<?= e(BASE_URL . 'actions/admin/update_user_role.php') ?>" method="post"
                                  style="display:flex;align-items:center;gap:var(--sp-2);">
                                <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">
                                <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                <select name="role" class="form-control" style="padding:.3rem .5rem;font-size:.82rem;width:auto;min-width:110px;"
                                        onchange="this.form.submit()">
                                    <option value="attendee" <?= $u['role'] === 'attendee' ? 'selected' : '' ?>>👤 Attendee</option>
                                    <option value="organizer" <?= $u['role'] === 'organizer' ? 'selected' : '' ?>>📋 Organiser</option>
                                    <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>🛡️ Admin</option>
                                </select>
                            </form>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:.88rem;text-align:center;"><?= (int)$u['event_count'] ?></td>
                    <td style="font-size:.88rem;text-align:center;"><?= (int)$u['rsvp_count'] ?></td>
                    <td style="font-size:.78rem;color:var(--color-text-muted);"><?= e(date('d M Y', strtotime($u['created_at']))) ?></td>
                    <td>
                        <?php if (!$isSelf): ?>
                            <form action="<?= e(BASE_URL . 'actions/admin/delete_user.php') ?>" method="post"
                                  onsubmit="return confirm('Delete <?= e($u['name']) ?>? This removes all their events, RSVPs, and comments.');">
                                <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">
                                <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                <button type="submit" class="button danger sm">🗑 Delete</button>
                            </form>
                        <?php else: ?>
                            <span style="font-size:.78rem;color:var(--color-text-light);">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
