<?php
declare(strict_types=1);

function normalize_media_url(string $fileUrl): string
{
    $path = trim($fileUrl);
    $normalized = preg_replace('#^(?:\.\./)+#', '', $path);

    return ltrim((string) ($normalized ?? $path), '/');
}

function relative_time_label(?string $timestamp): string
{
    if ($timestamp === null || $timestamp === '') {
        return 'Unknown time';
    }

    try {
        $now = new DateTimeImmutable('now');
        $created = new DateTimeImmutable($timestamp);
    } catch (Throwable $error) {
        return 'Unknown time';
    }

    $diff = $created->diff($now);

    if ($diff->y > 0) {
        return $diff->y . ' year' . ($diff->y === 1 ? '' : 's') . ' ago';
    }
    if ($diff->m > 0) {
        return $diff->m . ' month' . ($diff->m === 1 ? '' : 's') . ' ago';
    }
    if ($diff->d > 0) {
        return $diff->d . ' day' . ($diff->d === 1 ? '' : 's') . ' ago';
    }
    if ($diff->h > 0) {
        return $diff->h . ' hour' . ($diff->h === 1 ? '' : 's') . ' ago';
    }
    if ($diff->i > 0) {
        return $diff->i . ' min' . ($diff->i === 1 ? '' : 's') . ' ago';
    }

    return 'Just now';
}

/**
 * @return array{date:string,time:string}
 */
function report_date_time_labels(?string $timestamp): array
{
    if ($timestamp === null || $timestamp === '') {
        return ['date' => 'Unknown date', 'time' => '--:--'];
    }

    try {
        $created = new DateTimeImmutable($timestamp);
    } catch (Throwable $error) {
        return ['date' => 'Unknown date', 'time' => '--:--'];
    }

    return [
        'date' => $created->format('F d, Y'),
        'time' => $created->format('h:i A'),
    ];
}

function build_filter_url(string $basePath, ?string $status, ?int $categoryId): string
{
    $query = [];

    if ($status !== null && $status !== '') {
        $query['status'] = $status;
    }

    if ($categoryId !== null) {
        $query['category'] = $categoryId;
    }

    $queryString = http_build_query($query);
    if ($queryString === '') {
        return $basePath;
    }

    return $basePath . '?' . $queryString;
}

/**
 * @return list<array{category_id:int,category_name:string}>
 */
function fetch_categories(mysqli $db): array
{
    $categories = [];
    $categoryResult = $db->query('SELECT category_id, category_name FROM categories WHERE is_active = TRUE ORDER BY category_name ASC');

    while ($row = $categoryResult->fetch_assoc()) {
        $categories[] = [
            'category_id' => (int) ($row['category_id'] ?? 0),
            'category_name' => (string) ($row['category_name'] ?? ''),
        ];
    }

    return $categories;
}

// Reports show at most this many attached media items in their carousel.
const REPORT_MEDIA_DISPLAY_LIMIT = 4;

/**
 * @return array{reports:list<array<string,mixed>>,mediaByReport:array<int,list<array{file_url:string,file_type:string}>>}
 */
function fetch_reports_and_media(mysqli $db, string $selectedStatus, ?int $selectedCategoryId, ?int $currentUserId = null): array
{
    $sql = <<<'SQL'
        SELECT
            r.report_id,
            r.thread_id,
            r.title,
            r.description,
            r.status,
            r.upvote_count,
            r.comment_count,
            r.resolved_count,
            r.verified_by,
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

    if ($selectedStatus !== '') {
        $sql .= ' AND r.status = ?';
        $types .= 's';
        $params[] = $selectedStatus;
    }

    if ($selectedCategoryId !== null) {
        $sql .= ' AND r.category_id = ?';
        $types .= 'i';
        $params[] = $selectedCategoryId;
    }

    $sql .= ' ORDER BY r.created_at DESC, r.report_id DESC';

    $statement = $db->prepare($sql);
    if ($types !== '') {
        $statement->bind_param($types, ...$params);
    }
    $statement->execute();

    return fetch_report_media_for_rows($db, $statement->get_result()->fetch_all(MYSQLI_ASSOC), $currentUserId);
}

/**
 * @param list<int> $locationIds
 * @return array{reports:list<array<string,mixed>>,mediaByReport:array<int,list<array{file_url:string,file_type:string}>>}
 */
function fetch_reports_and_media_by_location_ids(mysqli $db, array $locationIds, ?int $currentUserId = null): array
{
    $locationIds = array_values(array_unique(array_filter($locationIds, static fn(int $locationId): bool => $locationId > 0)));

    if ($locationIds === []) {
        return ['reports' => [], 'mediaByReport' => []];
    }

    $placeholders = implode(',', array_fill(0, count($locationIds), '?'));
    $sql = <<<SQL
        SELECT
            r.report_id,
            r.thread_id,
            r.title,
            r.description,
            r.status,
            r.upvote_count,
            r.comment_count,
            r.resolved_count,
            r.verified_by,
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
          AND r.location_id IN ($placeholders)
        ORDER BY r.created_at DESC, r.report_id DESC
    SQL;

    $statement = $db->prepare($sql);
    $statement->bind_param(str_repeat('i', count($locationIds)), ...$locationIds);
    $statement->execute();

    return fetch_report_media_for_rows($db, $statement->get_result()->fetch_all(MYSQLI_ASSOC), $currentUserId);
}

/**
 * @return array{reports:list<array<string,mixed>>,mediaByReport:array<int,list<array{file_url:string,file_type:string}>>}
 */
function fetch_reports_and_media_by_author(mysqli $db, int $userId, ?int $currentUserId = null): array
{
    $sql = <<<'SQL'
        SELECT
            r.report_id,
            r.thread_id,
            r.title,
            r.description,
            r.status,
            r.upvote_count,
            r.comment_count,
            r.resolved_count,
            r.verified_by,
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
          AND r.user_id = ?
        ORDER BY r.created_at DESC, r.report_id DESC
    SQL;

    $statement = $db->prepare($sql);
    $statement->bind_param('i', $userId);
    $statement->execute();

    return fetch_report_media_for_rows($db, $statement->get_result()->fetch_all(MYSQLI_ASSOC), $currentUserId);
}

/**
 * @param list<array<string,mixed>> $reports
 * @return array{reports:list<array<string,mixed>>,mediaByReport:array<int,list<array{file_url:string,file_type:string}>>}
 */
function fetch_report_media_for_rows(mysqli $db, array $reports, ?int $currentUserId = null): array
{
    if ($reports === []) {
        return ['reports' => [], 'mediaByReport' => []];
    }

    attach_user_marks($db, $reports, $currentUserId);

    $mediaByReport = [];
    $reportIds = array_map(static fn(array $report): int => (int) $report['report_id'], $reports);
    $placeholders = implode(',', array_fill(0, count($reportIds), '?'));
    $mediaSql = "SELECT report_id, file_url, file_type FROM media_attachments WHERE report_id IN ($placeholders) ORDER BY report_id ASC, media_id ASC";
    $mediaStatement = $db->prepare($mediaSql);
    $mediaStatement->bind_param(str_repeat('i', count($reportIds)), ...$reportIds);
    $mediaStatement->execute();

    $mediaResult = $mediaStatement->get_result();
    while ($mediaRow = $mediaResult->fetch_assoc()) {
        $reportId = (int) $mediaRow['report_id'];
        if (count($mediaByReport[$reportId] ?? []) >= REPORT_MEDIA_DISPLAY_LIMIT) {
            continue;
        }
        $mediaByReport[$reportId][] = [
            'file_url' => (string) ($mediaRow['file_url'] ?? ''),
            'file_type' => strtolower((string) ($mediaRow['file_type'] ?? 'photo')),
        ];
    }

    return [
        'reports' => $reports,
        'mediaByReport' => $mediaByReport,
    ];
}

/**
 * Marks each report row with whether the given user has upvoted/marked it resolved.
 * @param list<array<string,mixed>> $reports
 */
function attach_user_marks(mysqli $db, array &$reports, ?int $currentUserId): void
{
    foreach ($reports as &$report) {
        $report['has_upvoted'] = false;
        $report['has_resolved'] = false;
    }
    unset($report);

    if ($currentUserId === null || $reports === []) {
        return;
    }

    $reportIds = array_map(static fn(array $report): int => (int) $report['report_id'], $reports);
    $placeholders = implode(',', array_fill(0, count($reportIds), '?'));
    $types = 'i' . str_repeat('i', count($reportIds));

    $upvoted = [];
    $upvoteStatement = $db->prepare("SELECT report_id FROM upvotes WHERE user_id = ? AND report_id IN ($placeholders)");
    $upvoteStatement->bind_param($types, $currentUserId, ...$reportIds);
    $upvoteStatement->execute();
    $upvoteResult = $upvoteStatement->get_result();
    while ($row = $upvoteResult->fetch_assoc()) {
        $upvoted[(int) $row['report_id']] = true;
    }

    $resolved = [];
    $resolvedStatement = $db->prepare("SELECT report_id FROM resolved_marks WHERE user_id = ? AND report_id IN ($placeholders)");
    $resolvedStatement->bind_param($types, $currentUserId, ...$reportIds);
    $resolvedStatement->execute();
    $resolvedResult = $resolvedStatement->get_result();
    while ($row = $resolvedResult->fetch_assoc()) {
        $resolved[(int) $row['report_id']] = true;
    }

    foreach ($reports as &$report) {
        $reportId = (int) $report['report_id'];
        $report['has_upvoted'] = isset($upvoted[$reportId]);
        $report['has_resolved'] = isset($resolved[$reportId]);
    }
    unset($report);
}
