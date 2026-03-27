<?php
// Simple 404 not-found page.

declare(strict_types=1);
?>
<section class="not-found">
    <h1>Page Not Found</h1>
    <p>The page you are looking for does not exist.</p>
    <a class="button" href="<?= e(url_for('event_feed')) ?>">Back to events</a>
</section>

