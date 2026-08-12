<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/thread-query.php';
require_once __DIR__ . '/../../includes/profile-query.php';

require_login('../auth/login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: user-profile.php');
    exit;
}

$currentUserId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$currentUserId = $currentUserId === false ? null : $currentUserId;

if ($currentUserId === null) {
    header('Location: ../auth/login.php');
    exit;
}

$action = trim((string) ($_POST['action'] ?? ''));
$redirectBase = 'user-profile.php';

try {
    $db = thread_db();

    if ($action === 'update_info') {
        $firstName = trim((string) ($_POST['first_name'] ?? ''));
        $lastName = trim((string) ($_POST['last_name'] ?? ''));
        $emailRaw = trim((string) ($_POST['email'] ?? ''));
        $email = $emailRaw === '' ? null : $emailRaw;
        $homeLocationId = filter_input(INPUT_POST, 'home_location_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $homeLocationId = $homeLocationId === false ? null : $homeLocationId;

        if ($firstName === '' || $lastName === '') {
            header('Location: ' . $redirectBase . '?error=' . urlencode('First and last name are required.'));
            exit;
        }
        if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header('Location: ' . $redirectBase . '?error=' . urlencode('Please provide a valid email address.'));
            exit;
        }
        if ($homeLocationId === null) {
            header('Location: ' . $redirectBase . '?error=' . urlencode('Please select a home location.'));
            exit;
        }

        profile_update_basic_info($db, $currentUserId, $firstName, $lastName, $email, $homeLocationId);

        // Session username doesn't change here, but keep names fresh if displayed elsewhere.
        header('Location: ' . $redirectBase . '?success=info_updated');
        exit;
    }

    if ($action === 'change_password') {
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            header('Location: ' . $redirectBase . '?error=' . urlencode('Please fill in all password fields.'));
            exit;
        }
        if (!profile_verify_current_password($db, $currentUserId, $currentPassword)) {
            header('Location: ' . $redirectBase . '?error=' . urlencode('Your current password is incorrect.'));
            exit;
        }
        if ($newPassword !== $confirmPassword) {
            header('Location: ' . $redirectBase . '?error=' . urlencode('New passwords do not match.'));
            exit;
        }
        if (strlen($newPassword) < 8) {
            header('Location: ' . $redirectBase . '?error=' . urlencode('New password must be at least 8 characters long.'));
            exit;
        }

        profile_update_password($db, $currentUserId, $newPassword);
        header('Location: ' . $redirectBase . '?success=password_updated');
        exit;
    }

    if ($action === 'request_phone_change') {
        $newPhone = trim((string) ($_POST['new_phone_number'] ?? ''));

        if ($newPhone === '' || !preg_match('/^\+?[0-9]{7,20}$/', $newPhone)) {
            header('Location: ' . $redirectBase . '?error=' . urlencode('Please enter a valid phone number.'));
            exit;
        }

        $code = profile_request_phone_change($db, $currentUserId, $newPhone);

        // No SMS gateway is wired up yet, so the verification code is
        // surfaced directly in the redirect for now instead of being texted.
        header('Location: ' . $redirectBase . '?success=phone_requested&devcode=' . urlencode($code));
        exit;
    }

    if ($action === 'confirm_phone_change') {
        $code = trim((string) ($_POST['verification_code'] ?? ''));

        if ($code === '') {
            header('Location: ' . $redirectBase . '?error=' . urlencode('Please enter the verification code.'));
            exit;
        }

        $confirmed = profile_confirm_phone_change($db, $currentUserId, $code);
        if (!$confirmed) {
            header('Location: ' . $redirectBase . '?error=' . urlencode('That code is invalid or has expired.'));
            exit;
        }

        header('Location: ' . $redirectBase . '?success=phone_confirmed');
        exit;
    }

    if ($action === 'cancel_phone_change') {
        profile_cancel_phone_change($db, $currentUserId);
        header('Location: ' . $redirectBase . '?success=phone_cancelled');
        exit;
    }

    header('Location: ' . $redirectBase . '?error=' . urlencode('Unknown action.'));
    exit;
} catch (mysqli_sql_exception $error) {
    if ((int) $error->getCode() === 1062) {
        header('Location: ' . $redirectBase . '?error=' . urlencode('That phone number is already in use by another account.'));
        exit;
    }
    header('Location: ' . $redirectBase . '?error=' . urlencode('A database error occurred. Please try again.'));
    exit;
} catch (Throwable $error) {
    header('Location: ' . $redirectBase . '?error=' . urlencode('Unable to complete that action. Please try again.'));
    exit;
}
