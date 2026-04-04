<?php
// Global page footer layout.

declare(strict_types=1);

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/helpers.php';

$footerPage = $GLOBALS['app_page'] ?? ($_GET['page'] ?? '');
$isLandingFooter = $footerPage === 'landing';
$showLoginSuccessOverlay = current_user_id() !== null
    && in_array($footerPage, ['event_feed', 'dashboard', 'admin_events', 'admin_users', 'event_detail'], true);
?>
</main>
<?php if (!$isLandingFooter): ?>
<footer class="site-footer">
    <div class="container">
        <p>&copy; <?= date('Y') ?> <?= e(APP_NAME) ?></p>
    </div>
</footer>
<?php endif; ?>
<script src="<?= e(BASE_URL . 'assets/js/main.js') ?>"></script>
<script src="<?= e(BASE_URL . 'assets/js/auth-ui.js') ?>"></script>
<?php if ($showLoginSuccessOverlay): ?>
<div id="authWelcomeOverlay" class="flash-overlay flash-overlay--success flash-overlay--hidden" hidden>
    <div class="flash-overlay-backdrop"></div>
    <div class="flash-overlay-card flash-animate-pop">
        <div class="flash-overlay-lottie">
            <lottie-player
                src="<?= e(BASE_URL . 'assets/lottie/success.json') ?>"
                background="transparent"
                speed="1"
                style="width: 140px; height: 140px;"
                loop="false"
                autoplay>
            </lottie-player>
        </div>
        <p class="flash-overlay-text">Signed in successfully. Welcome back!</p>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var overlay = document.getElementById('authWelcomeOverlay');
    if (!overlay) return;
    try {
        var t = sessionStorage.getItem('cityauth_login_submitted_at');
        if (!t) return;
        if (Date.now() - parseInt(t, 10) > 12000) {
            sessionStorage.removeItem('cityauth_login_submitted_at');
            return;
        }
        sessionStorage.removeItem('cityauth_login_submitted_at');
        overlay.hidden = false;
        overlay.classList.remove('flash-overlay--hidden');
        setTimeout(function () {
            overlay.classList.add('flash-overlay--exit');
            setTimeout(function () { overlay.remove(); }, 380);
        }, 4000);
    } catch (e) {}
});
</script>
<?php endif; ?>
</body>
</html>

