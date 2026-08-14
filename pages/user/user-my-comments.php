<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/thread-query.php';
require_once __DIR__ . '/../../includes/report-feed.php';
require_once __DIR__ . '/../../includes/user-activity-query.php';
require_once __DIR__ . '/../../includes/user-activity-layout.php';

require_login('../auth/login.php');

$currentUserId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$currentUserId = $currentUserId === false ? null : $currentUserId;
$categories = [];
$comments = [];
$errorMessage = null;

try {
    $db = thread_db();
    $categories = fetch_categories($db);
    if ($currentUserId !== null) {
        $comments = activity_fetch_comments($db, $currentUserId);
    }
} catch (Throwable $error) {
    $errorMessage = 'Unable to load your comments right now.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Comments - LissentialManila</title>
    <link rel="stylesheet" href="../../style/user/activity.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer">
</head>
<body>
<?php render_activity_navigation($categories, 'comments'); ?>
<div class="activity-main"><main>
    <header class="activity-heading"><h1>My Comments</h1><p>Comments you have posted on active reports.</p></header>
    <?php if ($errorMessage !== null): ?>
        <section class="activity-panel"><h2>Comments unavailable</h2><p><?= activity_layout_escape($errorMessage) ?></p></section>
    <?php elseif ($comments === []): ?>
        <section class="activity-panel"><h2>No comments yet</h2><p>Join a report discussion to see your comments here.</p></section>
    <?php else: ?>
        <?php foreach ($comments as $comment): ?>
            <?php $reportId = (int) $comment['report_id']; ?>
            <a class="comment-activity" href="user-report-details.php?id=<?= $reportId ?>#comments">
                <strong><?= activity_layout_escape((string) $comment['report_title']) ?></strong>
                <div class="comment-activity__meta"><span><?= activity_layout_escape(activity_location_label($comment)) ?></span><span><?= activity_layout_escape(relative_time_label((string) $comment['created_at'])) ?></span><span>Status: <?= activity_layout_escape((string) $comment['status']) ?></span></div>
                <p><?= activity_layout_escape((string) $comment['comment_text']) ?></p>
            </a>
        <?php endforeach; ?>
    <?php endif; ?>
</main></div>
<script src="../shared-js/notifications.js" defer></script><script src="../shared-js/navbar-user-menu.js" defer></script>
</body>
</html>