<?php
declare(strict_types=1);

require_once __DIR__ . '/../partials/hawassa_svg_icons.php';

$oldInput = get_old_input();
$registerOld = is_array($oldInput['register'] ?? null) ? $oldInput['register'] : [];
$oldName  = (string) ($registerOld['name'] ?? '');
$oldEmail = (string) ($registerOld['email'] ?? '');
$oldRole  = (string) ($registerOld['role'] ?? 'attendee');
?>
<div class="auth-immersive auth-immersive--register">
    <div class="auth-immersive-bg auth-immersive-bg--neutral" aria-hidden="true"></div>
    <div class="auth-immersive-inner">
        <a class="auth-back-link" href="<?= e(url_for('home')) ?>">← Home</a>
        <div class="auth-login-card auth-register-card auth-animate-in">
            <div class="auth-role-badge auth-role-badge--neutral auth-role-badge--interactive">
                <span class="auth-role-icon hawassa-icon-wrap" style="color: var(--hawassa-gold, #c9a227);">
                    <?= $HAWASSA_SVG['register_jebena'] ?? '' ?>
                </span>
            </div>
            <h1 class="auth-login-title">Create your account</h1>
            <p class="auth-login-sub">Join Hawassa’s verified event community — quick setup, clear validation, and a warm welcome.</p>

            <form action="<?= e(BASE_URL . 'actions/auth/register.php') ?>" method="post" class="auth-form auth-form-premium validated-form" id="regForm" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">

                <div class="float-field">
                    <input type="text" name="name" id="reg-name" value="<?= e($oldName) ?>" autocomplete="name"
                           required class="form-control float-input js-register-field" placeholder=" "
                           data-validate-label="Full name"
                           minlength="2" maxlength="120">
                    <label class="float-label" for="reg-name">Full name</label>
                    <span class="field-status-icon" aria-hidden="true"></span>
                </div>

                <div class="float-field">
                    <input type="email" name="email" id="reg-email" value="<?= e($oldEmail) ?>" autocomplete="email"
                           required class="form-control float-input js-register-field" placeholder=" "
                           data-validate-label="Email">
                    <label class="float-label" for="reg-email">Email</label>
                    <span class="field-status-icon" aria-hidden="true"></span>
                </div>

                <div class="float-field float-field-password">
                    <input type="password" name="password" id="reg-password" autocomplete="new-password" required
                           minlength="8" class="form-control float-input js-register-field js-pw" placeholder=" "
                           pattern="(?=.*[A-Za-z])(?=.*\d).{8,}"
                           title="At least 8 characters with at least one letter and one number."
                           data-validate-label="Password"
                           data-validate-password="1">
                    <label class="float-label" for="reg-password">Password</label>
                    <span class="field-status-icon" aria-hidden="true"></span>
                    <button type="button" class="password-toggle" data-password-toggle="reg-password" aria-label="Show password" tabindex="0">
                        <span class="password-toggle-eye password-toggle-eye--open" aria-hidden="true">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </span>
                        <span class="password-toggle-eye password-toggle-eye--closed" aria-hidden="true">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.274M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                        </span>
                    </button>
                </div>

                <div class="float-field float-field-password">
                    <input type="password" name="confirm_password" id="reg-confirm" autocomplete="new-password" required
                           minlength="8" class="form-control float-input js-register-field js-pw-confirm" placeholder=" "
                           data-validate-label="Confirm password"
                           data-validate-confirm="#reg-password">
                    <label class="float-label" for="reg-confirm">Confirm password</label>
                    <span class="field-status-icon" aria-hidden="true"></span>
                    <button type="button" class="password-toggle" data-password-toggle="reg-confirm" aria-label="Show password" tabindex="0">
                        <span class="password-toggle-eye password-toggle-eye--open" aria-hidden="true">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </span>
                        <span class="password-toggle-eye password-toggle-eye--closed" aria-hidden="true">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.274M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                        </span>
                    </button>
                </div>

                <fieldset class="auth-role-fieldset">
                    <legend class="auth-role-legend">I want to…</legend>
                    <label class="auth-role-option <?= $oldRole !== 'organizer' ? 'is-selected' : '' ?>">
                        <input type="radio" name="role" value="attendee" <?= $oldRole !== 'organizer' ? 'checked' : '' ?>>
                        <span class="auth-role-option-body">
                            <span class="auth-role-option-title">Attend events</span>
                            <span class="auth-role-option-desc">Discover, RSVP, and give feedback</span>
                        </span>
                    </label>
                    <label class="auth-role-option <?= $oldRole === 'organizer' ? 'is-selected' : '' ?>">
                        <input type="radio" name="role" value="organizer" <?= $oldRole === 'organizer' ? 'checked' : '' ?>>
                        <span class="auth-role-option-body">
                            <span class="auth-role-option-title">Organize events</span>
                            <span class="auth-role-option-desc">Create listings and manage RSVPs</span>
                        </span>
                    </label>
                </fieldset>

                <button type="submit" class="button primary auth-submit-btn" id="regBtn">Create account</button>
            </form>

            <p class="auth-alt-link">Already registered? <a href="<?= e(url_for('login')) ?>">Sign in</a></p>
        </div>
    </div>
</div>
<script>
document.getElementById('regForm')?.addEventListener('submit', function () {
    var b = document.getElementById('regBtn');
    if (b) { b.disabled = true; b.textContent = 'Creating account…'; }
});
</script>
