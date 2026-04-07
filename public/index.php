<?php
// Front controller: bootstraps the app and routes to the correct page.

declare(strict_types=1);

require __DIR__ . '/../config/config.php';
require __DIR__ . '/../includes/session.php';
require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../config/routes.php';

$requestedPage = isset($_GET['page']) ? (string) $_GET['page'] : '';

$host = (string) ($_SERVER['HTTP_HOST'] ?? '');
$remoteAddr = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
$isLocalhost = str_starts_with($host, 'localhost')
    || str_starts_with($host, '127.0.0.1')
    || str_starts_with($host, '[::1]')
    || $remoteAddr === '127.0.0.1'
    || $remoteAddr === '::1';

$page = $requestedPage !== ''
    ? $requestedPage
    : ($isLocalhost ? 'landing' : (current_user_id() !== null ? 'event_feed' : 'landing'));

$GLOBALS['app_page'] = $page;

// Resolve the view file from the routes map; fall back to 404.
$viewFile = $routes[$page] ?? null;

if ($viewFile === null) {
    http_response_code(404);
    $viewFile = __DIR__ . '/../views/pages/404.php';
}

$authFluidMain = in_array(
    $page,
    ['landing', 'home', 'login', 'login_attendee', 'login_organizer', 'login_admin', 'register'],
    true
);

// Shared layout: header → page content → footer.
require __DIR__ . '/../views/layout/header.php';
require __DIR__ . '/../views/partials/flash_messages.php';
require $viewFile;
require __DIR__ . '/../views/layout/footer.php';

