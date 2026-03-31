<?php
// Organiser/admin event update with time validation, image replacement, and re-approval reset.
declare(strict_types=1);

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/session.php';
require_once __DIR__ . '/../../../includes/helpers.php';
require_once __DIR__ . '/../../../includes/validation.php';
require_once __DIR__ . '/../../../includes/auth_guard.php';
require_once __DIR__ . '/../../../includes/policy.php';
require_once __DIR__ . '/../../../models/EventModel.php';

require_login();

if (!policy_is_admin() && current_user_role() !== 'organizer') {
    set_flash('error', 'You do not have permission to edit events.');
    redirect('event_feed');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('dashboard');
}

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    set_flash('error', 'Invalid form submission.');
    redirect('dashboard');
}

$eventId     = request_int($_POST, 'event_id');
$title       = request_string($_POST, 'title');
$description = request_string($_POST, 'description');
$location    = request_string($_POST, 'location');
$eventDate   = request_string($_POST, 'event_date');
$eventEnd    = request_string($_POST, 'event_end');
$capacity    = request_int($_POST, 'capacity');
$editReason  = request_string($_POST, 'edit_reason');
$actorId     = current_user_id() ?? 0;
$isAdmin     = policy_is_admin();

foreach ([
    validate_positive_int($eventId, 'Event'),
    validate_required($title, 'Title'),
    validate_max_length($title, 150, 'Title'),
    validate_required($location, 'Location'),
    validate_max_length($location, 150, 'Location'),
    validate_required($description, 'Description'),
    validate_max_length($description, 5000, 'Description'),
    validate_event_datetime_format($eventDate, 'Start date'),
    validate_event_datetime_format($eventEnd, 'End date'),
    validate_positive_int($capacity, 'Capacity'),
    validate_max_length($editReason, 500, 'Edit reason'),
] as $err) {
    if ($err !== true) {
        set_flash('error', $err);
        redirect('dashboard');
    }
}

$dtStart = parse_event_datetime($eventDate);
$dtEnd   = parse_event_datetime($eventEnd);

if ($dtEnd <= $dtStart) {
    set_flash('error', 'End date/time must be after the start date/time.');
    redirect('dashboard');
}

if (($dtEnd->getTimestamp() - $dtStart->getTimestamp()) / 60 < MIN_EVENT_DURATION_MINUTES) {
    set_flash('error', 'Event must be at least ' . MIN_EVENT_DURATION_MINUTES . ' minutes long.');
    redirect('dashboard');
}

try {
    $pdo = get_pdo();
    $pdo->beginTransaction();

    $eStmt = $pdo->prepare('SELECT * FROM events WHERE id=:id FOR UPDATE');
    $eStmt->execute([':id' => $eventId]);
    $event = $eStmt->fetch();

    if (!$event || !can_edit_event($actorId, $event)) {
        $pdo->rollBack();
        set_flash('error', 'Event not found or you do not have permission to edit it.');
        redirect('dashboard');
    }

    $oldImagePath   = $event['image_path'] ?? null;
    $newImagePath   = $oldImagePath; // will be replaced if new image uploaded
    $resetToPending = !$isAdmin && (int) ($event['is_verified'] ?? 0) === 1;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    log_exception($e, 'Update event lock');
    set_flash('error', 'Something went wrong. Please try again.');
    redirect('dashboard');
}

// Image replacement: delete old file if a new one is uploaded.
if (isset($_FILES['image']) && is_array($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
    $uploaded = _upload_image_for_update($pdo);
    if ($uploaded === false) {
        redirect('dashboard');
    }
    // Delete old image file.
    if ($oldImagePath !== null) {
        $oldFile = __DIR__ . '/../../../public/' . $oldImagePath;
        if (is_file($oldFile)) {
            @unlink($oldFile);
        }
    }
    $newImagePath = $uploaded;
}

try {
    $newVerified = $resetToPending ? 0 : (int) ($event['is_verified'] ?? 0);
    $pdo->prepare(
        'UPDATE events
         SET title=:title, description=:desc, image_path=:img,
             location=:loc, event_date=:date, event_end=:end, capacity=:cap,
             is_verified=:ver, edited_at=NOW(), edited_by=:by, edit_reason=:reason
         WHERE id=:id'
    )->execute([
        ':id'     => $eventId,
        ':title'  => $title,
        ':desc'   => $description,
        ':img'    => $newImagePath,
        ':loc'    => $location,
        ':date'   => $dtStart->format('Y-m-d H:i:s'),
        ':end'    => $dtEnd->format('Y-m-d H:i:s'),
        ':cap'    => $capacity,
        ':ver'    => $newVerified,
        ':by'     => $actorId,
        ':reason' => $editReason !== '' ? $editReason : null,
    ]);
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    log_exception($e, 'Update event write');
    set_flash('error', 'Something went wrong. Please try again.');
    redirect('dashboard');
}

set_flash('success', $resetToPending
    ? 'Event changes submitted and pending re-approval.'
    : 'Event updated successfully.');
redirect('dashboard');

function _upload_image_for_update(\PDO $pdo): string|false
{
    $tmpName = (string) ($_FILES['image']['tmp_name'] ?? '');
    $size    = (int) ($_FILES['image']['size'] ?? 0);
    $maxSize = 2 * 1024 * 1024;
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];

    if ($size <= 0 || $size > $maxSize || !is_uploaded_file($tmpName)) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        set_flash('error', 'Image must be a valid file under 2 MB.');
        return false;
    }
    $mime = (string) (new finfo(FILEINFO_MIME_TYPE))->file($tmpName);
    $ext  = $allowed[$mime] ?? null;
    if ($ext === null) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        set_flash('error', 'Only JPG, PNG, and GIF images are allowed.');
        return false;
    }
    $uploadDir = __DIR__ . '/../../../public/uploads';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        set_flash('error', 'Could not prepare upload directory.');
        return false;
    }
    $newName = bin2hex(random_bytes(16)) . '.' . $ext;
    if (!move_uploaded_file($tmpName, $uploadDir . '/' . $newName)) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        set_flash('error', 'Could not store uploaded image.');
        return false;
    }
    return 'uploads/' . $newName;
}
