<?php
// Event-related data access functions.

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

/**
 * Get all approved (verified) events for the public feed.
 */
function event_get_approved_events(): array
{
    $pdo = get_pdo();

    $stmt = $pdo->query(
        'SELECT *
         FROM events
         WHERE is_verified = 1
         ORDER BY event_date ASC, created_at DESC'
    );

    return $stmt->fetchAll();
}

/**
 * Get all events that are pending admin approval.
 */
function event_get_pending_events(): array
{
    $pdo = get_pdo();

    $stmt = $pdo->query(
        'SELECT *
         FROM events
         WHERE is_verified = 0
         ORDER BY created_at DESC'
    );

    return $stmt->fetchAll();
}

/**
 * Get full details for a single event by ID.
 */
function event_get_by_id(int $id): ?array
{
    $pdo = get_pdo();

    $stmt = $pdo->prepare('SELECT * FROM events WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $event = $stmt->fetch();

    return $event !== false ? $event : null;
}

/**
 * Get all events created by a specific organizer.
 */
function event_get_by_organizer(int $organizerId): array
{
    $pdo = get_pdo();

    $stmt = $pdo->prepare(
        'SELECT e.*,
                (SELECT COUNT(*) FROM rsvps r WHERE r.event_id = e.id) AS rsvp_count
         FROM events e
         WHERE e.organizer_id = :organizer_id
         ORDER BY e.created_at DESC'
    );

    $stmt->execute([':organizer_id' => $organizerId]);

    return $stmt->fetchAll();
}

/**
 * Create a new event in pending/unverified state.
 *
 * Returns the new event ID on success, or null on failure.
 */
function event_create(
    int $organizerId,
    string $title,
    string $description,
    ?string $imagePath,
    string $location,
    string $eventDate,
    int $capacity
): ?int {
    $pdo = get_pdo();

    $stmt = $pdo->prepare(
        'INSERT INTO events (organizer_id, title, description, image_path, location, event_date, capacity, is_verified, created_at)
         VALUES (:organizer_id, :title, :description, :image_path, :location, :event_date, :capacity, 0, NOW())'
    );

    $ok = $stmt->execute([
        ':organizer_id' => $organizerId,
        ':title'        => $title,
        ':description'  => $description,
        ':image_path'   => $imagePath,
        ':location'     => $location,
        ':event_date'   => $eventDate,
        ':capacity'     => $capacity,
    ]);

    if (!$ok) {
        return null;
    }

    return (int) $pdo->lastInsertId();
}

/**
 * Update editable event fields (not approval flag).
 */
function event_update_basic(
    int $eventId,
    string $title,
    string $description,
    ?string $imagePath,
    string $location,
    string $eventDate,
    int $capacity
): bool {
    $pdo = get_pdo();

    $stmt = $pdo->prepare(
        'UPDATE events
         SET title = :title,
             description = :description,
             image_path = :image_path,
             location = :location,
             event_date = :event_date,
             capacity = :capacity
         WHERE id = :id'
    );

    return $stmt->execute([
        ':id'          => $eventId,
        ':title'       => $title,
        ':description' => $description,
        ':image_path'  => $imagePath,
        ':location'    => $location,
        ':event_date'  => $eventDate,
        ':capacity'    => $capacity,
    ]);
}

/**
 * Delete an event owned by the given organizer.
 * Returns false if the event doesn't exist or the organizer doesn't own it.
 */
function event_delete(int $eventId, int $organizerId): bool
{
    $pdo = get_pdo();

    $stmt = $pdo->prepare(
        'DELETE FROM events WHERE id = :id AND organizer_id = :organizer_id'
    );

    $stmt->execute([
        ':id'           => $eventId,
        ':organizer_id' => $organizerId,
    ]);

    return $stmt->rowCount() > 0;
}

/**
 * Toggle the approval/verification flag for an event.
 */
function event_set_verified(int $eventId, bool $isVerified): bool
{
    $pdo = get_pdo();

    $stmt = $pdo->prepare(
        'UPDATE events
         SET is_verified = :is_verified
         WHERE id = :id'
    );

    return $stmt->execute([
        ':id'          => $eventId,
        ':is_verified' => $isVerified ? 1 : 0,
    ]);
}

