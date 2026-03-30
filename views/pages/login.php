<?php
// Login page with CSRF protection and old-input refill support.

declare(strict_types=1);

$oldInput = get_old_input();
$loginOld = is_array($oldInput['login'] ?? null) ? $oldInput['login'] : [];
$oldEmail = (string) ($loginOld['email'] ?? '');
?>
<section class="auth-page">
    <h1>Login</h1>
    <form action="<?= e(BASE_URL . 'actions/auth/login.php') ?>" method="post" class="auth-form">
        <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">
        <input type="hidden" name="redirect" value="<?= e(isset($_GET['redirect']) ? (string) $_GET['redirect'] : '') ?>">
        <label>
            Email
            <input type="email" name="email" value="<?= e($oldEmail) ?>" autocomplete="email" required>
        </label>
        <label>
            Password
            <input type="password" name="password" autocomplete="current-password" required>
        </label>
        <label style="flex-direction:row;align-items:center;gap:0.5rem;">
            <input type="checkbox" name="remember_me" value="1">
            <span>Remember me for 30 days</span>
        </label>
        <button type="submit" class="button primary">Login</button>
    </form>
</section>

