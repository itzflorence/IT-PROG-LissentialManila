<?php

declare(strict_types=1);

/**
 * Standalone database/query layer for the Threads module.
 * XAMPP defaults: host=localhost, user=root, password="", database=lissential_manila_db.
 * Environment variables may override these values: DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT.
 */
function thread_db(): mysqli
{
    static $db = null;

    if ($db instanceof mysqli) {
        return $db;
    }

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $host = getenv('DB_HOST') ?: 'localhost';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: '';
    $name = getenv('DB_NAME') ?: 'lissential_manila_db';
    $port = (int) (getenv('DB_PORT') ?: 3306);

    $db = new mysqli($host, $user, $pass, $name, $port);
    $db->set_charset('utf8mb4');

    return $db;
}

function thread_escape(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function thread_normalize_status(?string $status): ?string
{
    if ($status === null || $status === '') {
        return null;
    }

    $allowed = ['Active', 'Resolved', 'Archived'];
    return in_array($status, $allowed, true) ? $status : null;
}

/** @return array{all:int,Active:int,Resolved:int,Archived:int} */
function thread_status_counts(mysqli $db): array
{
    $counts = ['all' => 0, 'Active' => 0, 'Resolved' => 0, 'Archived' => 0];
    $result = $db->query('SELECT status, COUNT(*) AS total FROM threads GROUP BY status');

    while ($row = $result->fetch_assoc()) {
        $status = (string) $row['status'];
        $total = (int) $row['total'];
        if (array_key_exists($status, $counts)) {
            $counts[$status] = $total;
            $counts['all'] += $total;
        }
    }

    return $counts;
}

/**
 * @return array<int, array<string, mixed>>
 */
function thread_fetch_all(mysqli $db, ?string $status, string $search): array
{
    $status = thread_normalize_status($status);
    $search = trim($search);

    $sql = <<<'SQL'
        SELECT
            t.thread_id,
            t.title,
            t.description,
            t.status,
            t.total_reports,
            t.verified_reports,
            t.unverified_reports,
            t.created_at,
            t.updated_at,
            c.category_name,
            l.city,
            l.district,
            l.landmark,
            CONCAT(u.first_name, ' ', u.last_name) AS creator_name,
            (
                SELECT COUNT(*)
                FROM reports r
                WHERE r.thread_id = t.thread_id
                  AND r.is_deleted = FALSE
            ) AS actual_report_count
        FROM threads t
        INNER JOIN categories c ON c.category_id = t.category_id
        INNER JOIN locations l ON l.location_id = t.location_id
        INNER JOIN users u ON u.user_id = t.created_by
        WHERE 1 = 1
    SQL;

    $types = '';
    $params = [];

    if ($status !== null) {
        $sql .= ' AND t.status = ?';
        $types .= 's';
        $params[] = $status;
    }

    if ($search !== '') {
        $sql .= <<<'SQL'
             AND (
                t.title LIKE ?
                OR COALESCE(t.description, '') LIKE ?
                OR c.category_name LIKE ?
                OR l.city LIKE ?
                OR l.district LIKE ?
                OR COALESCE(l.landmark, '') LIKE ?
            )
        SQL;
        $term = '%' . $search . '%';
        $types .= 'ssssss';
        array_push($params, $term, $term, $term, $term, $term, $term);
    }

    $sql .= " ORDER BY FIELD(t.status, 'Active', 'Resolved', 'Archived'), t.updated_at DESC, t.thread_id DESC";

    $stmt = $db->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();

    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/** @return array<string, mixed>|null */
function thread_fetch_one(mysqli $db, int $threadId): ?array
{
    $sql = <<<'SQL'
        SELECT
            t.thread_id,
            t.title,
            t.description,
            t.status,
            t.total_reports,
            t.verified_reports,
            t.unverified_reports,
            t.created_at,
            t.updated_at,
            c.category_name,
            l.city,
            l.district,
            l.landmark,
            CONCAT(u.first_name, ' ', u.last_name) AS creator_name
        FROM threads t
        INNER JOIN categories c ON c.category_id = t.category_id
        INNER JOIN locations l ON l.location_id = t.location_id
        INNER JOIN users u ON u.user_id = t.created_by
        WHERE t.thread_id = ?
        LIMIT 1
    SQL;

    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $threadId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    return $row ?: null;
}

/** @return array<int, array<string, mixed>> */
function thread_fetch_reports(mysqli $db, int $threadId): array
{
    $sql = <<<'SQL'
        SELECT
            r.report_id,
            r.title,
            r.description,
            r.status,
            r.upvote_count,
            r.comment_count,
            r.created_at,
            c.category_name,
            l.city,
            l.district,
            CONCAT(u.first_name, ' ', u.last_name) AS reporter_name,
            u.username
        FROM reports r
        INNER JOIN categories c ON c.category_id = r.category_id
        INNER JOIN locations l ON l.location_id = r.location_id
        INNER JOIN users u ON u.user_id = r.user_id
        WHERE r.thread_id = ?
          AND r.is_deleted = FALSE
        ORDER BY r.created_at DESC, r.report_id DESC
    SQL;

    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $threadId);
    $stmt->execute();

    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function thread_location_label(array $thread): string
{
    $parts = [];
    if (!empty($thread['district'])) {
        $parts[] = (string) $thread['district'];
    }
    if (!empty($thread['city'])) {
        $parts[] = (string) $thread['city'];
    }
    if (!empty($thread['landmark'])) {
        $parts[] = (string) $thread['landmark'];
    }

    return implode(', ', $parts);
}

function thread_date_label(?string $date): string
{
    if (!$date) {
        return 'Unknown date';
    }

    $timestamp = strtotime($date);
    return $timestamp === false ? $date : date('F j, Y \a\t g:i A', $timestamp);
}
