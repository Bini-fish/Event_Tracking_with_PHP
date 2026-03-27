<?php
// RSVP-related data access functions.

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

/**
 * Count how many RSVPs an event currently has.
 */
function rsvp_count_for_event(int $eventId): int
{
    $pdo = get_pdo();

    $stmt = $pdo->prepare('SELECT COUNT(*) AS c FROM rsvps WHERE event_id = ?');
    $stmt->execute([$eventId]);
    $row = $stmt->fetch();

    return $row ? (int) $row['c'] : 0;
}

/**
 * Get the remaining seats for an event based on capacity and RSVPs.
 *
 * Returns zero if capacity is exceeded or not set.
 */
function rsvp_remaining_seats(int $eventId, int $capacity): int
{
    $current = rsvp_count_for_event($eventId);
    $remaining = $capacity - $current;

    return $remaining > 0 ? $remaining : 0;
}

/**
 * Check if a given user has already RSVPed to an event.
 */
function rsvp_user_has_rsvped(int $eventId, int $userId): bool
{
    $pdo = get_pdo();

    $stmt = $pdo->prepare('SELECT id FROM rsvps WHERE event_id = ? AND user_id = ? LIMIT 1');
    $stmt->execute([$eventId, $userId]);

    return $stmt->fetch() !== false;
}

/**
 * Determine whether a user can RSVP to an event (capacity not exceeded and not duplicated).
 */
function rsvp_can_rsvp(int $eventId, int $userId, int $capacity): bool
{
    if (rsvp_user_has_rsvped($eventId, $userId)) {
        return false;
    }

    return rsvp_remaining_seats($eventId, $capacity) > 0;
}

/**
 * Get all RSVPs for a given event, joined with basic user info.
 * Used by organizers to see who has registered.
 */
function rsvp_get_for_event(int $eventId): array
{
    $pdo = get_pdo();

    $stmt = $pdo->prepare(
        'SELECT r.id, r.created_at, u.id AS user_id, u.name, u.email
         FROM rsvps r
         JOIN users u ON u.id = r.user_id
         WHERE r.event_id = :event_id
         ORDER BY r.created_at ASC'
    );

    $stmt->execute([':event_id' => $eventId]);

    return $stmt->fetchAll();
}

/**
 * Insert a new RSVP record.
 */
function rsvp_add(int $eventId, int $userId): bool
{
    $pdo = get_pdo();

    $stmt = $pdo->prepare(
        'INSERT INTO rsvps (event_id, user_id, created_at)
         VALUES (:event_id, :user_id, NOW())'
    );

    return $stmt->execute([
        ':event_id' => $eventId,
        ':user_id'  => $userId,
    ]);
}

