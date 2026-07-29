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
function official_fetch_queue(mysqli $db, string $statusFilter = '', string $search = '', ?int $locationId = null): array
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

    if ($locationId !== null) {
        $sql .= ' AND r.location_id = ?';
        $types .= 'i';
        $params[] = $locationId;
    }

    // Pending first, then oldest-first within each status
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

/**
 * logged-in official's assigned monitoring area (users.assigned_location_id) for the Assigned Area page. returns null if the official has no assigned location
 */
function official_fetch_assigned_location(mysqli $db, int $userId): ?array
{
    $stmt = $db->prepare(
        'SELECT l.location_id, l.city, l.district, l.landmark
         FROM users u
         INNER JOIN locations l ON l.location_id = u.assigned_location_id
         WHERE u.user_id = ?
         LIMIT 1'
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    return $row ?: null;
}

/**
 * threads at a single location for the Assigned Area page
 */
function official_fetch_threads_by_location(mysqli $db, int $locationId): array
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
        WHERE t.location_id = ?
        ORDER BY FIELD(t.status, 'Active', 'Resolved', 'Archived'), t.updated_at DESC
    SQL;

    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $locationId);
    $stmt->execute();

    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/** Maps a thread's new status to the matching audit_logs.action enum value. */
function official_audit_action_for_thread_status(string $oldStatus, string $newStatus): string
{
    if ($oldStatus === $newStatus) {
        return 'Update Thread';
    }

    return $newStatus === 'Archived' ? 'Archive Thread' : 'Update Thread';
}

/**
 * Proposal: "When a thread status changes to Resolved or Archived, all linked reports automatically inherit that status."
 *
 * The reports.status column, however, only allows Pending/Verified/Resolved/
 * Rejected (no "Archived" value), so an Archived thread cannot literally set
 * its reports to "Archived" without breaking the schema. This cascades the
 * Resolved case as specified, and leaves report statuses untouched when a
 * thread is Archived instead (flagged in the Edit Thread page's UI copy).
 */
function official_cascade_thread_status_to_reports(mysqli $db, int $threadId, string $newThreadStatus, int $actingUserId): int
{
    if ($newThreadStatus !== 'Resolved') {
        return 0;
    }

    $stmt = $db->prepare(
        "UPDATE reports
         SET status = 'Resolved',
             verified_by = COALESCE(verified_by, ?),
             verified_at = COALESCE(verified_at, NOW())
         WHERE thread_id = ?
           AND is_deleted = FALSE
           AND status <> 'Resolved'"
    );
    $stmt->bind_param('ii', $actingUserId, $threadId);
    $stmt->execute();

    return $stmt->affected_rows;
}