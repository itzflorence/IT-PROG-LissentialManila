<?php declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/thread-query.php';
require_once __DIR__ . '/../../includes/report-feed.php';
require_once __DIR__ . '/../../includes/official-query.php';

require_role(['Official', 'Admin'], '../auth/login.php', '../../index.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: official-create-thread.php');
    exit;
}

$currentUserId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$currentUserId = $currentUserId === false ? null : $currentUserId;

$reportId = filter_input(INPUT_POST, 'report_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$reportId = $reportId === false ? null : $reportId;

$redirectBack = 'official-create-thread.php' . ($reportId !== null ? '?report_id=' . $reportId : '');

if ($currentUserId === null) {
    header('Location: ' . $redirectBack . ($reportId !== null ? '&' : '?') . 'error=' . urlencode('Your session expired. Please log in again.'));
    exit;
}

$postType = trim((string) ($_POST['post_type'] ?? 'thread'));
$title = trim((string) ($_POST['title'] ?? ''));
$locationId = filter_input(INPUT_POST, 'location_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$locationId = $locationId === false ? null : $locationId;
$description = trim((string) ($_POST['description'] ?? ''));

// Shared Validation
if ($title === '' || (function_exists('mb_strlen') ? mb_strlen($title) : strlen($title)) > 255) {
    header('Location: ' . $redirectBack . ($reportId !== null ? '&' : '?') . 'error=' . urlencode('Title is required and must be 255 characters or fewer.'));
    exit;
}
if ($locationId === null) {
    header('Location: ' . $redirectBack . ($reportId !== null ? '&' : '?') . 'error=' . urlencode('Please select a valid location.'));
    exit;
}
if ($description === '') {
    header('Location: ' . $redirectBack . ($reportId !== null ? '&' : '?') . 'error=' . urlencode('Description/Details cannot be empty.'));
    exit;
}

try {
    $db = thread_db();

    // ==========================================
    // ROUTE 1: OFFICIAL ADVISORY
    // ==========================================
    if ($postType === 'advisory') {
        $stmt = $db->prepare('INSERT INTO advisories (title, content, location_id, posted_by) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('ssii', $title, $description, $locationId, $currentUserId);
        $stmt->execute();

        $advisoryId = (int) $db->insert_id;
        official_log_action($db, $currentUserId, 'Create Advisory', 'advisory', $advisoryId, "Posted advisory \"{$title}\".");

        header('Location: official-home.php?success=advisory_created');
        exit;
    }

    // ==========================================
    // ROUTE 2: INCIDENT THREAD (Default)
    // ==========================================
    $categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $categoryId = $categoryId === false ? null : $categoryId;
    $status = trim((string) ($_POST['status'] ?? 'Active'));
    $allowedThreadStatuses = ['Active', 'Resolved', 'Archived'];

    if ($categoryId === null) {
        header('Location: ' . $redirectBack . ($reportId !== null ? '&' : '?') . 'error=' . urlencode('Please select a valid category.'));
        exit;
    }
    if (!in_array($status, $allowedThreadStatuses, true)) {
        $status = 'Active';
    }

    $insertStmt = $db->prepare(
        'INSERT INTO threads (title, location_id, category_id, created_by, status, description) 
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $insertStmt->bind_param('siiiss', $title, $locationId, $categoryId, $currentUserId, $status, $description);
    $insertStmt->execute();

    $newThreadId = (int) $db->insert_id;
    $logDescription = "Created thread #{$newThreadId} (\"{$title}\").";

    if ($reportId !== null) {
        $linkStmt = $db->prepare('UPDATE reports SET thread_id = ? WHERE report_id = ? AND is_deleted = FALSE');
        $linkStmt->bind_param('ii', $newThreadId, $reportId);
        $linkStmt->execute();
        if ($linkStmt->affected_rows > 0) {
            $logDescription .= " Linked report #{$reportId} to it.";
        }
    }

    official_recalculate_thread_counts($db, $newThreadId);
    official_log_action($db, $currentUserId, 'Create Thread', 'thread', $newThreadId, $logDescription);

    if ($reportId !== null) {
        header('Location: official-edit-report.php?id=' . $reportId . '&updated=1');
        exit;
    }

    header('Location: official-edit-thread.php?id=' . $newThreadId . '&created=1');
    exit;

} catch (Throwable $error) {
    header('Location: ' . $redirectBack . ($reportId !== null ? '&' : '?') . 'error=' . urlencode('Unable to process the request. Please check your inputs and try again.'));
    exit;
}