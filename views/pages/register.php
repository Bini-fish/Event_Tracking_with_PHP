<?php
// Registration page: choose role (attendee or organizer).

declare(strict_types=1);

$oldInput = get_old_input();
$registerOld = is_array($oldInput['register'] ?? null) ? $oldInput['register'] : [];
$oldName = (string) ($registerOld['name'] ?? '');
$oldEmail = (string) ($registerOld['email'] ?? '');
$oldRole = (string) ($registerOld['role'] ?? 'attendee');
?>
<section class="auth-page">
    <h1>Create an Account</h1>
    <form action="<?= e(BASE_URL . 'actions/auth/register.php') ?>" method="post" class="auth-form">
        <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">
        <label>
            Name
            <input type="text" name="name" value="<?= e($oldName) ?>" required>
        </label>
        <label>
            Email
            <input type="email" name="email" value="<?= e($oldEmail) ?>" required>
        </label>
        <label>
            Password
            <input
                type="password"
                name="password"
                minlength="8"
                pattern="(?=.*[A-Za-z])(?=.*\d).{8,}"
                title="At least 8 characters with at least one letter and one number."
                autocomplete="new-password"
                required
            >
        </label>
        <label>
            Confirm Password
            <input
                type="password"
                name="confirm_password"
                minlength="8"
                autocomplete="new-password"
                required
            >
        </label>
        <fieldset style="border:1px solid var(--color-border-subtle);border-radius:10px;padding:0.7rem 1rem;">
            <legend style="font-size:0.85rem;color:var(--color-text-muted);padding:0 0.4rem;">I want to…</legend>
            <label style="flex-direction:row;align-items:center;gap:0.5rem;cursor:pointer;">
                <input type="radio" name="role" value="attendee" <?= $oldRole !== 'organizer' ? 'checked' : '' ?>>
                <span>Attend events (Attendee)</span>
            </label>
            <label style="flex-direction:row;align-items:center;gap:0.5rem;cursor:pointer;margin-top:0.4rem;">
                <input type="radio" name="role" value="organizer" <?= $oldRole === 'organizer' ? 'checked' : '' ?>>
                <span>Create &amp; manage events (Organizer)</span>
            </label>
        </fieldset>
        <button type="submit" class="button primary">Register</button>
        <p style="text-align:center;font-size:0.85rem;color:var(--color-text-muted);margin:0;">
            Already have an account? <a href="<?= e(url_for('login')) ?>" style="color:var(--color-primary);">Login</a>
        </p>
    </form>
</section>
