<?php
// Login page: placeholder form; validation and handling come later.

declare(strict_types=1);
?>
<section class="auth-page">
    <h1>Login</h1>
    <form action="<?= e(BASE_URL . 'actions/auth/login.php') ?>" method="post" class="auth-form">
        <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">
        <input type="hidden" name="redirect" value="<?= e(isset($_GET['redirect']) ? (string) $_GET['redirect'] : '') ?>">
        <label>
            Email
            <input type="email" name="email" required>
        </label>
        <label>
            Password
            <input type="password" name="password" required>
        </label>
        <button type="submit" class="button primary">Login</button>
    </form>
</section>

