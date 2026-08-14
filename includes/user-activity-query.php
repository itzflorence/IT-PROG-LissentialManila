<?php
declare(strict_types=1);

/** @return array<string,mixed>|null */
function activity_fetch_home_location(mysqli $db, int $userId): ?array
{
    $statement = $db->prepare(
        'SELECT l.location_id, l.city, l.district, l.landmark
         FROM users u
         INNER JOIN locations l ON l.location_id = u.home_location_id
         WHERE u.user_id = ?
           AND u.is_deleted = FALSE
         LIMIT 1'
    );
    $statement->bind_param('i', $userId);
    $statement->execute();
    $row = $statement->get_result()->fetch_assoc();

    return $row ?: null;
}

/** @return list<array<string,mixed>> */
function activity_fetch_saved_locations(mysqli $db, int $userId): array
{
    $statement = $db->prepare(
        'SELECT l.location_id, l.city, l.district, l.landmark
         FROM saved_locations sl
         INNER JOIN locations l ON l.location_id = sl.location_id
         WHERE sl.user_id = ?
           AND l.is_active = TRUE
         ORDER BY l.city ASC, l.district ASC'
    );
    $statement->bind_param('i', $userId);
    $statement->execute();

    return $statement->get_result()->fetch_all(MYSQLI_ASSOC);
}

/** @return list<array<string,mixed>> */
function activity_fetch_active_locations(mysqli $db): array
{
    return $db->query(
        'SELECT location_id, city, district, landmark
         FROM locations
         WHERE is_active = TRUE
         ORDER BY city ASC, district ASC'
    )->fetch_all(MYSQLI_ASSOC);
}

/** @return array<string,list<array<string,mixed>>> keyed by city, each entry {location_id,district,landmark} */
function activity_fetch_locations_grouped_by_city(mysqli $db): array
{
    $grouped = [];
    foreach (activity_fetch_active_locations($db) as $location) {
        $grouped[(string) $location['city']][] = $location;
    }

    return $grouped;
}

/** @param list<int> $locationIds */
function activity_replace_saved_locations(mysqli $db, int $userId, array $locationIds): void
{
    $locationIds = array_values(array_unique(array_filter($locationIds, static fn(int $locationId): bool => $locationId > 0)));
    $db->begin_transaction();

    try {
        $delete = $db->prepare('DELETE FROM saved_locations WHERE user_id = ?');
        $delete->bind_param('i', $userId);
        $delete->execute();

        if ($locationIds !== []) {
            $save = $db->prepare(
                'INSERT INTO saved_locations (user_id, location_id)
                 SELECT ?, location_id
                 FROM locations
                 WHERE location_id = ?
                   AND is_active = TRUE'
            );

            foreach ($locationIds as $locationId) {
                $save->bind_param('ii', $userId, $locationId);
                $save->execute();
            }
        }

        $db->commit();
    } catch (Throwable $error) {
        $db->rollback();
        throw $error;
    }
}

/** @return list<array<string,mixed>> */
function activity_fetch_comments(mysqli $db, int $userId): array
{
    $statement = $db->prepare(
        'SELECT c.comment_id, c.comment_text, c.created_at, r.report_id, r.title AS report_title,
                r.status, l.city, l.district
         FROM comments c
         INNER JOIN reports r ON r.report_id = c.report_id
         INNER JOIN locations l ON l.location_id = r.location_id
         WHERE c.user_id = ?
           AND c.is_deleted = FALSE
           AND r.is_deleted = FALSE
         ORDER BY c.created_at DESC, c.comment_id DESC'
    );
    $statement->bind_param('i', $userId);
    $statement->execute();

    return $statement->get_result()->fetch_all(MYSQLI_ASSOC);
}

function activity_location_label(array $location): string
{
    return trim((string) ($location['district'] ?? '') . ', ' . (string) ($location['city'] ?? ''), ', ');
}