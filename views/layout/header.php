<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/helpers.php';

$isLanding = isset($page) && $page === 'landing';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Discover and track city-wide events — RSVP, give feedback, and stay connected.">
    <title><?= e(APP_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,600;0,9..144,700;1,9..144,500&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(BASE_URL . 'assets/css/style.css') ?>">
    <script src="https://unpkg.com/@lottiefiles/lottie-player@2.0.8/dist/lottie-player.js" defer></script>
</head>
<body class="<?= $isLanding ? 'page-is-landing' : '' ?>">
<header class="site-header<?= $isLanding ? ' site-header--landing' : '' ?>" role="banner">
    <div class="container header-inner">
        <a href="<?= e(url_for(current_user_id() !== null ? 'event_feed' : 'landing')) ?>" class="logo" aria-label="<?= e(APP_NAME) ?> home">
            <span class="logo-mark" aria-hidden="true">
                <svg width="22" height="22" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M16 3L4 9v8c0 8 6 15 12 17 6-2 12-9 12-17V9L16 3z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M10 17c2 3 4.5 4.5 6 4.5s4-1.5 6-4.5" stroke="currentColor" stroke-width="1.35" stroke-linecap="round"/><path d="M6 24c2.5-1 5-1.5 10-1.5s7.5.5 10 1.5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" opacity=".5"/></svg>
            </span>
            <span class="logo-text"><?= e(APP_NAME) ?></span>
        </a>

        <button class="nav-toggle" id="navToggle" aria-expanded="false" aria-controls="mainNav" aria-label="Toggle navigation">
            <span></span><span></span><span></span>
        </button>

        <nav class="main-nav" id="mainNav" role="navigation" aria-label="Main navigation">
            <?php if (!$isLanding): ?>
            <a href="<?= e(url_for('event_feed')) ?>">Events</a>
            <?php endif; ?>
            <?php if (current_user_id() !== null): ?>
                <?php if (user_has_role('organizer')): ?>
                    <a href="<?= e(url_for('dashboard')) ?>">Dashboard</a>
                <?php endif; ?>
                <?php if (user_has_role('admin')): ?>
                    <a href="<?= e(url_for('admin_events')) ?>">Admin Events</a>
                    <a href="<?= e(url_for('admin_users')) ?>">Users</a>
                <?php endif; ?>
                <form action="<?= e(BASE_URL . 'actions/auth/logout.php') ?>" method="post" class="inline-form">
                    <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">
                    <button type="submit" aria-label="Log out">Logout</button>
                </form>
            <?php else: ?>
                <a href="<?= e(url_for('login')) ?>">Login</a>
                <a href="<?= e(url_for('register')) ?>">Register</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="site-main<?= !empty($authFluidMain) ? ' site-main--fluid' : ' container' ?><?= $isLanding ? ' site-main--landing' : '' ?>" id="main-content">
<script>
(function() {
    const btn = document.getElementById('navToggle');
    const nav = document.getElementById('mainNav');
    if (btn && nav) {
        btn.addEventListener('click', function() {
            const open = nav.classList.toggle('open');
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }
})();
</script>
