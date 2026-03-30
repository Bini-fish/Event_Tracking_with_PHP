<?php
// Handle basic event updates from the organizer (with optional image change).
declare(strict_types=1);

require_once __DIR__ . '/../../../includes/session.php';
require_once __DIR__ . '/../../../includes/helpers.php';
require_once __DIR__ . '/../../../includes/validation.php';
require_once __DIR__ . '/../../../includes/auth_guard.php';
require_once __DIR__ . '/../../../includes/policy.php';
require_once __DIR__ . '/../../../models/EventModel.php';

require_login();

if (!policy_is_admin() && current_user_role() !== 'organizer') {
    http_response_code(403);
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
$capacity    = request_int($_POST, 'capacity');
$editReason  = request_string($_POST, 'edit_reason');

foreach ([
    validate_positive_int($eventId, 'Event'),
    validate_required($title, 'Title'),
    validate_max_length($title, 150, 'Title'),
    validate_required($location, 'Location'),
    validate_max_length($location, 150, 'Location'),
    validate_required($description, 'Description'),
    validate_max_length($description, 5000, 'Description'),
    validate_event_datetime_format($eventDate, 'Event date'),
    validate_event_datetime_bounds($eventDate, 300, 60 * 60 * 24 * 365 * 10, 'Event date'),
    validate_positive_int($capacity, 'Capacity'),
    validate_max_length($editReason, 500, 'Edit reason'),
] as $err) {
    if ($err !== true) {
        set_flash('error', $err);
        redirect('dashboard');
    }
}

try {
    $pdo = get_pdo();
} catch (Throwable $e) {
    log_exception($e, 'Update event DB init error');
    set_flash('error', 'Something went wrong. Please try again.');
    redirect('dashboard');
}

$actorId = current_user_id() ?? 0;
$isAdmin = policy_is_admin();
$imagePath = null;
$resetToPending = false;

try {
    $pdo->beginTransaction();

    $eventStmt = $pdo->prepare('SELECT * FROM events WHERE id = :id LIMIT 1 FOR UPDATE');
    $eventStmt->execute([':id' => $eventId]);
    $event = $eventStmt->fetch();

    if (!$event || !can_edit_event($actorId, $event)) {
        $pdo->rollBack();
        set_flash('error', 'Event not found or you do not have permission to edit it.');
        redirect('dashboard');
    }

    $imagePath = $event['image_path'] ?? null;
    $resetToPending = !$isAdmin && (int) ($event['is_verified'] ?? 0) === 1;
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    log_exception($e, 'Update event DB read/lock error');
    set_flash('error', 'Something went wrong. Please try again.');
    redirect('dashboard');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    log_exception($e, 'Update event read/lock error');
    set_flash('error', 'Something went wrong. Please try again.');
    redirect('dashboard');
}

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
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        set_flash('error', 'Image must be a valid uploaded file under 2MB.');
        redirect('dashboard');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = (string) $finfo->file($tmpName);
    $ext   = $allowedMimeToExt[$mime] ?? null;

    if ($ext === null) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        set_flash('error', 'Only JPG, PNG, and GIF images are allowed.');
        redirect('dashboard');
    }

    $uploadDir = __DIR__ . '/../../../public/uploads';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        set_flash('error', 'Could not prepare upload directory.');
        redirect('dashboard');
    }

    $newName    = bin2hex(random_bytes(16)) . '.' . $ext;
    $uploadPath = $uploadDir . '/' . $newName;

    if (!move_uploaded_file($tmpName, $uploadPath)) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        set_flash('error', 'Could not store uploaded image.');
        redirect('dashboard');
    }

    $imagePath = 'uploads/' . $newName;
}

try {
    $newVerificationState = $resetToPending ? 0 : (int) ($event['is_verified'] ?? 0);
    $stmt = $pdo->prepare(
        'UPDATE events
         SET title = :title,
             description = :description,
             image_path = :image_path,
             location = :location,
             event_date = :event_date,
             capacity = :capacity,
             is_verified = :is_verified,
             edited_at = NOW(),
             edited_by = :edited_by,
             edit_reason = :edit_reason
         WHERE id = :id'
    );
    $ok = $stmt->execute([
        ':id' => $eventId,
        ':title' => $title,
        ':description' => $description,
        ':image_path' => $imagePath,
        ':location' => $location,
        ':event_date' => $eventDate,
        ':capacity' => $capacity,
        ':is_verified' => $newVerificationState,
        ':edited_by' => $actorId,
        ':edit_reason' => $editReason !== '' ? $editReason : null,
    ]);

    $pdo->commit();
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    log_exception($e, 'Update event DB write error');
    set_flash('error', 'Something went wrong. Please try again.');
    redirect('dashboard');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    log_exception($e, 'Update event write error');
    set_flash('error', 'Something went wrong. Please try again.');
    redirect('dashboard');
}

if ($ok && $resetToPending) {
    set_flash('success', 'Event changes submitted and pending re-approval.');
} else {
    set_flash($ok ? 'success' : 'error', $ok ? 'Event updated.' : 'Could not update event.');
}
redirect('dashboard');

