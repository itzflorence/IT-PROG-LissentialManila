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

$reportId = filter_input(INPUT_POST, 'report_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$reportId = $reportId === false ? null : $reportId;

if ($reportId === null || $currentUserId === null) {
    header('Location: official-home.php?error=' . urlencode('Invalid report reference. Please try again.'));
    exit;
}

$redirectBack = 'official-edit-report.php?id=' . $reportId;

$title = trim((string) ($_POST['title'] ?? ''));
$description = trim((string) ($_POST['description'] ?? ''));
$categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$categoryId = $categoryId === false ? null : $categoryId;
$locationId = filter_input(INPUT_POST, 'location_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$locationId = $locationId === false ? null : $locationId;
$status = trim((string) ($_POST['status'] ?? ''));
$remarks = trim((string) ($_POST['verification_remarks'] ?? ''));
$remarks = $remarks === '' ? null : $remarks;

$threadIdRaw = trim((string) ($_POST['thread_id'] ?? ''));
$threadId = null;
if ($threadIdRaw !== '') {
    $threadId = filter_var($threadIdRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $threadId = $threadId === false ? false : $threadId; // false marks "was provided but invalid"
}

// Validation 
if ($title === '' || (function_exists('mb_strlen') ? mb_strlen($title) : strlen($title)) > 255) {
    header('Location: ' . $redirectBack . '&error=' . urlencode('Title is required and must be 255 characters or fewer.'));
    exit;
}

if ($description === '') {
    header('Location: ' . $redirectBack . '&error=' . urlencode('Description is required.'));
    exit;
}

if ($categoryId === null) {
    header('Location: ' . $redirectBack . '&error=' . urlencode('Please select a valid category.'));
    exit;
}

if ($locationId === null) {
    header('Location: ' . $redirectBack . '&error=' . urlencode('Please select a valid location.'));
    exit;
}

if (!in_array($status, OFFICIAL_REPORT_STATUSES, true)) {
    header('Location: ' . $redirectBack . '&error=' . urlencode('Please select a valid status.'));
    exit;
}

if ($status === 'Rejected' && $remarks === null) {
    header('Location: ' . $redirectBack . '&error=' . urlencode('A remark is required when rejecting a report.'));
    exit;
}

if ($threadId === false) {
    header('Location: ' . $redirectBack . '&error=' . urlencode('Invalid thread selection.'));
    exit;
}

try {
    $db = thread_db();

    // Snapshot the report as it stands before this edit, for count recalculation + audit logging.
    $beforeStmt = $db->prepare('SELECT thread_id, status FROM reports WHERE report_id = ? AND is_deleted = FALSE LIMIT 1');
    $beforeStmt->bind_param('i', $reportId);
    $beforeStmt->execute();
    $before = $beforeStmt->get_result()->fetch_assoc();

    if (!$before) {
        header('Location: official-home.php?error=' . urlencode('That report no longer exists.'));
        exit;
    }

    $oldThreadId = $before['thread_id'] !== null ? (int) $before['thread_id'] : null;
    $oldStatus = (string) $before['status'];

    // A report only carries verified_by/verified_at once an official has actually acted on it.
    $verifiedBy = $status === 'Pending' ? null : $currentUserId;
    $verifiedAt = $status === 'Pending' ? null : date('Y-m-d H:i:s');

    $updateStmt = $db->prepare(
        'UPDATE reports
         SET title = ?,
             description = ?,
             category_id = ?,
             location_id = ?,
             status = ?,
             verification_remarks = ?,
             verified_by = ?,
             verified_at = ?,
             thread_id = ?
         WHERE report_id = ?
           AND is_deleted = FALSE'
    );
    $updateStmt->bind_param(
        'ssiissisii',
        $title,
        $description,
        $categoryId,
        $locationId,
        $status,
        $remarks,
        $verifiedBy,
        $verifiedAt,
        $threadId,
        $reportId
    );
    $updateStmt->execute();

    // Keep both the old and new thread's counters accurate after a reassignment.
    if ($oldThreadId !== null) {
        official_recalculate_thread_counts($db, $oldThreadId);
    }
    if ($threadId !== null && $threadId !== $oldThreadId) {
        official_recalculate_thread_counts($db, $threadId);
    }

    $action = official_audit_action_for_status($oldStatus, $status);
    $logDescription = "Updated report #{$reportId} (\"{$title}\"): status {$oldStatus} -> {$status}.";
    if ($threadId !== $oldThreadId) {
        $logDescription .= $threadId === null
            ? ' Removed from its thread.'
            : " Reassigned to thread #{$threadId}.";
    }
    official_log_action($db, $currentUserId, $action, 'report', $reportId, $logDescription);

    header('Location: ' . $redirectBack . '&updated=1');
    exit;
} catch (Throwable $error) {
    header('Location: ' . $redirectBack . '&error=' . urlencode('Unable to save changes. Please check your inputs and try again.'));
    exit;
}