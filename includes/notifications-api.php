<?php

declare(strict_types=1);

/**
 * JSON endpoint for the navbar notification bell: returns recent alerts
 * for threads created near the current user's home/saved locations.
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/thread-query.php';
require_once __DIR__ . '/notifications-query.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_authenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$currentUserId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$currentUserId = $currentUserId === false ? null : $currentUserId;

if ($currentUserId === null) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

try {
    $db = thread_db();

    $notifications = notifications_fetch_recent($db, $currentUserId);

    echo json_encode([
        'notifications' => $notifications,
    ]);
} catch (Throwable $error) {
    http_response_code(500);
    echo json_encode(['error' => 'Unable to load notifications right now.']);
}
