<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../database/connection.php';

ensure_session_started();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.php');
    exit;
}

$firstName = trim((string) ($_POST['first_name'] ?? ''));
$lastName = trim((string) ($_POST['last_name'] ?? ''));
$username = trim((string) ($_POST['username'] ?? ''));
$emailRaw = trim((string) ($_POST['email'] ?? ''));
$phoneNumber = trim((string) ($_POST['phone_number'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$confirmPassword = (string) ($_POST['confirm_password'] ?? '');
$email = $emailRaw === '' ? null : $emailRaw;

if (
    $firstName === '' ||
    $lastName === '' ||
    $username === '' ||
    $phoneNumber === '' ||
    $password === '' ||
    $confirmPassword === ''
) {
    header('Location: register.php?error=' . urlencode('Please fill in all required fields.'));
    exit;
}

if (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $username)) {
    header('Location: register.php?error=' . urlencode('Username must be 3-50 chars and use only letters, numbers, or underscores.'));
    exit;
}

if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: register.php?error=' . urlencode('Please provide a valid email address.'));
    exit;
}

if ($password !== $confirmPassword) {
    header('Location: register.php?error=' . urlencode('Passwords do not match.'));
    exit;
}

if (strlen($password) < 8) {
    header('Location: register.php?error=' . urlencode('Password must be at least 8 characters long.'));
    exit;
}

$homeLocationId = 1;
$locationStmt = mysqli_prepare($conn, 'SELECT location_id FROM locations WHERE is_active = TRUE ORDER BY location_id ASC LIMIT 1');
if ($locationStmt) {
    mysqli_stmt_execute($locationStmt);
    mysqli_stmt_bind_result($locationStmt, $candidateLocationId);
    if (mysqli_stmt_fetch($locationStmt)) {
        $homeLocationId = (int) $candidateLocationId;
    }
    mysqli_stmt_close($locationStmt);
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);
if ($passwordHash === false) {
    header('Location: register.php?error=' . urlencode('Unable to secure password. Please try again.'));
    exit;
}

$insertSql = 'INSERT INTO users (phone_number, email, first_name, last_name, username, password_hash, role, home_location_id)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
$insertStmt = mysqli_prepare($conn, $insertSql);

if (!$insertStmt) {
    header('Location: register.php?error=' . urlencode('Unable to create account right now.'));
    exit;
}

$defaultRole = 'Student';
mysqli_stmt_bind_param(
    $insertStmt,
    'sssssssi',
    $phoneNumber,
    $email,
    $firstName,
    $lastName,
    $username,
    $passwordHash,
    $defaultRole,
    $homeLocationId
);

if (!mysqli_stmt_execute($insertStmt)) {
    $errorCode = mysqli_errno($conn);
    mysqli_stmt_close($insertStmt);

    if ($errorCode === 1062) {
        header('Location: register.php?error=' . urlencode('Username, phone number, or email already exists.'));
        exit;
    }

    header('Location: register.php?error=' . urlencode('Unable to create account right now.'));
    exit;
}

$newUserId = (int) mysqli_insert_id($conn);
mysqli_stmt_close($insertStmt);

$_SESSION['user_id'] = $newUserId;
$_SESSION['username'] = $username;
$_SESSION['role'] = $defaultRole;

header('Location: ../../index.php');
exit;
