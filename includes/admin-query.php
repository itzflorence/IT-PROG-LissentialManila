<?php

declare(strict_types=1);

/**
 * Query/helper layer for the Admin > Manage Accounts module.
 * Mirrors the conventions used in includes/official-query.php.
 */

const ADMIN_ACCOUNT_ROLES = ['Student', 'Official', 'Admin'];

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
