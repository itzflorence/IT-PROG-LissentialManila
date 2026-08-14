<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/thread-query.php';

require_login('../auth/login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: user-my-reports.php');
    exit;
}

$currentUserId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT);
if (!$currentUserId) {
    header('Location: ../auth/login.php');
    exit;
}

$reportId = filter_input(INPUT_POST, 'report_id', FILTER_VALIDATE_INT);
$title = trim((string) ($_POST['title'] ?? ''));
$description = trim((string) ($_POST['description'] ?? ''));
$categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
$locationId = filter_input(INPUT_POST, 'location_id', FILTER_VALIDATE_INT);

// Basic Validation
if (!$reportId || $title === '' || !$categoryId || !$locationId) {
    header('Location: user-edit-report.php?id=' . $reportId . '&error=' . urlencode('Please fill in all required fields.'));
    exit;
}

if (strlen($title) > 255) {
    header('Location: user-edit-report.php?id=' . $reportId . '&error=' . urlencode('The title cannot exceed 255 characters.'));
    exit;
}

try {
    $db = thread_db();

    $authCheck = $db->prepare('SELECT status FROM reports WHERE report_id = ? AND user_id = ? AND is_deleted = FALSE');
    $authCheck->bind_param('ii', $reportId, $currentUserId);
    $authCheck->execute();
    $report = $authCheck->get_result()->fetch_assoc();

    if (!$report) {
        header('Location: user-my-reports.php?error=' . urlencode('Report not found or permission denied.'));
        exit;
    }

    if (in_array($report['status'], ['Verified', 'Resolved'], true)) {
        header('Location: user-my-reports.php?error=' . urlencode('Verified or Resolved reports cannot be edited.'));
        exit;
    }

    $db->begin_transaction();
    $stmt = $db->prepare('UPDATE reports SET title = ?, description = ?, category_id = ?, location_id = ? WHERE report_id = ?');
    $stmt->bind_param('ssiii', $title, $description, $categoryId, $locationId, $reportId);
    $stmt->execute();

    if (isset($_FILES['media_files']) && !empty($_FILES['media_files']['name'][0])) {

        $deleteMedia = $db->prepare('DELETE FROM media_attachments WHERE report_id = ?');
        $deleteMedia->bind_param('i', $reportId);
        $deleteMedia->execute();

        $uploadDir = __DIR__ . '/../../assets/report_media/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileCount = count($_FILES['media_files']['name']);
        $mediaStmt = $db->prepare('INSERT INTO media_attachments (report_id, file_url, file_type) VALUES (?, ?, ?)');

        for ($i = 0; $i < $fileCount; $i++) {
            if ($_FILES['media_files']['error'][$i] === UPLOAD_ERR_OK) {

                $tmpName = $_FILES['media_files']['tmp_name'][$i];
                $originalName = basename($_FILES['media_files']['name'][$i]);
                $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

                $mime = mime_content_type($tmpName);
                $dbFileType = str_starts_with($mime, 'video/') ? 'video' : 'photo';

                $uniqueName = 'rep_' . $reportId . '_edit_' . time() . '_' . $i . '.' . $extension;
                $targetPath = $uploadDir . $uniqueName;

                if (move_uploaded_file($tmpName, $targetPath)) {
                    $dbUrl = '../../assets/report_media/' . $uniqueName;
                    $mediaStmt->bind_param('iss', $reportId, $dbUrl, $dbFileType);
                    $mediaStmt->execute();
                }
            }
        }
    }

    $db->commit();

    header('Location: user-report-details.php?id=' . $reportId);
    exit;

} catch (Throwable $e) {
    if (isset($db)) {
        $db->rollback();
    }
    header('Location: user-edit-report.php?id=' . $reportId . '&error=' . urlencode('An error occurred while saving the report.'));
    exit;
}
