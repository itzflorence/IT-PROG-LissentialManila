<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/thread-query.php';
require_once __DIR__ . '/../../includes/report-feed.php';
require_once __DIR__ . '/../../includes/official-query.php';
require_once __DIR__ . '/../../includes/report-media.php';

require_login('../auth/login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: user-create-report.php');
    exit;
}

$currentUserId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$currentUserId = $currentUserId === false ? null : $currentUserId;

if ($currentUserId === null) {
    header('Location: ../auth/login.php');
    exit;
}

$action = trim((string) ($_POST['action'] ?? 'create'));
$action = in_array($action, ['create', 'update'], true) ? $action : 'create';

$reportId = null;
if ($action === 'update') {
    $reportId = filter_input(INPUT_POST, 'report_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $reportId = $reportId === false ? null : $reportId;
}

$redirectBack = $action === 'update' && $reportId !== null
    ? 'user-edit-report.php?id=' . $reportId
    : 'user-create-report.php';

$title = trim((string) ($_POST['title'] ?? ''));
$description = trim((string) ($_POST['description'] ?? ''));
$locationId = filter_input(INPUT_POST, 'location_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$locationId = $locationId === false ? null : $locationId;
$categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$categoryId = $categoryId === false ? null : $categoryId;

if ($title === '' || (function_exists('mb_strlen') ? mb_strlen($title) : strlen($title)) > 255) {
    header('Location: ' . $redirectBack . '&error=' . urlencode('Title is required and must be 255 characters or fewer.'));
    exit;
}

if ($locationId === null) {
    header('Location: ' . $redirectBack . '&error=' . urlencode('Please select a valid location.'));
    exit;
}

if ($categoryId === null) {
    header('Location: ' . $redirectBack . '&error=' . urlencode('Please select a valid category.'));
    exit;
}

if ($action === 'update' && $reportId === null) {
    header('Location: user-my-reports.php?error=' . urlencode('Invalid report reference.'));
    exit;
}

try {
    $db = thread_db();

    $locationCheck = $db->prepare('SELECT 1 FROM locations WHERE location_id = ? AND is_active = TRUE LIMIT 1');
    $locationCheck->bind_param('i', $locationId);
    $locationCheck->execute();
    if (!$locationCheck->get_result()->fetch_row()) {
        header('Location: ' . $redirectBack . '&error=' . urlencode('Please select a valid location.'));
        exit;
    }

    $categoryCheck = $db->prepare('SELECT requires_description FROM categories WHERE category_id = ? AND is_active = TRUE LIMIT 1');
    $categoryCheck->bind_param('i', $categoryId);
    $categoryCheck->execute();
    $categoryRow = $categoryCheck->get_result()->fetch_assoc();
    if (!$categoryRow) {
        header('Location: ' . $redirectBack . '&error=' . urlencode('Please select a valid category.'));
        exit;
    }

    if ((int) $categoryRow['requires_description'] === 1 && $description === '') {
        header('Location: ' . $redirectBack . '&error=' . urlencode('Please add a description for this category.'));
        exit;
    }

    if ($action === 'create') {
        $insert = $db->prepare(
            'INSERT INTO reports (user_id, location_id, category_id, title, description)
             VALUES (?, ?, ?, ?, ?)'
        );
        $insert->bind_param('iiiss', $currentUserId, $locationId, $categoryId, $title, $description);
        $insert->execute();
        $reportId = (int) $db->insert_id;

        save_report_media_uploads($db, $reportId, $_FILES['media'] ?? [], REPORT_MEDIA_DISPLAY_LIMIT);

        official_log_action($db, $currentUserId, 'Create Report', 'report', $reportId, "Created report #{$reportId} (\"{$title}\").");

        header('Location: user-report-details.php?id=' . $reportId . '&created=1');
        exit;
    }

    // action === 'update'
    $ownershipCheck = $db->prepare('SELECT user_id FROM reports WHERE report_id = ? AND is_deleted = FALSE LIMIT 1');
    $ownershipCheck->bind_param('i', $reportId);
    $ownershipCheck->execute();
    $existing = $ownershipCheck->get_result()->fetch_assoc();

    if (!$existing || (int) $existing['user_id'] !== $currentUserId) {
        header('Location: user-my-reports.php?error=' . urlencode('You can only edit your own reports.'));
        exit;
    }

    $update = $db->prepare(
        'UPDATE reports
         SET title = ?, description = ?, location_id = ?, category_id = ?
         WHERE report_id = ? AND user_id = ? AND is_deleted = FALSE'
    );
    $update->bind_param('ssiiii', $title, $description, $locationId, $categoryId, $reportId, $currentUserId);
    $update->execute();

    $removeMediaIds = $_POST['remove_media'] ?? [];
    if (is_array($removeMediaIds) && $removeMediaIds !== []) {
        remove_report_media($db, $reportId, $removeMediaIds);
    }

    $remainingSlots = REPORT_MEDIA_DISPLAY_LIMIT - count_report_media($db, $reportId);
    save_report_media_uploads($db, $reportId, $_FILES['media'] ?? [], $remainingSlots);

    official_log_action($db, $currentUserId, 'Edit Report', 'report', $reportId, "Updated report #{$reportId} (\"{$title}\").");

    header('Location: user-report-details.php?id=' . $reportId . '&updated=1');
    exit;
} catch (Throwable $error) {
    header('Location: ' . $redirectBack . '&error=' . urlencode('Unable to save this report. Please try again.'));
    exit;
}
