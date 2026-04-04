<?php
declare(strict_types=1);

require_once __DIR__ . '/../partials/hawassa_svg_icons.php';

$homeRoles = [
    ['page' => 'login_attendee', 'label' => 'Attendee', 'desc' => 'Discover events and RSVP in one tap.', 'accent' => '#3B82F6', 'svg_key' => 'role_attendee'],
    ['page' => 'login_organizer', 'label' => 'Organizer', 'desc' => 'Run events and stay on top of RSVPs.', 'accent' => '#10B981', 'svg_key' => 'role_organizer'],
    ['page' => 'login_admin', 'label' => 'Admin', 'desc' => 'Keep the city calendar trusted and safe.', 'accent' => '#F97316', 'svg_key' => 'role_admin'],
];
?>
<section class="home-hero home-hero--hawassa auth-animate-in">
    <p class="home-hero-kicker">Hawassa · City events</p>
    <h1 class="home-hero-title">Discover what’s happening</h1>
    <p class="home-hero-text home-hero-text--lead">
        Browse verified city events, reserve your seat, share feedback, and stay in the loop.
    </p>
</section>

<section class="home-roles" aria-label="Sign in by role">
    <h2 class="home-section-title">Sign in</h2>
    <p class="home-section-lead">Pick your role for a tailored login experience.</p>
    <div class="home-role-grid">
        <?php foreach ($homeRoles as $r): ?>
            <a class="home-role-card" href="<?= e(url_for($r['page'])) ?>"
               style="--role-accent: <?= e($r['accent']) ?>;">
                <span class="home-role-icon hawassa-icon-wrap" style="color: <?= e($r['accent']) ?>;"><?= $HAWASSA_SVG[$r['svg_key']] ?? '' ?></span>
                <span class="home-role-name"><?= e($r['label']) ?></span>
                <span class="home-role-desc"><?= e($r['desc']) ?></span>
                <span class="home-role-go">Sign in →</span>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<section class="home-create-account" aria-label="Need an account?">
    <div class="home-create-card">
        <div class="home-create-copy">
            <h2 class="home-create-title">Create account</h2>
            <p class="home-create-sub">Join as an attendee or organizer in under a minute.</p>
        </div>
        <a class="button auth-create-btn" href="<?= e(url_for('register')) ?>">Get started</a>
    </div>
</section>
