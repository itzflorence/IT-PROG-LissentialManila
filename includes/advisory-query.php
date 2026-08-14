<?php

declare(strict_types=1);

/**
 * Query layer for official/LGU advisories (advisories table).
 */

/** @return list<array<string,mixed>> */
function fetch_active_advisories(mysqli $db, int $limit = 5): array
{
    $sql = <<<'SQL'
        SELECT
            a.advisory_id,
            a.title,
            a.content,
            a.created_at,
            u.username,
            u.first_name,
            u.last_name,
            l.city,
            l.district
        FROM advisories a
        INNER JOIN users u ON u.user_id = a.posted_by
        LEFT JOIN locations l ON l.location_id = a.location_id
        WHERE a.is_active = TRUE
        ORDER BY a.created_at DESC
        LIMIT ?
    SQL;

    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $limit);
    $stmt->execute();

    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function advisory_location_label(array $advisory): string
{
    if (empty($advisory['city'])) {
        return 'City-wide';
    }

    return trim((string) ($advisory['district'] ?? '') . ', ' . (string) ($advisory['city'] ?? ''), ', ');
}
