<?php
// Global page header and navigation bar layout.

declare(strict_types=1);

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/helpers.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= e(APP_NAME) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= e(BASE_URL . 'assets/css/style.css') ?>">
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <a href="<?= e(url_for('event_feed')) ?>" class="logo"><?= e(APP_NAME) ?></a>
        <nav class="main-nav">
            <a href="<?= e(url_for('event_feed')) ?>">Discover Events</a>
            <?php if (current_user_id() !== null): ?>
                <?php if (user_has_role('organizer')): ?>
                    <a href="<?= e(url_for('dashboard')) ?>">Dashboard</a>
                <?php endif; ?>
                <?php if (user_has_role('admin')): ?>
                    <a href="<?= e(url_for('admin_events')) ?>">Admin Panel</a>
                <?php endif; ?>
                <form action="<?= e(BASE_URL . 'actions/auth/logout.php') ?>" method="post" class="inline-form">
                    <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">
                    <button type="submit">Logout</button>
                </form>
            <?php else: ?>
                <a href="<?= e(url_for('login')) ?>">Login</a>
                <a href="<?= e(url_for('register')) ?>">Register</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="site-main container">
