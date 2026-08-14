<?php

declare(strict_types=1);

/**
 * Query layer for the notification bell panel (nearby alerts from home/saved locations).
 */

/**
 * @return list<array<string,mixed>>
 */
function notifications_fetch_recent(mysqli $db, int $userId, int $limit = 20): array
{
    $sql = <<<'SQL'
        SELECT
            n.notification_id,
            n.created_at,
            t.thread_id,
            t.title,
            t.status,
            c.category_name,
            l.city,
            l.district
        FROM notifications n
        INNER JOIN threads t ON t.thread_id = n.thread_id
        INNER JOIN categories c ON c.category_id = t.category_id
        INNER JOIN locations l ON l.location_id = t.location_id
        WHERE n.user_id = ?
        ORDER BY n.created_at DESC
        LIMIT ?
    SQL;

    $stmt = $db->prepare($sql);
    $stmt->bind_param('ii', $userId, $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = [
            'notification_id' => (int) $row['notification_id'],
            'created_at' => (string) $row['created_at'],
            'thread_id' => (int) $row['thread_id'],
            'title' => (string) $row['title'],
            'status' => (string) $row['status'],
            'category_name' => (string) $row['category_name'],
            'location_label' => trim((string) $row['district'] . ', ' . (string) $row['city']),
        ];
    }

    return $notifications;
}
