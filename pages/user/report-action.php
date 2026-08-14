<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/thread-query.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !is_authenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'Please log in to interact with reports.']);
    exit;
}

$currentUserId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$reportId = filter_input(INPUT_POST, 'report_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$action = trim((string) ($_POST['action'] ?? ''));

if ($currentUserId === false || $reportId === false || !in_array($action, ['upvote', 'resolved'], true)) {
    http_response_code(422);
    echo json_encode(['error' => 'Invalid report action.']);
    exit;
}

try {
    $db = thread_db();

    $junctionTable = $action === 'upvote' ? 'upvotes' : 'resolved_marks';
    $counterColumn = $action === 'upvote' ? 'upvote_count' : 'resolved_count';

    $existing = $db->prepare("SELECT 1 FROM {$junctionTable} WHERE user_id = ? AND report_id = ? LIMIT 1");
    $existing->bind_param('ii', $currentUserId, $reportId);
    $existing->execute();
    $alreadyMarked = (bool) $existing->get_result()->fetch_row();

    if ($alreadyMarked) {
        // Clicking an already-active button undoes the mark and decrements the counter.
        $remove = $db->prepare("DELETE FROM {$junctionTable} WHERE user_id = ? AND report_id = ?");
        $remove->bind_param('ii', $currentUserId, $reportId);
        $remove->execute();

        if ($remove->affected_rows > 0) {
            $decrement = $db->prepare("UPDATE reports SET {$counterColumn} = GREATEST(0, {$counterColumn} - 1) WHERE report_id = ? AND is_deleted = FALSE");
            $decrement->bind_param('i', $reportId);
            $decrement->execute();
        }

        $isActive = false;
    } else {
        $insert = $db->prepare(
            "INSERT INTO {$junctionTable} (user_id, report_id)
             SELECT ?, ?
             FROM reports
             WHERE report_id = ?
               AND is_deleted = FALSE"
        );
        $insert->bind_param('iii', $currentUserId, $reportId, $reportId);
        $insert->execute();

        if ($insert->affected_rows === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'This report is no longer available.']);
            exit;
        }

        $increment = $db->prepare("UPDATE reports SET {$counterColumn} = {$counterColumn} + 1 WHERE report_id = ? AND is_deleted = FALSE");
        $increment->bind_param('i', $reportId);
        $increment->execute();

        $isActive = true;
    }

    $counter = $db->prepare("SELECT {$counterColumn} AS count FROM reports WHERE report_id = ? AND is_deleted = FALSE LIMIT 1");
    $counter->bind_param('i', $reportId);
    $counter->execute();
    $row = $counter->get_result()->fetch_assoc();

    if (!$row) {
        http_response_code(404);
        echo json_encode(['error' => 'This report is no longer available.']);
        exit;
    }

    echo json_encode([
        'count' => (int) $row['count'],
        'active' => $isActive,
    ]);
} catch (Throwable $error) {
    http_response_code(500);
    echo json_encode(['error' => 'Unable to update this report right now.']);
}