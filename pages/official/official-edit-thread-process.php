<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/thread-query.php';
require_once __DIR__ . '/../../includes/report-feed.php';
require_once __DIR__ . '/../../includes/official-query.php';

require_role(['Official', 'Admin'], '../auth/login.php', '../../index.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: official-home.php');
    exit;
}

$currentUserId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$currentUserId = $currentUserId === false ? null : $currentUserId;

$threadId = filter_input(INPUT_POST, 'thread_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$threadId = $threadId === false ? null : $threadId;

if ($threadId === null || $currentUserId === null) {
    header('Location: official-home.php?error=' . urlencode('Invalid thread reference. Please try again.'));
    exit;
}

$redirectBack = 'official-edit-thread.php?id=' . $threadId;

$title = trim((string) ($_POST['title'] ?? ''));
$description = trim((string) ($_POST['description'] ?? ''));
$description = $description === '' ? null : $description;
$status = trim((string) ($_POST['status'] ?? ''));
$allowedThreadStatuses = ['Active', 'Resolved', 'Archived'];

if ($title === '' || (function_exists('mb_strlen') ? mb_strlen($title) : strlen($title)) > 255) {
    header('Location: ' . $redirectBack . '&error=' . urlencode('Thread title is required and must be 255 characters or fewer.'));
    exit;
}

if (!in_array($status, $allowedThreadStatuses, true)) {
    header('Location: ' . $redirectBack . '&error=' . urlencode('Please select a valid status.'));
    exit;
}

try {
    $db = thread_db();

    $beforeStmt = $db->prepare('SELECT status FROM threads WHERE thread_id = ? LIMIT 1');
    $beforeStmt->bind_param('i', $threadId);
    $beforeStmt->execute();
    $before = $beforeStmt->get_result()->fetch_assoc();

    if (!$before) {
        header('Location: official-home.php?error=' . urlencode('That thread no longer exists.'));
        exit;
    }

    $oldStatus = (string) $before['status'];

    $updateStmt = $db->prepare('UPDATE threads SET title = ?, description = ?, status = ? WHERE thread_id = ?');
    $updateStmt->bind_param('sssi', $title, $description, $status, $threadId);
    $updateStmt->execute();

    // Resolved cascades to every linked report
    official_cascade_thread_status_to_reports($db, $threadId, $status, $currentUserId);
    official_recalculate_thread_counts($db, $threadId);

    $action = official_audit_action_for_thread_status($oldStatus, $status);
    $logDescription = "Updated thread #{$threadId} (\"{$title}\"): status {$oldStatus} -> {$status}.";
    official_log_action($db, $currentUserId, $action, 'thread', $threadId, $logDescription);

    header('Location: ' . $redirectBack . '&updated=1');
    exit;
} catch (Throwable $error) {
    header('Location: ' . $redirectBack . '&error=' . urlencode('Unable to save changes. Please check your inputs and try again.'));
    exit;
}