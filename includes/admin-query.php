<?php

declare(strict_types=1);

/**
 * Query/helper layer for Admin features: Manage Accounts (CRUD on users)
 * and Platform Analytics (aggregate stats). Mirrors the conventions used
 * in includes/official-query.php.
 */

const ADMIN_ACCOUNT_ROLES = ['Student', 'Official', 'Admin'];

/* ============================================================ */
/* MANAGE ACCOUNTS                                                */
/* ============================================================ */

/**
 * Accounts list for the Manage Accounts table, with optional search + role filter.
 * @return list<array<string, mixed>>
 */
function admin_fetch_accounts(mysqli $db, string $search = '', string $roleFilter = ''): array
{
    $search = trim($search);
    $roleFilter = in_array($roleFilter, ADMIN_ACCOUNT_ROLES, true) ? $roleFilter : '';

    $sql = <<<'SQL'
        SELECT
            u.user_id,
            u.phone_number,
            u.email,
            u.first_name,
            u.last_name,
            u.username,
            u.role,
            u.assigned_location_id,
            u.home_location_id,
            u.is_deleted,
            u.created_at,
            al.city AS assigned_city,
            al.district AS assigned_district,
            hl.city AS home_city,
            hl.district AS home_district
        FROM users u
        LEFT JOIN locations al ON al.location_id = u.assigned_location_id
        INNER JOIN locations hl ON hl.location_id = u.home_location_id
        WHERE 1 = 1
    SQL;

    $types = '';
    $params = [];

    if ($roleFilter !== '') {
        $sql .= ' AND u.role = ?';
        $types .= 's';
        $params[] = $roleFilter;
    }

    if ($search !== '') {
        $sql .= ' AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.username LIKE ? OR u.phone_number LIKE ?)';
        $term = '%' . $search . '%';
        $types .= 'ssss';
        array_push($params, $term, $term, $term, $term);
    }

    $sql .= ' ORDER BY u.created_at DESC, u.user_id DESC';

    $stmt = $db->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();

    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/** @return array<string, mixed>|null */
function admin_fetch_account(mysqli $db, int $userId): ?array
{
    $stmt = $db->prepare(
        'SELECT user_id, phone_number, email, first_name, last_name, username, role,
                assigned_location_id, home_location_id, is_deleted
         FROM users
         WHERE user_id = ?
         LIMIT 1'
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    return $row ?: null;
}

/**
 * Creates a new account. Returns the new user_id.
 * @throws mysqli_sql_exception on duplicate phone/username (errno 1062) or other DB errors
 */
function admin_create_account(mysqli $db, array $data): int
{
    $passwordHash = password_hash((string) $data['password'], PASSWORD_DEFAULT);

    $stmt = $db->prepare(
        'INSERT INTO users
            (phone_number, email, first_name, last_name, username, password_hash, role, assigned_location_id, home_location_id)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param(
        'sssssssii',
        $data['phone_number'],
        $data['email'],
        $data['first_name'],
        $data['last_name'],
        $data['username'],
        $passwordHash,
        $data['role'],
        $data['assigned_location_id'],
        $data['home_location_id']
    );
    $stmt->execute();

    return (int) $db->insert_id;
}

/**
 * Updates an existing account. Password is only changed when $data['password'] is non-empty.
 * @throws mysqli_sql_exception on duplicate phone/username (errno 1062) or other DB errors
 */
function admin_update_account(mysqli $db, int $userId, array $data): void
{
    if (!empty($data['password'])) {
        $passwordHash = password_hash((string) $data['password'], PASSWORD_DEFAULT);
        $stmt = $db->prepare(
            'UPDATE users
             SET phone_number = ?, email = ?, first_name = ?, last_name = ?, username = ?,
                 password_hash = ?, role = ?, assigned_location_id = ?, home_location_id = ?
             WHERE user_id = ?'
        );
        $stmt->bind_param(
            'sssssssiii',
            $data['phone_number'],
            $data['email'],
            $data['first_name'],
            $data['last_name'],
            $data['username'],
            $passwordHash,
            $data['role'],
            $data['assigned_location_id'],
            $data['home_location_id'],
            $userId
        );
    } else {
        $stmt = $db->prepare(
            'UPDATE users
             SET phone_number = ?, email = ?, first_name = ?, last_name = ?, username = ?,
                 role = ?, assigned_location_id = ?, home_location_id = ?
             WHERE user_id = ?'
        );
        $stmt->bind_param(
            'ssssssiii',
            $data['phone_number'],
            $data['email'],
            $data['first_name'],
            $data['last_name'],
            $data['username'],
            $data['role'],
            $data['assigned_location_id'],
            $data['home_location_id'],
            $userId
        );
    }

    $stmt->execute();
}

function admin_soft_delete_account(mysqli $db, int $userId): void
{
    $stmt = $db->prepare('UPDATE users SET is_deleted = TRUE WHERE user_id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
}

function admin_restore_account(mysqli $db, int $userId): void
{
    $stmt = $db->prepare('UPDATE users SET is_deleted = FALSE WHERE user_id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
}

/** Convenience label, e.g. "Marikina Heights, Marikina" or "—" when no location is set. */
function admin_location_label(?string $district, ?string $city): string
{
    if ($district === null || $city === null) {
        return '—';
    }

    return $district . ', ' . $city;
}

/* ============================================================ */
/* PLATFORM ANALYTICS                                              */
/* ============================================================ */

/**
 * Account counts by role, split into active / deleted.
 * @return array<string, array{active:int, deleted:int}>
 */
function admin_fetch_user_counts(mysqli $db): array
{
    $counts = [
        'Student' => ['active' => 0, 'deleted' => 0],
        'Official' => ['active' => 0, 'deleted' => 0],
        'Admin' => ['active' => 0, 'deleted' => 0],
    ];

    $result = $db->query('SELECT role, is_deleted, COUNT(*) AS total FROM users GROUP BY role, is_deleted');
    while ($row = $result->fetch_assoc()) {
        $role = (string) $row['role'];
        if (!isset($counts[$role])) {
            continue;
        }
        $key = ((int) $row['is_deleted']) === 1 ? 'deleted' : 'active';
        $counts[$role][$key] = (int) $row['total'];
    }

    return $counts;
}

/**
 * Report counts by status (non-deleted reports only). All four statuses are
 * always present in the result, defaulting to 0.
 * @return array<string, int>
 */
function admin_fetch_report_status_counts(mysqli $db): array
{
    $counts = ['Pending' => 0, 'Verified' => 0, 'Resolved' => 0, 'Rejected' => 0];

    $result = $db->query("SELECT status, COUNT(*) AS total FROM reports WHERE is_deleted = FALSE GROUP BY status");
    while ($row = $result->fetch_assoc()) {
        $status = (string) $row['status'];
        if (array_key_exists($status, $counts)) {
            $counts[$status] = (int) $row['total'];
        }
    }

    return $counts;
}

/**
 * Report counts per active category, for the category breakdown chart.
 * @return list<array{category_name:string, total:int}>
 */
function admin_fetch_reports_by_category(mysqli $db): array
{
    $sql = <<<'SQL'
        SELECT c.category_name, COUNT(r.report_id) AS total
        FROM categories c
        LEFT JOIN reports r ON r.category_id = c.category_id AND r.is_deleted = FALSE
        WHERE c.is_active = TRUE
        GROUP BY c.category_id, c.category_name
        ORDER BY total DESC, c.category_name ASC
    SQL;

    $result = $db->query($sql);
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = [
            'category_name' => (string) $row['category_name'],
            'total' => (int) $row['total'],
        ];
    }

    return $rows;
}

/**
 * Daily report submission counts for the last $days days (zero-filled for gaps).
 * @return list<array{date:string, total:int}>
 */
function admin_fetch_reports_timeseries(mysqli $db, int $days = 14): array
{
    $stmt = $db->prepare(
        'SELECT DATE(created_at) AS report_date, COUNT(*) AS total
         FROM reports
         WHERE is_deleted = FALSE
           AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
         GROUP BY DATE(created_at)
         ORDER BY report_date ASC'
    );
    $stmt->bind_param('i', $days);
    $stmt->execute();

    $byDate = [];
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $byDate[(string) $row['report_date']] = (int) $row['total'];
    }

    // Zero-fill so the trend line has one point per day, even with no reports.
    $series = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-{$i} days"));
        $series[] = [
            'date' => $date,
            'total' => $byDate[$date] ?? 0,
        ];
    }

    return $series;
}

/**
 * Locations with the most (non-deleted) reports, for the "hotspot" chart.
 * @return list<array{city:string, district:string, total:int}>
 */
function admin_fetch_top_locations(mysqli $db, int $limit = 5): array
{
    $sql = <<<'SQL'
        SELECT l.city, l.district, COUNT(r.report_id) AS total
        FROM locations l
        INNER JOIN reports r ON r.location_id = l.location_id AND r.is_deleted = FALSE
        GROUP BY l.location_id, l.city, l.district
        ORDER BY total DESC
        LIMIT ?
    SQL;

    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $limit);
    $stmt->execute();

    $rows = [];
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $rows[] = [
            'city' => (string) $row['city'],
            'district' => (string) $row['district'],
            'total' => (int) $row['total'],
        ];
    }

    return $rows;
}

/**
 * Most recent audit log entries, joined with the acting user, for the activity feed.
 * @return list<array<string, mixed>>
 */
function admin_fetch_recent_audit_logs(mysqli $db, int $limit = 10): array
{
    $sql = <<<'SQL'
        SELECT al.log_id, al.action, al.entity_type, al.entity_id, al.description, al.created_at,
               u.username, u.role
        FROM audit_logs al
        INNER JOIN users u ON u.user_id = al.user_id
        ORDER BY al.created_at DESC, al.log_id DESC
        LIMIT ?
    SQL;

    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $limit);
    $stmt->execute();

    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/** Simple scalar counters used by the summary cards row. */
function admin_fetch_platform_totals(mysqli $db): array
{
    $totals = [
        'active_advisories' => 0,
        'total_comments' => 0,
        'total_upvotes' => 0,
    ];

    $advisoryResult = $db->query('SELECT COUNT(*) AS total FROM advisories WHERE is_active = TRUE');
    $totals['active_advisories'] = (int) ($advisoryResult->fetch_assoc()['total'] ?? 0);

    $engagementResult = $db->query(
        'SELECT
            (SELECT COUNT(*) FROM comments WHERE is_deleted = FALSE) AS total_comments,
            (SELECT COUNT(*) FROM upvotes) AS total_upvotes'
    );
    $engagementRow = $engagementResult->fetch_assoc() ?: [];
    $totals['total_comments'] = (int) ($engagementRow['total_comments'] ?? 0);
    $totals['total_upvotes'] = (int) ($engagementRow['total_upvotes'] ?? 0);

    return $totals;
}
