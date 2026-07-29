<?php

declare(strict_types=1);

/**
 * Query/helper layer for the MMDA/LGU Official module
 */

/** Statuses an official is allowed to set on a report. */
const OFFICIAL_REPORT_STATUSES = ['Pending', 'Verified', 'Resolved', 'Rejected'];

function official_escape(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Reports queue for official review dashboard. pending reports are on top
 */
function official_fetch_queue(mysqli $db, string $statusFilter = '', string $search = ''): array
{
    $statusFilter = in_array($statusFilter, OFFICIAL_REPORT_STATUSES, true) ? $statusFilter : '';
    $search = trim($search);

    $sql = <<<'SQL'
        SELECT
            r.report_id,
            r.thread_id,
            r.title,
            r.description,
            r.status,
            r.upvote_count,
            r.comment_count,
            r.verified_by,
            r.verified_at,
            r.created_at,
            u.username,
            u.first_name,
            u.last_name,
            c.category_name,
            l.city,
            l.district
        FROM reports r
        INNER JOIN users u ON u.user_id = r.user_id
        INNER JOIN categories c ON c.category_id = r.category_id
        INNER JOIN locations l ON l.location_id = r.location_id
        WHERE r.is_deleted = FALSE
    SQL;

    $types = '';
    $params = [];

    if ($statusFilter !== '') {
        $sql .= ' AND r.status = ?';
        $types .= 's';
        $params[] = $statusFilter;
    }

    if ($search !== '') {
        $sql .= ' AND (r.title LIKE ? OR l.city LIKE ? OR l.district LIKE ? OR u.username LIKE ?)';
        $term = '%' . $search . '%';
        $types .= 'ssss';
        array_push($params, $term, $term, $term, $term);
    }

    // pending first, then oldest-first within each status
    $sql .= " ORDER BY FIELD(r.status, 'Pending', 'Rejected', 'Verified', 'Resolved'), r.created_at ASC";

    $stmt = $db->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();

    $reports = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $mediaByReport = [];

    if ($reports !== []) {
        $reportIds = array_map(static fn(array $report): int => (int) $report['report_id'], $reports);
        $placeholders = implode(',', array_fill(0, count($reportIds), '?'));

        $mediaStatement = $db->prepare(
            "SELECT report_id, file_url, file_type FROM media_attachments WHERE report_id IN ($placeholders) ORDER BY report_id ASC, media_id ASC"
        );
        $mediaStatement->bind_param(str_repeat('i', count($reportIds)), ...$reportIds);
        $mediaStatement->execute();

        $mediaResult = $mediaStatement->get_result();
        while ($mediaRow = $mediaResult->fetch_assoc()) {
            $reportId = (int) $mediaRow['report_id'];
            $mediaByReport[$reportId][] = [
                'file_url' => normalize_media_url((string) ($mediaRow['file_url'] ?? '')),
                'file_type' => strtolower((string) ($mediaRow['file_type'] ?? 'photo')),
            ];
        }
    }

    return [
        'reports' => $reports,
        'mediaByReport' => $mediaByReport,
    ];
}

function official_fetch_report_for_edit(mysqli $db, int $reportId): ?array
{
    $sql = <<<'SQL'
        SELECT
            r.report_id,
            r.user_id,
            r.thread_id,
            r.location_id,
            r.category_id,
            r.title,
            r.description,
            r.upvote_count,
            r.comment_count,
            r.status,
            r.verification_remarks,
            r.verified_by,
            r.verified_at,
            r.created_at,
            r.updated_at,
            u.username,
            u.first_name,
            u.last_name,
            u.phone_number,
            c.category_name,
            l.city,
            l.district,
            l.landmark,
            t.title AS thread_title,
            t.status AS thread_status
        FROM reports r
        INNER JOIN users u ON u.user_id = r.user_id
        INNER JOIN categories c ON c.category_id = r.category_id
        INNER JOIN locations l ON l.location_id = r.location_id
        LEFT JOIN threads t ON t.thread_id = r.thread_id
        WHERE r.report_id = ?
          AND r.is_deleted = FALSE
        LIMIT 1
    SQL;

    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $reportId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    return $row ?: null;
}

/** @return list<array{file_url:string,file_type:string}> */
function official_fetch_media(mysqli $db, int $reportId): array
{
    $stmt = $db->prepare('SELECT file_url, file_type FROM media_attachments WHERE report_id = ? ORDER BY media_id ASC');
    $stmt->bind_param('i', $reportId);
    $stmt->execute();

    $media = [];
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $media[] = [
            'file_url' => normalize_media_url((string) ($row['file_url'] ?? '')),
            'file_type' => strtolower((string) ($row['file_type'] ?? 'photo')),
        ];
    }

    return $media;
}

/** @return list<array<string, mixed>> */
function official_fetch_comments(mysqli $db, int $reportId): array
{
    $stmt = $db->prepare(
        'SELECT c.comment_id, c.user_id, c.comment_text, c.created_at, u.username, u.first_name, u.last_name
         FROM comments c
         INNER JOIN users u ON u.user_id = c.user_id
         WHERE c.report_id = ?
           AND c.is_deleted = FALSE
         ORDER BY c.created_at ASC, c.comment_id ASC'
    );
    $stmt->bind_param('i', $reportId);
    $stmt->execute();

    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * active locations grouped by city for the "reassign location" dropdown in the official report edit form
 */
function official_fetch_locations_grouped(mysqli $db): array
{
    $result = $db->query(
        'SELECT location_id, city, district
         FROM locations
         WHERE is_active = TRUE
         ORDER BY city ASC, district ASC'
    );

    $grouped = [];
    while ($row = $result->fetch_assoc()) {
        $city = (string) $row['city'];
        $grouped[$city][] = [
            'location_id' => (int) $row['location_id'],
            'district' => (string) $row['district'],
        ];
    }

    return $grouped;
}

/**
 * existing threads at a given location, offered as "assign to thread" candidates when an official verifies a report. 
 */
function official_fetch_candidate_threads(mysqli $db, int $locationId): array
{
    $sql = <<<'SQL'
        SELECT t.thread_id, t.title, t.status, c.category_name
        FROM threads t
        INNER JOIN categories c ON c.category_id = t.category_id
        WHERE t.location_id = ?
        ORDER BY FIELD(t.status, 'Active', 'Resolved', 'Archived'), t.updated_at DESC
    SQL;

    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $locationId);
    $stmt->execute();

    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * keeps thread's total_reports/verified_reports/unverified_reports counters in sync 
 */
function official_recalculate_thread_counts(mysqli $db, int $threadId): void
{
    $stmt = $db->prepare(
        'UPDATE threads t
         SET
            total_reports = (
                SELECT COUNT(*) FROM reports r
                WHERE r.thread_id = t.thread_id AND r.is_deleted = FALSE
            ),
            verified_reports = (
                SELECT COUNT(*) FROM reports r
                WHERE r.thread_id = t.thread_id AND r.is_deleted = FALSE
                  AND r.status IN (\'Verified\', \'Resolved\')
            ),
            unverified_reports = (
                SELECT COUNT(*) FROM reports r
                WHERE r.thread_id = t.thread_id AND r.is_deleted = FALSE
                  AND r.status NOT IN (\'Verified\', \'Resolved\')
            )
         WHERE t.thread_id = ?'
    );
    $stmt->bind_param('i', $threadId);
    $stmt->execute();
}

/** maps a report's new status to the matching audit_logs.action enum value. */
function official_audit_action_for_status(string $oldStatus, string $newStatus): string
{
    if ($oldStatus === $newStatus) {
        return 'Edit Report';
    }

    return match ($newStatus) {
        'Verified' => 'Verify Report',
        'Rejected' => 'Reject Report',
        'Resolved' => 'Resolve Report',
        default => 'Edit Report',
    };
}

/** records an official/admin action for the audit trail. */
function official_log_action(mysqli $db, int $userId, string $action, string $entityType, int $entityId, string $description): void
{
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    $ip = $ip === '' ? null : substr($ip, 0, 45);

    $stmt = $db->prepare(
        'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, description, ip_address)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('ississ', $userId, $action, $entityType, $entityId, $description, $ip);
    $stmt->execute();
}