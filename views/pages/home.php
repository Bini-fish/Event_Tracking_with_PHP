<?php
// Home page: simple landing pointing users to the event feed.

declare(strict_types=1);
?>
<section class="hero">
    <h1>Discover What&apos;s Happening In Your City</h1>
    <p>Browse verified events, RSVP in one click, and never miss what matters.</p>
    <a class="button primary" href="<?= e(url_for('event_feed')) ?>">Browse Events</a>
</section>

