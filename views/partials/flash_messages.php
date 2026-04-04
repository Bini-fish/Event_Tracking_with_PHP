<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/helpers.php';

$flashes = get_flashes();
if (empty($flashes)) {
    return;
}

$primaryType = 'info';
foreach (['error', 'warning', 'success', 'info'] as $t) {
    if (!empty($flashes[$t])) {
        $primaryType = $t;
        break;
    }
}

$messagesFlat = [];
foreach ($flashes as $type => $items) {
    foreach ($items as $message) {
        $messagesFlat[] = ['type' => $type, 'text' => (string) $message];
    }
}

$firstText = $messagesFlat[0]['text'] ?? '';
$lottieFile = $primaryType === 'success' ? 'success.json' : 'error.json';
$overlayClass = 'flash-overlay flash-overlay--' . e($primaryType);
?>
<div class="<?= $overlayClass ?>" id="flashOverlay" role="alert" aria-live="assertive" data-flash-dismiss="4500">
    <div class="flash-overlay-backdrop" data-flash-close="1"></div>
    <div class="flash-overlay-card flash-animate-pop">
        <button type="button" class="flash-overlay-x" data-flash-close="1" aria-label="Dismiss">×</button>
        <div class="flash-overlay-lottie">
            <lottie-player
                src="<?= e(BASE_URL . 'assets/lottie/' . $lottieFile) ?>"
                background="transparent"
                speed="1"
                style="width: 140px; height: 140px;"
                <?= $primaryType === 'success' ? 'loop="false"' : 'loop="true"' ?>
                autoplay>
            </lottie-player>
        </div>
        <p class="flash-overlay-text"><?= e($firstText) ?></p>
        <?php if (count($messagesFlat) > 1): ?>
            <ul class="flash-overlay-list">
                <?php foreach (array_slice($messagesFlat, 1) as $row): ?>
                    <li><?= e($row['text']) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
<?php if ($primaryType === 'error'): ?>
<script>
try { sessionStorage.removeItem('cityauth_login_submitted_at'); } catch (e) {}
</script>
<?php endif; ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var overlay = document.getElementById('flashOverlay');
    if (!overlay) return;

    function closeOverlay() {
        overlay.classList.add('flash-overlay--exit');
        setTimeout(function () { overlay.remove(); }, 380);
    }

    var delay = parseInt(overlay.getAttribute('data-flash-dismiss'), 10) || 4500;
    var timer = setTimeout(closeOverlay, delay);

    overlay.querySelectorAll('[data-flash-close]').forEach(function (el) {
        el.addEventListener('click', function () {
            clearTimeout(timer);
            closeOverlay();
        });
    });
});
</script>
