<?php
// Handle organizer event creation (with optional image upload).
declare(strict_types=1);

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
$capacity    = request_int($_POST, 'capacity');

foreach ([
    validate_required($title, 'Title'),
    validate_max_length($title, 150, 'Title'),
    validate_required($location, 'Location'),
    validate_max_length($location, 150, 'Location'),
    validate_required($description, 'Description'),
    validate_max_length($description, 5000, 'Description'),
    validate_event_datetime_format($eventDate, 'Event date'),
    validate_event_datetime_bounds($eventDate, 300, 60 * 60 * 24 * 365 * 10, 'Event date'),
    validate_positive_int($capacity, 'Capacity'),
] as $err) {
    if ($err !== true) {
        set_flash('error', $err);
        redirect('dashboard');
    }
}

// Optional image upload.
$imagePath = null;
if (isset($_FILES['image']) && is_array($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
    $tmpName = (string) ($_FILES['image']['tmp_name'] ?? '');
    $size    = (int) ($_FILES['image']['size'] ?? 0);
    $maxSize = 2 * 1024 * 1024; // 2MB
    $allowedMimeToExt = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
    ];

    if ($size <= 0 || $size > $maxSize || !is_uploaded_file($tmpName)) {
        set_flash('error', 'Image must be a valid uploaded file under 2MB.');
        redirect('dashboard');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = (string) $finfo->file($tmpName);
    $ext   = $allowedMimeToExt[$mime] ?? null;

    if ($ext === null) {
        set_flash('error', 'Only JPG, PNG, and GIF images are allowed.');
        redirect('dashboard');
    }

    $uploadDir = __DIR__ . '/../../../public/uploads';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        set_flash('error', 'Could not prepare upload directory.');
        redirect('dashboard');
    }

    $newName    = bin2hex(random_bytes(16)) . '.' . $ext;
    $uploadPath = $uploadDir . '/' . $newName;

    if (!move_uploaded_file($tmpName, $uploadPath)) {
        set_flash('error', 'Could not store uploaded image.');
        redirect('dashboard');
    }

    $imagePath = 'uploads/' . $newName;
}

try {
    $eventId = event_create(
        current_user_id() ?? 0,
        $title,
        $description,
        $imagePath,
        $location,
        $eventDate,
        $capacity
    );
} catch (PDOException $e) {
    log_exception($e, 'Create event DB write error');
    set_flash('error', 'Something went wrong. Please try again.');
    redirect('dashboard');
} catch (Throwable $e) {
    log_exception($e, 'Create event write error');
    set_flash('error', 'Something went wrong. Please try again.');
    redirect('dashboard');
}

if ($eventId === null) {
    set_flash('error', 'Could not create event. Please try again.');
} else {
    set_flash('success', 'Event submitted and pending admin approval.');
}

redirect('dashboard');

