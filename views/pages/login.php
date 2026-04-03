<?php
declare(strict_types=1);

require_once __DIR__ . '/../partials/hawassa_svg_icons.php';

$loginTiles = [
    [
        'page'    => 'login_attendee',
        'label'   => 'Attendee',
        'desc'    => 'RSVP and explore city events.',
        'accent'  => '#3B82F6',
        'soft'    => 'rgba(59, 130, 246, 0.14)',
        'svg_key' => 'role_attendee',
    ],
    [
        'page'    => 'login_organizer',
        'label'   => 'Organizer',
        'desc'    => 'Publish and manage your events.',
        'accent'  => '#10B981',
        'soft'    => 'rgba(16, 185, 129, 0.14)',
        'svg_key' => 'role_organizer',
    ],
    [
        'page'    => 'login_admin',
        'label'   => 'Admin',
        'desc'    => 'Moderate and configure the system.',
        'accent'  => '#F97316',
        'soft'    => 'rgba(249, 115, 22, 0.14)',
        'svg_key' => 'role_admin',
    ],
];

$redirectParam = isset($_GET['redirect']) ? (string) $_GET['redirect'] : '';
?>
<div class="auth-hub-wrap">
    <div class="auth-hub-bg" aria-hidden="true"></div>
    <div class="auth-hub-content auth-animate-in">
        <header class="auth-hub-header">
            <h1 class="auth-hub-title">Sign in</h1>
            <p class="auth-hub-lead">Choose how you use the platform. You’ll get a focused sign-in screen for that role.</p>
        </header>

        <div class="auth-hub-grid">
            <?php foreach ($loginTiles as $tile): ?>
                <a class="auth-hub-card"
                   href="<?= e(url_for($tile['page'], $redirectParam !== '' ? ['redirect' => $redirectParam] : [])) ?>"
                   style="--tile-accent: <?= e($tile['accent']) ?>; --tile-soft: <?= e($tile['soft']) ?>;">
                    <span class="auth-hub-card-icon hawassa-icon-wrap" style="color: <?= e($tile['accent']) ?>;"><?= $HAWASSA_SVG[$tile['svg_key']] ?? '' ?></span>
                    <span class="auth-hub-card-label"><?= e($tile['label']) ?></span>
                    <span class="auth-hub-card-desc"><?= e($tile['desc']) ?></span>
                    <span class="auth-hub-card-cta">Continue →</span>
                </a>
            <?php endforeach; ?>
        </div>

        <p class="auth-hub-foot">
            New here? <a href="<?= e(url_for('register')) ?>">Create an account</a>
        </p>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    try {
        if (document.querySelector('.flash-overlay--error')) {
            sessionStorage.removeItem('cityauth_login_submitted_at');
        }
    } catch (e) {}
});
</script>
