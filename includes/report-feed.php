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

/**
 * @return array{reports:list<array<string,mixed>>,mediaByReport:array<int,list<array{file_url:string,file_type:string}>>}
 */
function fetch_reports_and_media(mysqli $db, string $selectedStatus, ?int $selectedCategoryId): array
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

    $reports = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $mediaByReport = [];

    if ($reports !== []) {
        $reportIds = array_map(
            static fn(array $report): int => (int) $report['report_id'],
            $reports
        );

        $placeholders = implode(',', array_fill(0, count($reportIds), '?'));
        $mediaSql = "SELECT report_id, file_url, file_type FROM media_attachments WHERE report_id IN ($placeholders) ORDER BY report_id ASC, media_id ASC";
        $mediaStatement = $db->prepare($mediaSql);
        $mediaStatement->bind_param(str_repeat('i', count($reportIds)), ...$reportIds);
        $mediaStatement->execute();

        $mediaResult = $mediaStatement->get_result();
        while ($mediaRow = $mediaResult->fetch_assoc()) {
            $reportId = (int) $mediaRow['report_id'];
            $mediaByReport[$reportId][] = [
                'file_url' => (string) ($mediaRow['file_url'] ?? ''),
                'file_type' => strtolower((string) ($mediaRow['file_type'] ?? 'photo')),
            ];
        }
    }

    return [
        'reports' => $reports,
        'mediaByReport' => $mediaByReport,
    ];
}
