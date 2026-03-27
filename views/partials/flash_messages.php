<?php
// Flash message partial used to show success/error notices.

declare(strict_types=1);

require_once __DIR__ . '/../../includes/helpers.php';

$flashes = get_flashes();

if (!empty($flashes)): ?>
    <section class="flash-messages">
        <?php foreach ($flashes as $type => $messages): ?>
            <?php foreach ($messages as $message): ?>
                <div class="flash flash-<?= e($type) ?>"><?= e($message) ?></div>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

