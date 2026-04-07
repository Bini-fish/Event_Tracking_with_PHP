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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(BASE_URL . 'assets/css/style.css') ?>">
    <script src="https://unpkg.com/@lottiefiles/lottie-player@2.0.8/dist/lottie-player.js" defer></script>
</head>
<body class="<?= $isLanding ? 'page-is-landing' : '' ?>">
<header class="site-header<?= $isLanding ? ' site-header--landing' : '' ?>" role="banner">
    <div class="container header-inner">
        <?php if (!$isLanding): ?>
        <button class="nav-toggle" id="navToggle" aria-expanded="false" aria-controls="mainNav" aria-label="Toggle navigation">
            <span></span><span></span><span></span>
        </button>
        <?php endif; ?>

        <nav class="main-nav" id="mainNav" role="navigation" aria-label="Main navigation">
            <a href="<?= e(url_for('event_feed')) ?>">Events</a>
            <a href="<?= e(url_for('login')) ?>">Login</a>
            <a href="<?= e(url_for('register')) ?>">Register</a>
        </nav>
    </div>
</header>
<main class="site-main<?= !empty($authFluidMain) ? ' site-main--fluid' : ' container' ?><?= $isLanding ? ' site-main--landing' : '' ?>" id="main-content">
<script>
(function() {
    var btn = document.getElementById('navToggle');
    var nav = document.getElementById('mainNav');
    if (!btn || !nav) return;
    var isMenuOpen = false;

    function renderMenu() {
        nav.style.display = isMenuOpen ? 'block' : 'none';
        btn.setAttribute('aria-expanded', isMenuOpen ? 'true' : 'false');
    }

    btn.addEventListener('click', function() {
        isMenuOpen = !isMenuOpen;
        renderMenu();
    });

    nav.querySelectorAll('a').forEach(function(el) {
        el.addEventListener('click', function() {
            isMenuOpen = false;
            renderMenu();
        });
    });

    renderMenu();
})();
</script>
