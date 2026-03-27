<?php
// Handle organizer event creation (with optional image upload).
declare(strict_types=1);

require_once __DIR__ . '/../../../includes/session.php';
require_once __DIR__ . '/../../../includes/helpers.php';
require_once __DIR__ . '/../../../includes/validation.php';
require_once __DIR__ . '/../../../includes/auth_guard.php';
require_once __DIR__ . '/../../../models/EventModel.php';

require_organizer();

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

if (
    !validate_required_string($title)
    || !validate_required_string($location)
    || !validate_event_datetime($eventDate)
    || !validate_positive_int($capacity)
) {
    set_flash('error', 'Please fill in all required event fields.');
    redirect('dashboard');
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

$eventId = event_create(
    current_user_id() ?? 0,
    $title,
    $description,
    $imagePath,
    $location,
    $eventDate,
    $capacity
);

if ($eventId === null) {
    set_flash('error', 'Could not create event. Please try again.');
} else {
    set_flash('success', 'Event submitted and pending admin approval.');
}

redirect('dashboard');

