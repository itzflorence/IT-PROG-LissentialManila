<?php

declare(strict_types=1);

/**
 * Filesystem + DB helpers for report media uploads (create/edit report forms).
 */

const REPORT_MEDIA_MAX_BYTES = 15 * 1024 * 1024;
const REPORT_MEDIA_ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'jfif', 'mp4', 'webm', 'mov'];
const REPORT_MEDIA_VIDEO_EXTENSIONS = ['mp4', 'webm', 'mov'];
const REPORT_MEDIA_UPLOAD_DIR = __DIR__ . '/../assets/report_media/';
const REPORT_MEDIA_UPLOAD_URL_PREFIX = 'assets/report_media/';

/**
 * Saves newly uploaded media files from a $_FILES['media']-shaped array, up to $remainingSlots files.
 * Invalid files (bad extension, too large, failed upload) are silently skipped.
 * @return list<array{file_url:string,file_type:string}>
 */
function save_report_media_uploads(mysqli $db, int $reportId, array $filesInput, int $remainingSlots): array
{
    $saved = [];

    if ($remainingSlots <= 0 || empty($filesInput['name']) || !is_array($filesInput['name'])) {
        return $saved;
    }

    $fileCount = count($filesInput['name']);

    for ($i = 0; $i < $fileCount && count($saved) < $remainingSlots; $i++) {
        $error = $filesInput['error'][$i] ?? UPLOAD_ERR_NO_FILE;
        if ($error !== UPLOAD_ERR_OK) {
            continue;
        }

        $tmpPath = (string) ($filesInput['tmp_name'][$i] ?? '');
        $originalName = (string) ($filesInput['name'][$i] ?? '');
        $size = (int) ($filesInput['size'][$i] ?? 0);

        if ($tmpPath === '' || !is_uploaded_file($tmpPath) || $size <= 0 || $size > REPORT_MEDIA_MAX_BYTES) {
            continue;
        }

        $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($extension, REPORT_MEDIA_ALLOWED_EXTENSIONS, true)) {
            continue;
        }

        $fileType = in_array($extension, REPORT_MEDIA_VIDEO_EXTENSIONS, true) ? 'video' : 'photo';
        $filename = 'report' . $reportId . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
        $destination = REPORT_MEDIA_UPLOAD_DIR . $filename;

        if (!move_uploaded_file($tmpPath, $destination)) {
            continue;
        }

        $fileUrl = REPORT_MEDIA_UPLOAD_URL_PREFIX . $filename;
        $insert = $db->prepare('INSERT INTO media_attachments (report_id, file_url, file_type) VALUES (?, ?, ?)');
        $insert->bind_param('iss', $reportId, $fileUrl, $fileType);
        $insert->execute();

        $saved[] = ['file_url' => $fileUrl, 'file_type' => $fileType];
    }

    return $saved;
}

/** Removes specific media attachments belonging to a report and deletes their files from disk. */
function remove_report_media(mysqli $db, int $reportId, array $mediaIds): void
{
    $mediaIds = array_values(array_unique(array_filter(array_map('intval', $mediaIds), static fn(int $id): bool => $id > 0)));
    if ($mediaIds === []) {
        return;
    }

    $placeholders = implode(',', array_fill(0, count($mediaIds), '?'));
    $types = 'i' . str_repeat('i', count($mediaIds));

    $select = $db->prepare("SELECT media_id, file_url FROM media_attachments WHERE report_id = ? AND media_id IN ($placeholders)");
    $select->bind_param($types, $reportId, ...$mediaIds);
    $select->execute();
    $rows = $select->get_result()->fetch_all(MYSQLI_ASSOC);

    if ($rows === []) {
        return;
    }

    $delete = $db->prepare("DELETE FROM media_attachments WHERE report_id = ? AND media_id IN ($placeholders)");
    $delete->bind_param($types, $reportId, ...$mediaIds);
    $delete->execute();

    foreach ($rows as $row) {
        $path = __DIR__ . '/../' . ltrim((string) $row['file_url'], '/');
        if (is_file($path)) {
            @unlink($path);
        }
    }
}

function count_report_media(mysqli $db, int $reportId): int
{
    $stmt = $db->prepare('SELECT COUNT(*) AS total FROM media_attachments WHERE report_id = ?');
    $stmt->bind_param('i', $reportId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    return (int) ($row['total'] ?? 0);
}
