<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/thread-query.php';
require_once __DIR__ . '/../../includes/official-query.php';
require_once __DIR__ . '/../../includes/admin-query.php';

require_role(['Admin'], '../auth/login.php', '../../index.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin-manage-accounts.php');
    exit;
}

$currentUserId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$currentUserId = $currentUserId === false ? null : $currentUserId;

if ($currentUserId === null) {
    header('Location: ../auth/login.php');
    exit;
}

$action = trim((string) ($_POST['action'] ?? ''));
$redirectBase = 'admin-manage-accounts.php';

try {
    $db = thread_db();

    if ($action === 'create') {
        $firstName = trim((string) ($_POST['first_name'] ?? ''));
        $lastName = trim((string) ($_POST['last_name'] ?? ''));
        $username = trim((string) ($_POST['username'] ?? ''));
        $emailRaw = trim((string) ($_POST['email'] ?? ''));
        $email = $emailRaw === '' ? null : $emailRaw;
        $phoneNumber = trim((string) ($_POST['phone_number'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $role = trim((string) ($_POST['role'] ?? ''));

        $homeLocationId = filter_input(INPUT_POST, 'home_location_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $homeLocationId = $homeLocationId === false ? null : $homeLocationId;

        $assignedLocationIdRaw = trim((string) ($_POST['assigned_location_id'] ?? ''));
        $assignedLocationId = null;
        if ($assignedLocationIdRaw !== '') {
            $assignedLocationId = filter_var($assignedLocationIdRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $assignedLocationId = $assignedLocationId === false ? null : $assignedLocationId;
        }

        // Validation
        if ($firstName === '' || $lastName === '' || $username === '' || $phoneNumber === '' || $password === '') {
            header('Location: ' . $redirectBase . '?error=' . urlencode('Please fill in all required fields.'));
            exit;
        }
        if (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $username)) {
            header('Location: ' . $redirectBase . '?error=' . urlencode('Username must be 3-50 chars and use only letters, numbers, or underscores.'));
            exit;
        }
        if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header('Location: ' . $redirectBase . '?error=' . urlencode('Please provide a valid email address.'));
            exit;
        }
        if (strlen($password) < 8) {
            header('Location: ' . $redirectBase . '?error=' . urlencode('Password must be at least 8 characters long.'));
            exit;
        }
        if (!in_array($role, ADMIN_ACCOUNT_ROLES, true)) {
            header('Location: ' . $redirectBase . '?error=' . urlencode('Please select a valid role.'));
            exit;
        }
        if ($homeLocationId === null) {
            header('Location: ' . $redirectBase . '?error=' . urlencode('Please select a home location.'));
            exit;
        }

        $newUserId = admin_create_account($db, [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'username' => $username,
            'email' => $email,
            'phone_number' => $phoneNumber,
            'password' => $password,
            'role' => $role,
            'assigned_location_id' => $assignedLocationId,
            'home_location_id' => $homeLocationId,
        ]);

        official_log_action(
            $db,
            $currentUserId,
            'Create User',
            'user',
            $newUserId,
            "Created {$role} account \"{$username}\" ({$firstName} {$lastName})."
        );

        header('Location: ' . $redirectBase . '?success=created');
        exit;
    }

    if ($action === 'update') {
        $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $userId = $userId === false ? null : $userId;

        if ($userId === null) {
            header('Location: ' . $redirectBase . '?error=' . urlencode('Invalid account reference.'));
            exit;
        }

        $existing = admin_fetch_account($db, $userId);
        if ($existing === null) {
            header('Location: ' . $redirectBase . '?error=' . urlencode('That account no longer exists.'));
            exit;
        }

        $firstName = trim((string) ($_POST['first_name'] ?? ''));
        $lastName = trim((string) ($_POST['last_name'] ?? ''));
        $username = trim((string) ($_POST['username'] ?? ''));
        $emailRaw = trim((string) ($_POST['email'] ?? ''));
        $email = $emailRaw === '' ? null : $emailRaw;
        $phoneNumber = trim((string) ($_POST['phone_number'] ?? ''));
        $password = (string) ($_POST['password'] ?? ''); // optional on edit
        $role = trim((string) ($_POST['role'] ?? ''));

        $homeLocationId = filter_input(INPUT_POST, 'home_location_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $homeLocationId = $homeLocationId === false ? null : $homeLocationId;

        $assignedLocationIdRaw = trim((string) ($_POST['assigned_location_id'] ?? ''));
        $assignedLocationId = null;
        if ($assignedLocationIdRaw !== '') {
            $assignedLocationId = filter_var($assignedLocationIdRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $assignedLocationId = $assignedLocationId === false ? null : $assignedLocationId;
        }

        $redirectBack = $redirectBase;

        if ($firstName === '' || $lastName === '' || $username === '' || $phoneNumber === '') {
            header('Location: ' . $redirectBack . '?error=' . urlencode('Please fill in all required fields.'));
            exit;
        }
        if (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $username)) {
            header('Location: ' . $redirectBack . '?error=' . urlencode('Username must be 3-50 chars and use only letters, numbers, or underscores.'));
            exit;
        }
        if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header('Location: ' . $redirectBack . '?error=' . urlencode('Please provide a valid email address.'));
            exit;
        }
        if ($password !== '' && strlen($password) < 8) {
            header('Location: ' . $redirectBack . '?error=' . urlencode('New password must be at least 8 characters long.'));
            exit;
        }
        if (!in_array($role, ADMIN_ACCOUNT_ROLES, true)) {
            header('Location: ' . $redirectBack . '?error=' . urlencode('Please select a valid role.'));
            exit;
        }
        if ($homeLocationId === null) {
            header('Location: ' . $redirectBack . '?error=' . urlencode('Please select a home location.'));
            exit;
        }

        // Prevent an admin from demoting their own last Admin account by accident.
        if ($userId === $currentUserId && $role !== 'Admin') {
            header('Location: ' . $redirectBack . '?error=' . urlencode('You cannot change your own role away from Admin.'));
            exit;
        }

        admin_update_account($db, $userId, [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'username' => $username,
            'email' => $email,
            'phone_number' => $phoneNumber,
            'password' => $password,
            'role' => $role,
            'assigned_location_id' => $assignedLocationId,
            'home_location_id' => $homeLocationId,
        ]);

        $action_label = $existing['role'] !== $role ? 'Change User Role' : 'Edit User';
        official_log_action(
            $db,
            $currentUserId,
            $action_label,
            'user',
            $userId,
            "Updated account \"{$username}\" ({$firstName} {$lastName})."
        );

        header('Location: ' . $redirectBack . '?success=updated');
        exit;
    }

    if ($action === 'delete') {
        $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $userId = $userId === false ? null : $userId;

        if ($userId === null) {
            header('Location: ' . $redirectBase . '?error=' . urlencode('Invalid account reference.'));
            exit;
        }

        if ($userId === $currentUserId) {
            header('Location: ' . $redirectBase . '?error=' . urlencode('You cannot delete your own account while logged in.'));
            exit;
        }

        $existing = admin_fetch_account($db, $userId);
        if ($existing === null) {
            header('Location: ' . $redirectBase . '?error=' . urlencode('That account no longer exists.'));
            exit;
        }

        admin_soft_delete_account($db, $userId);
        official_log_action(
            $db,
            $currentUserId,
            'Delete User',
            'user',
            $userId,
            "Deactivated account \"{$existing['username']}\"."
        );

        header('Location: ' . $redirectBase . '?success=deleted');
        exit;
    }

    if ($action === 'restore') {
        $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $userId = $userId === false ? null : $userId;

        if ($userId === null) {
            header('Location: ' . $redirectBase . '?error=' . urlencode('Invalid account reference.'));
            exit;
        }

        $existing = admin_fetch_account($db, $userId);
        if ($existing === null) {
            header('Location: ' . $redirectBase . '?error=' . urlencode('That account no longer exists.'));
            exit;
        }

        admin_restore_account($db, $userId);
        official_log_action(
            $db,
            $currentUserId,
            'Restore User',
            'user',
            $userId,
            "Restored account \"{$existing['username']}\"."
        );

        header('Location: ' . $redirectBase . '?success=restored');
        exit;
    }

    header('Location: ' . $redirectBase . '?error=' . urlencode('Unknown action.'));
    exit;
} catch (mysqli_sql_exception $error) {
    if ((int) $error->getCode() === 1062) {
        header('Location: ' . $redirectBase . '?error=' . urlencode('Username or phone number already exists.'));
        exit;
    }
    header('Location: ' . $redirectBase . '?error=' . urlencode('A database error occurred. Please try again.'));
    exit;
} catch (Throwable $error) {
    header('Location: ' . $redirectBase . '?error=' . urlencode('Unable to complete that action. Please try again.'));
    exit;
}
