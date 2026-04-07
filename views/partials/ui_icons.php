<?php
declare(strict_types=1);

/**
 * Lucide-style stroke icons for inline UI (24×24 viewBox, 1.5 stroke). Presentation only.
 */
function ui_icon(string $name, array $opts = []): string
{
    $class = htmlspecialchars($opts['class'] ?? 'ui-icon-svg', ENT_QUOTES, 'UTF-8');
    $size  = (int) ($opts['size'] ?? 24);

    $inner = match ($name) {
        'calendar' => '<path d="M8 2v4m8-4v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>',
        'map_pin' => '<path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="10" r="3" stroke="currentColor" stroke-width="1.5"/>',
        'ticket' => '<path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M13 5v2M13 17v2M13 11v2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',
        'clock' => '<circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"/><path d="M12 6v6l4 2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',
        'check_circle' => '<circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"/><path d="m9 12 2 2 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>',
        'circle_plus' => '<circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"/><path d="M8 12h8m-4-4v8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',
        'list' => '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',
        'plus' => '<path d="M5 12h14M12 5v14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="1.5"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>',
        'search' => '<circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="1.5"/><path d="m21 21-4.3-4.3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',
        'clipboard_list' => '<rect width="8" height="4" x="8" y="2" rx="1" ry="1" stroke="currentColor" stroke-width="1.5"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 11h4M12 16h4M8 11h.01M8 16h.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',
        'x' => '<path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',
        'check' => '<path d="M20 6 9 17l-5-5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>',
        'alert_triangle' => '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 9v4M12 17h.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',
        'tag' => '<path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><circle cx="7.5" cy="7.5" r=".5" fill="currentColor" stroke="none"/>',
        'star' => '<path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>',
        'lock' => '<rect width="18" height="11" x="3" y="11" rx="2" ry="2" stroke="currentColor" stroke-width="1.5"/><path d="M7 11V7a5 5 0 0 1 10 0v4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',
        'pencil' => '<path d="M17 3a2.85 2.83 0 0 1 4 4L7.5 20.5 2 22l1.5-5.5Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="m15 5 4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',
        'trash_2' => '<path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M10 11v6M14 11v6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>',
        'user_plus' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="1.5"/><path d="M19 8v6M22 11h-6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',
        'user' => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="1.5"/>',
        'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>',
        'info' => '<circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"/><path d="M12 16v-4M12 8h.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',
        default => '',
    };

    if ($inner === '') {
        return '';
    }

    return '<svg class="' . $class . '" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">' . $inner . '</svg>';
}

/**
 * Read-only row of stars (filled count, 1–5).
 */
function ui_stars_display(int $filled, int $max = 5): string
{
    $filled = max(0, min($max, $filled));
    $out     = '<span class="rating-stars-display" aria-hidden="true">';
    for ($i = 0; $i < $max; $i++) {
        $on  = $i < $filled;
        $cls = $on ? 'rating-star rating-star--on' : 'rating-star rating-star--off';
        $out .= '<span class="' . $cls . '">' . ui_icon('star', ['size' => 14, 'class' => 'rating-star-svg']) . '</span>';
    }
    return $out . '</span>';
}
