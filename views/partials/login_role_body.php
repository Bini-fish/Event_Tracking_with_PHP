<?php
declare(strict_types=1);

if (!isset($loginRole) || !in_array($loginRole, ['attendee', 'organizer', 'admin'], true)) {
    throw new InvalidArgumentException('Invalid login role view.');
}

require_once __DIR__ . '/hawassa_svg_icons.php';

$oldInput = get_old_input();
$loginOld = is_array($oldInput['login'] ?? null) ? $oldInput['login'] : [];
$oldEmail = (string) ($loginOld['email'] ?? '');

$loginRoleThemes = [
    'attendee' => [
        'title'       => 'Attendee',
        'headline'    => 'Welcome back',
        'subtitle'    => 'Browse events, RSVP, and share feedback.',
        'accent'      => '#3B82F6',
        'accentSoft'  => 'rgba(59, 130, 246, 0.12)',
        'accentGlow'  => 'rgba(59, 130, 246, 0.45)',
        'demoEmail'   => 'meron@hawassa.et',
    ],
    'organizer' => [
        'title'       => 'Organizer',
        'headline'    => 'Organizer portal',
        'subtitle'    => 'Create events, manage RSVPs, and review feedback.',
        'accent'      => '#10B981',
        'accentSoft'  => 'rgba(16, 185, 129, 0.12)',
        'accentGlow'  => 'rgba(16, 185, 129, 0.45)',
        'demoEmail'   => 'dawit@hawassa.et',
    ],
    'admin' => [
        'title'       => 'Admin',
        'headline'    => 'Administrator access',
        'subtitle'    => 'Approve events, oversee RSVPs, and manage the platform.',
        'accent'      => '#F97316',
        'accentSoft'  => 'rgba(249, 115, 22, 0.12)',
        'accentGlow'  => 'rgba(249, 115, 22, 0.45)',
        'demoEmail'   => 'admin@cityevents.local',
    ],
];

$theme = $loginRoleThemes[$loginRole];
$redirectParam = isset($_GET['redirect']) ? (string) $_GET['redirect'] : '';
$roleIconSvg = $HAWASSA_SVG['role_' . $loginRole] ?? '';
?>
<div class="auth-immersive" style="--auth-accent: <?= e($theme['accent']) ?>; --auth-accent-soft: <?= e($theme['accentSoft']) ?>; --auth-accent-glow: <?= e($theme['accentGlow']) ?>;">
    <div class="auth-immersive-bg" aria-hidden="true"></div>
    <div class="auth-immersive-inner">
        <a class="auth-back-link" href="<?= e(url_for('login')) ?>">← All sign-in options</a>
        <div class="auth-login-card auth-animate-in">
            <div class="auth-role-badge auth-role-badge--interactive">
                <span class="auth-role-icon hawassa-icon-wrap" style="color: <?= e($theme['accent']) ?>;"><?= $roleIconSvg ?></span>
            </div>
            <h1 class="auth-login-title"><?= e($theme['headline']) ?></h1>
            <p class="auth-role-chip" style="background: <?= e($theme['accentSoft']) ?>; color: <?= e($theme['accent']) ?>;"><?= e($theme['title']) ?></p>
            <p class="auth-login-sub"><?= e($theme['subtitle']) ?></p>

            <form action="<?= e(BASE_URL . 'actions/auth/login.php') ?>" method="post" class="auth-form auth-form-premium validated-form" id="roleLoginForm" data-auth-login="1">
                <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">
                <input type="hidden" name="redirect" value="<?= e($redirectParam) ?>">

                <div class="float-field">
                    <input type="email" name="email" id="login-email" value="<?= e($oldEmail) ?>" autocomplete="email"
                           required class="form-control float-input" placeholder=" "
                           data-validate-label="Email">
                    <label class="float-label" for="login-email">Email</label>
                    <span class="field-status-icon" aria-hidden="true"></span>
                </div>

                <div class="float-field float-field-password">
                    <input type="password" name="password" id="login-password" autocomplete="current-password"
                           required class="form-control float-input" placeholder=" "
                           data-validate-label="Password">
                    <label class="float-label" for="login-password">Password</label>
                    <button type="button" class="password-toggle" data-password-toggle="login-password" aria-label="Show password" tabindex="0">
                        <span class="password-toggle-eye password-toggle-eye--open" aria-hidden="true">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </span>
                        <span class="password-toggle-eye password-toggle-eye--closed" aria-hidden="true">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.274M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                        </span>
                    </button>
                </div>

                <label class="auth-remember">
                    <input type="checkbox" name="remember_me" value="1">
                    <span>Remember me for 30 days</span>
                </label>

                <button type="submit" class="button auth-submit-btn" id="roleLoginBtn"><?= e('Sign in as ' . $theme['title']) ?></button>

                <p class="auth-demo-hint">
                    Demo: <strong><?= e($theme['demoEmail']) ?></strong> · password <code>password</code>
                </p>
            </form>

            <p class="auth-alt-link">No account? <a href="<?= e(url_for('register')) ?>">Create one</a></p>
        </div>
    </div>
</div>
<script>
document.getElementById('roleLoginForm')?.addEventListener('submit', function () {
    try { sessionStorage.setItem('cityauth_login_submitted_at', String(Date.now())); } catch (e) {}
    var b = document.getElementById('roleLoginBtn');
    if (b) { b.disabled = true; b.textContent = 'Signing in…'; }
});
</script>
