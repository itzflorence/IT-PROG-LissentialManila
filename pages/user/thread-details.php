<?php

declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/thread-query.php';

require_login('../auth/login.php');

$threadId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$thread = null;
$reports = [];
$errorMessage = null;

if (!$threadId || $threadId < 1) {
    http_response_code(400);
    $errorMessage = 'A valid thread ID is required.';
} else {
    try {
        $db = thread_db();
        $thread = thread_fetch_one($db, $threadId);
        if ($thread === null) {
            http_response_code(404);
            $errorMessage = 'The requested thread does not exist.';
        } else {
            $reports = thread_fetch_reports($db, $threadId);
        }
    } catch (Throwable $error) {
        http_response_code(500);
        $errorMessage = $error->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= thread_escape($thread['title'] ?? 'Thread Details') ?> | Lissential Manila</title>
    <link rel="stylesheet" href="../../style/user/threads.css">
</head>
<body>
<header class="details-navbar">
    <a href="user-threads.php" class="details-navbar__back"><i class="fa-solid fa-arrow-left"></i> Back to Threads</a>
    <a href="../../index.php"><img src="../../assets/LOGO/logo_normal.png" alt="Lissential Manila logo"></a>
</header>

<main class="thread-details-page">
    <?php if ($errorMessage !== null): ?>
        <section class="thread-state thread-state--error">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <h1>Thread unavailable</h1>
            <p><?= thread_escape($errorMessage) ?></p>
            <a href="user-threads.php">Return to all threads</a>
        </section>
    <?php else: ?>
        <section class="thread-details-hero">
            <div class="thread-details-hero__topline">
                <span class="thread-status thread-status--<?= strtolower(thread_escape((string) $thread['status'])) ?>"><i class="fa-solid fa-circle"></i><?= thread_escape($thread['status']) ?></span>
                <span>Last updated <?= thread_escape(thread_date_label($thread['updated_at'])) ?></span>
            </div>
            <h1><?= thread_escape($thread['title']) ?></h1>
            <p><?= thread_escape($thread['description'] ?: 'No official description has been added to this thread yet.') ?></p>
            <div class="thread-details-hero__meta">
                <span><i class="fa-solid fa-location-dot"></i><?= thread_escape(thread_location_label($thread)) ?></span>
                <span><i class="fa-solid fa-layer-group"></i><?= thread_escape($thread['category_name']) ?></span>
                <span><i class="fa-solid fa-user-shield"></i>Created by <?= thread_escape($thread['creator_name']) ?></span>
            </div>
        </section>

        <section class="thread-summary-grid">
            <article><strong><?= count($reports) ?></strong><span>Linked reports</span></article>
            <article><strong><?= (int) $thread['verified_reports'] ?></strong><span>Verified</span></article>
            <article><strong><?= (int) $thread['unverified_reports'] ?></strong><span>Unverified</span></article>
        </section>

        <section class="linked-reports">
            <div class="linked-reports__heading">
                <div><p class="threads-eyebrow">COMMUNITY REPORTS</p><h2>Reports linked to this thread</h2></div>
            </div>

            <?php if ($reports === []): ?>
                <div class="thread-state thread-state--compact"><i class="fa-regular fa-file-lines"></i><h3>No linked reports yet</h3><p>An official thread can exist before individual reports are linked to it.</p></div>
            <?php else: ?>
                <div class="report-list">
                    <?php foreach ($reports as $report): ?>
                        <article class="linked-report-card">
                            <div class="linked-report-card__topline">
                                <span class="report-status report-status--<?= strtolower(thread_escape((string) $report['status'])) ?>"><?= thread_escape($report['status']) ?></span>
                                <span><?= thread_escape(thread_date_label($report['created_at'])) ?></span>
                            </div>
                            <h3><?= thread_escape($report['title']) ?></h3>
                            <p><?= thread_escape($report['description']) ?></p>
                            <div class="linked-report-card__meta">
                                <span><i class="fa-solid fa-user"></i><?= thread_escape($report['username'] ?: $report['reporter_name']) ?></span>
                                <span><i class="fa-solid fa-location-dot"></i><?= thread_escape($report['district'] . ', ' . $report['city']) ?></span>
                                <span><i class="fa-solid fa-square-caret-up"></i><?= (int) $report['upvote_count'] ?></span>
                                <span><i class="fa-solid fa-comment"></i><?= (int) $report['comment_count'] ?></span>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</main>
</body>
</html>

        <a href="../../index.php"><img src="../../assets/LOGO/logo_normal.png" alt="Lissential Manila logo"></a>
