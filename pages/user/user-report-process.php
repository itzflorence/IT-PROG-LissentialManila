<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/thread-query.php';

require_login('../auth/login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: user-create-report.php');
    exit;
}

$currentUserId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT);
if (!$currentUserId) {
    header('Location: ../auth/login.php');
    exit;
}

date_default_timezone_set('Asia/Manila');
$currentTimestamp = date('Y-m-d H:i:s');

$title = trim((string) ($_POST['title'] ?? ''));
$description = trim((string) ($_POST['description'] ?? ''));
$categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
$locationId = filter_input(INPUT_POST, 'location_id', FILTER_VALIDATE_INT);

if ($title === '' || !$categoryId || !$locationId) {
    header('Location: user-create-report.php?error=' . urlencode('Please fill in all required fields.'));
    exit;
}

if (strlen($title) > 255) {
    header('Location: user-create-report.php?error=' . urlencode('The title cannot exceed 255 characters.'));
    exit;
}

if (!isset($_FILES['media_files']) || empty($_FILES['media_files']['name'][0])) {
    header('Location: user-create-report.php?error=' . urlencode('At least one media attachment (photo/video) is required.'));
    exit;
}

try {
    $db = thread_db();

    $db->begin_transaction();

    $stmt = $db->prepare('INSERT INTO reports (user_id, location_id, category_id, title, description, created_at) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('iiisss', $currentUserId, $locationId, $categoryId, $title, $description, $currentTimestamp);
    $stmt->execute();

    $reportId = $db->insert_id;

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

            $uniqueName = 'rep_' . $reportId . '_' . time() . '_' . $i . '.' . $extension;
            $targetPath = $uploadDir . $uniqueName;

            if (move_uploaded_file($tmpName, $targetPath)) {
                $dbUrl = '../../assets/report_media/' . $uniqueName;
                $mediaStmt->bind_param('iss', $reportId, $dbUrl, $dbFileType);
                $mediaStmt->execute();
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
    header('Location: user-create-report.php?error=' . urlencode('An error occurred while saving the report. Please try again.'));
    exit;
}
