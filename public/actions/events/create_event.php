<?php
// Organiser event creation with start/end datetime validation.
declare(strict_types=1);

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/session.php';
require_once __DIR__ . '/../../../includes/helpers.php';
require_once __DIR__ . '/../../../includes/validation.php';
require_once __DIR__ . '/../../../includes/auth_guard.php';
require_once __DIR__ . '/../../../includes/policy.php';
require_once __DIR__ . '/../../../models/EventModel.php';

require_organizer();

if (!can_create_event(current_user_id() ?? 0)) {
    set_flash('error', 'You do not have permission to create events.');
    redirect('event_feed');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('dashboard');
}

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    set_flash('error', 'Invalid form submission.');
    redirect('dashboard');
}

$title       = request_string($_POST, 'title');
$description = request_string($_POST, 'description');
$location    = request_string($_POST, 'location');
$eventDate   = request_string($_POST, 'event_date');
$eventEnd    = request_string($_POST, 'event_end');
$capacity    = request_int($_POST, 'capacity');
$orgId       = current_user_id() ?? 0;

// Basic validation.
foreach ([
    validate_required($title, 'Title'),
    validate_max_length($title, 150, 'Title'),
    validate_required($location, 'Location'),
    validate_max_length($location, 150, 'Location'),
    validate_required($description, 'Description'),
    validate_max_length($description, 5000, 'Description'),
    validate_event_datetime_format($eventDate, 'Start date'),
    validate_event_datetime_bounds($eventDate, 300, 60 * 60 * 24 * 365 * 10, 'Start date'),
    validate_event_datetime_format($eventEnd, 'End date'),
    validate_positive_int($capacity, 'Capacity'),
] as $err) {
    if ($err !== true) {
        set_flash('error', $err);
        redirect('dashboard');
    }
}

// Start < End and minimum duration.
$dtStart = parse_event_datetime($eventDate);
$dtEnd   = parse_event_datetime($eventEnd);

if ($dtEnd <= $dtStart) {
    set_flash('error', 'End date/time must be after the start date/time.');
    redirect('dashboard');
}

$durationMinutes = ($dtEnd->getTimestamp() - $dtStart->getTimestamp()) / 60;
if ($durationMinutes < MIN_EVENT_DURATION_MINUTES) {
    set_flash('error', 'Event must be at least ' . MIN_EVENT_DURATION_MINUTES . ' minutes long.');
    redirect('dashboard');
}

// Organiser buffer check (non-blocking: show warning, still create).
$bufferStart = $dtStart->modify('-' . ORGANIZER_BUFFER_MINUTES . ' minutes')->format('Y-m-d H:i:s');
$bufferEnd   = $dtEnd->modify('+' . ORGANIZER_BUFFER_MINUTES . ' minutes')->format('Y-m-d H:i:s');
$overlaps = event_detect_organizer_overlap($orgId, $bufferStart, $bufferEnd);
if (!empty($overlaps)) {
    $titles = implode(', ', array_column($overlaps, 'title'));
    set_flash('warning', "Note: this event is close to or overlaps your other event(s): {$titles}. Create proceeded anyway.");
}

// Image upload.
$imagePath = null;
if (isset($_FILES['image']) && is_array($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
    $imagePath = _handle_image_upload();
    if ($imagePath === false) {
        redirect('dashboard');
    }
}

try {
    $eventId = event_create($orgId, $title, $description, $imagePath, $location,
        $dtStart->format('Y-m-d H:i:s'), $dtEnd->format('Y-m-d H:i:s'), $capacity);
} catch (Throwable $e) {
    log_exception($e, 'Create event DB');
    set_flash('error', 'Something went wrong. Please try again.');
    redirect('dashboard');
}

if ($eventId === null) {
    set_flash('error', 'Could not create event. Please try again.');
} else {
    set_flash('success', 'Event submitted and pending admin approval.');
}
redirect('dashboard');

// ── helpers local to this file ────────────────────────────────────────────
function _handle_image_upload(): string|false
{
    $tmpName = (string) ($_FILES['image']['tmp_name'] ?? '');
    $size    = (int) ($_FILES['image']['size'] ?? 0);
    $maxSize = 2 * 1024 * 1024;
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];

    if ($size <= 0 || $size > $maxSize || !is_uploaded_file($tmpName)) {
        set_flash('error', 'Image must be a valid file under 2 MB.');
        return false;
    }
    $mime = (string) (new finfo(FILEINFO_MIME_TYPE))->file($tmpName);
    $ext  = $allowed[$mime] ?? null;
    if ($ext === null) {
        set_flash('error', 'Only JPG, PNG, and GIF images are allowed.');
        return false;
    }
    $uploadDir = __DIR__ . '/../../../public/uploads';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        set_flash('error', 'Could not prepare upload directory.');
        return false;
    }
    $newName = bin2hex(random_bytes(16)) . '.' . $ext;
    if (!move_uploaded_file($tmpName, $uploadDir . '/' . $newName)) {
        set_flash('error', 'Could not store uploaded image.');
        return false;
    }
    return 'uploads/' . $newName;
}
