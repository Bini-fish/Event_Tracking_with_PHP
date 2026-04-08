<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/helpers.php';

$isLanding = isset($page) && $page === 'landing';
$isAuthPage = isset($page) && in_array($page, ['login', 'login_attendee', 'login_organizer', 'login_admin', 'register'], true);
$isLoggedIn = current_user_id() !== null;
$userRole = current_user_role();
$effectiveRole = current_effective_role();
$adminViewRole = $userRole === 'admin' ? (get_admin_view_role() ?? 'admin') : null;
$isAdminSwitchedView = $userRole === 'admin' && in_array((string) $adminViewRole, ['organizer', 'attendee'], true);

$homePage = 'landing';
if ($effectiveRole === 'admin') {
    $homePage = 'admin_events';
} elseif ($effectiveRole === 'organizer') {
    $homePage = 'dashboard';
} elseif ($effectiveRole === 'attendee') {
    $homePage = 'event_feed';
}

$homeAriaLabel = match ($homePage) {
    'admin_events' => 'Go to admin home',
    'dashboard' => 'Go to organizer dashboard',
    'event_feed' => 'Go to events home',
    default => 'Go to home',
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Discover and track city-wide events — RSVP, give feedback, and stay connected.">
    <title><?= e(APP_NAME) ?></title>
    <link rel="icon" type="image/png" href="<?= e(BASE_URL . 'assets/images/new FAVICON.png?v=20260408') ?>">
    <link rel="shortcut icon" type="image/png" href="<?= e(BASE_URL . 'assets/images/new FAVICON.png?v=20260408') ?>">
    <link rel="alternate icon" type="image/jpeg" href="<?= e(BASE_URL . 'assets/images/image.jpg?v=20260408') ?>">
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
        <a class="logo logo--symbol-only header-home-link" href="<?= e(url_for($homePage)) ?>" aria-label="<?= e($homeAriaLabel) ?>">
            <img src="<?= e(BASE_URL . 'assets/images/new FAVICON.png') ?>" alt="">
        </a>
        <?php endif; ?>

        <?php if (!$isLanding && !$isAuthPage): ?>
        <button class="nav-toggle" id="navToggle" aria-expanded="false" aria-controls="mainNav" aria-label="Toggle navigation">
            <span></span><span></span><span></span>
        </button>
        <?php endif; ?>

        <?php if (!$isAuthPage): ?>
        <nav class="main-nav" id="mainNav" role="navigation" aria-label="Main navigation">
            <?php if (!$isLoggedIn): ?>
                <a href="<?= e(url_for('event_feed')) ?>">Events</a>
                <a href="<?= e(url_for('login')) ?>">Login</a>
                <a href="<?= e(url_for('register')) ?>">Register</a>
            <?php elseif ($userRole === 'admin'): ?>
                <?php if ($adminViewRole === 'organizer'): ?>
                    <a href="<?= e(url_for('dashboard')) ?>">Organizer Dashboard</a>
                    <a href="<?= e(url_for('event_feed')) ?>">Events</a>
                    <a href="<?= e(url_for('admin_events')) ?>">Admin Panel</a>
                <?php elseif ($adminViewRole === 'attendee'): ?>
                    <a href="<?= e(url_for('event_feed')) ?>">Events</a>
                    <a href="<?= e(url_for('admin_events')) ?>">Admin Panel</a>
                <?php else: ?>
                    <a href="<?= e(url_for('admin_events')) ?>">Admin Events</a>
                    <a href="<?= e(url_for('admin_users')) ?>">User Management</a>
                    <a href="<?= e(url_for('event_feed')) ?>">Events</a>
                <?php endif; ?>

                <?php if ($adminViewRole !== 'organizer'): ?>
                    <form class="inline-form" action="<?= e(BASE_URL . 'actions/auth/switch_view_role.php') ?>" method="post">
                        <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">
                        <input type="hidden" name="view_role" value="organizer">
                        <button type="submit" class="nav-link-button nav-link-button--soft">Switch to Organizer</button>
                    </form>
                <?php endif; ?>

                <?php if ($adminViewRole !== 'attendee'): ?>
                    <form class="inline-form" action="<?= e(BASE_URL . 'actions/auth/switch_view_role.php') ?>" method="post">
                        <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">
                        <input type="hidden" name="view_role" value="attendee">
                        <button type="submit" class="nav-link-button nav-link-button--soft">Switch to Attendee</button>
                    </form>
                <?php endif; ?>

                <?php if ($adminViewRole !== 'admin'): ?>
                    <form class="inline-form" action="<?= e(BASE_URL . 'actions/auth/switch_view_role.php') ?>" method="post">
                        <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">
                        <input type="hidden" name="view_role" value="admin">
                        <button type="submit" class="nav-link-button nav-link-button--soft">Back to Admin View</button>
                    </form>
                <?php endif; ?>

                <form class="inline-form" action="<?= e(BASE_URL . 'actions/auth/logout.php') ?>" method="post">
                    <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">
                    <button type="submit" class="nav-link-button">Logout</button>
                </form>
            <?php elseif ($userRole === 'organizer'): ?>
                <a href="<?= e(url_for('event_feed')) ?>">Events</a>
                <a href="<?= e(url_for('dashboard')) ?>">Dashboard</a>
                <form class="inline-form" action="<?= e(BASE_URL . 'actions/auth/logout.php') ?>" method="post">
                    <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">
                    <button type="submit" class="nav-link-button">Logout</button>
                </form>
            <?php else: ?>
                <a href="<?= e(url_for('event_feed')) ?>">Events</a>
                <form class="inline-form" action="<?= e(BASE_URL . 'actions/auth/logout.php') ?>" method="post">
                    <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">
                    <button type="submit" class="nav-link-button">Logout</button>
                </form>
            <?php endif; ?>
        </nav>
        <?php endif; ?>
    </div>
</header>
<main class="site-main<?= !empty($authFluidMain) ? ' site-main--fluid' : ' container' ?><?= $isLanding ? ' site-main--landing' : '' ?>" id="main-content">
<?php if ($isAdminSwitchedView): ?>
    <div class="admin-view-notice" role="status" aria-live="polite">
        Signed in as admin - currently in <?= e((string) $adminViewRole) ?> view.
    </div>
<?php endif; ?>
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
