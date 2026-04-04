<?php
declare(strict_types=1);

$landingBg = BASE_URL . 'assets/images/image.jpg';
?>
<div class="landing-page" id="hawassaLanding">
    <div class="landing-page__bg" style="background-image: url('<?= e($landingBg) ?>');" data-parallax-bg aria-hidden="true"></div>
    <div class="landing-page__scrim" aria-hidden="true"></div>
    <div class="landing-page__vignette" aria-hidden="true"></div>

    <div class="landing-page__content">
        <p class="landing-page__eyebrow landing-reveal">Lake Hawassa · Ethiopia</p>
        <h1 class="landing-page__title landing-reveal landing-reveal--2">
            Where city lights meet <span class="landing-page__accent">living tradition</span>
        </h1>
        <p class="landing-page__lead landing-reveal landing-reveal--3">
            Celebrate verified city events—from lakeside gatherings to cultural nights—then RSVP, share feedback, and stay close to your community.
        </p>
        <div class="landing-page__actions landing-reveal landing-reveal--4">
            <a class="landing-page__cta" href="<?= e(url_for('home')) ?>">
                <span class="landing-page__cta-label">Explore the platform</span>
                <span class="landing-page__cta-icon" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </span>
            </a>
            <a class="landing-page__ghost" href="<?= e(url_for('login')) ?>">Sign in</a>
        </div>
        <p class="landing-page__note landing-reveal landing-reveal--5">City-level event tracking for Hawassa — modern, trusted, and community-first.</p>
    </div>
</div>
<script src="<?= e(BASE_URL . 'assets/js/landing.js') ?>" defer></script>
