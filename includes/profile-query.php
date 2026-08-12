<?php

declare(strict_types=1);

/**
 * Query/helper layer for the "Account Profile" page.
 * Unlike admin-query.php (admin managing *other* accounts), this module is
 * for a logged-in user viewing/editing their *own* account.
 */

/** @return array<string, mixed>|null */
function profile_fetch_account(mysqli $db, int $userId): ?array
{
    $sql = <<<'SQL'
        SELECT
            u.user_id, u.phone_number, u.email, u.first_name, u.last_name, u.username,
            u.role, u.assigned_location_id, u.home_location_id, u.created_at,
            u.pending_phone_number, u.is_phone_verified, u.phone_verification_expires_at,
            hl.city AS home_city, hl.district AS home_district,
            al.city AS assigned_city, al.district AS assigned_district
        FROM users u
        INNER JOIN locations hl ON hl.location_id = u.home_location_id
        LEFT JOIN locations al ON al.location_id = u.assigned_location_id
        WHERE u.user_id = ?
          AND u.is_deleted = FALSE
        LIMIT 1
    SQL;

    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    return $row ?: null;
}

function profile_update_basic_info(mysqli $db, int $userId, string $firstName, string $lastName, ?string $email, int $homeLocationId): void
{
    $stmt = $db->prepare(
        'UPDATE users SET first_name = ?, last_name = ?, email = ?, home_location_id = ? WHERE user_id = ?'
    );
    $stmt->bind_param('sssii', $firstName, $lastName, $email, $homeLocationId, $userId);
    $stmt->execute();
}

/** Fetches just the password hash, for verifying the "current password" field. */
function profile_verify_current_password(mysqli $db, int $userId, string $suppliedPassword): bool
{
    $stmt = $db->prepare('SELECT password_hash FROM users WHERE user_id = ? LIMIT 1');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row) {
        return false;
    }

    return password_verify($suppliedPassword, (string) $row['password_hash']);
}

function profile_update_password(mysqli $db, int $userId, string $newPassword): void
{
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $db->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?');
    $stmt->bind_param('si', $hash, $userId);
    $stmt->execute();
}

/**
 * Starts a phone-number change: stores the new number as "pending" plus a
 * verification code, without touching the live phone_number column yet.
 * Returns the generated code (there's no SMS gateway wired up, so the caller
 * displays it directly as a dev-mode stand-in for an actual SMS).
 */
function profile_request_phone_change(mysqli $db, int $userId, string $newPhoneNumber): string
{
    $code = (string) random_int(100000, 999999);
    $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

    $stmt = $db->prepare(
        'UPDATE users
         SET pending_phone_number = ?, phone_verification_code = ?, phone_verification_expires_at = ?, is_phone_verified = FALSE
         WHERE user_id = ?'
    );
    $stmt->bind_param('sssi', $newPhoneNumber, $code, $expiresAt, $userId);
    $stmt->execute();

    return $code;
}

/**
 * Confirms a pending phone-number change if the submitted code matches and
 * hasn't expired. Returns true on success.
 */
function profile_confirm_phone_change(mysqli $db, int $userId, string $submittedCode): bool
{
    $stmt = $db->prepare(
        'SELECT pending_phone_number, phone_verification_code, phone_verification_expires_at
         FROM users WHERE user_id = ? LIMIT 1'
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row || $row['pending_phone_number'] === null || $row['phone_verification_code'] === null) {
        return false;
    }

    if ($row['phone_verification_code'] !== $submittedCode) {
        return false;
    }

    $expiresAt = $row['phone_verification_expires_at'];
    if ($expiresAt !== null && strtotime((string) $expiresAt) < time()) {
        return false;
    }

    $updateStmt = $db->prepare(
        'UPDATE users
         SET phone_number = pending_phone_number,
             pending_phone_number = NULL,
             phone_verification_code = NULL,
             phone_verification_expires_at = NULL,
             is_phone_verified = TRUE
         WHERE user_id = ?'
    );
    $updateStmt->bind_param('i', $userId);
    $updateStmt->execute();

    return true;
}

function profile_cancel_phone_change(mysqli $db, int $userId): void
{
    $stmt = $db->prepare(
        'UPDATE users
         SET pending_phone_number = NULL, phone_verification_code = NULL,
             phone_verification_expires_at = NULL, is_phone_verified = TRUE
         WHERE user_id = ?'
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
}
