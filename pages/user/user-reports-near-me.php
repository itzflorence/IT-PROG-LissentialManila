<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/thread-query.php';
require_once __DIR__ . '/../../includes/report-feed.php';
require_once __DIR__ . '/../../includes/report-card.php';
require_once __DIR__ . '/../../includes/user-activity-query.php';
require_once __DIR__ . '/../../includes/user-activity-layout.php';

require_login('../auth/login.php');

$currentUserId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$currentUserId = $currentUserId === false ? null : $currentUserId;
$categories = [];
$homeLocation = null;
$reports = [];
$mediaByReport = [];
$errorMessage = null;

try {
    $db = thread_db();
    $categories = fetch_categories($db);
    if ($currentUserId !== null) {
        $homeLocation = activity_fetch_home_location($db, $currentUserId);
        if ($homeLocation !== null) {
            $reportData = fetch_reports_and_media_by_location_ids($db, [(int) $homeLocation['location_id']], $currentUserId);
            $reports = $reportData['reports'];
            $mediaByReport = $reportData['mediaByReport'];
        }
    }
} catch (Throwable $error) {
    $errorMessage = 'Unable to load reports near you right now.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports Near Me - LissentialManila</title>
    <link rel="stylesheet" href="../../style/user/activity.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <script src="../shared-js/media-carousel.js" defer></script>
    <script src="../shared-js/report-actions.js" defer></script>
</head>
<body>
<?php render_activity_navigation($categories, 'nearby'); ?>
<div class="activity-main"><main>
    <header class="activity-heading">
        <h1>Reports Near Me</h1>
        <p><?= $homeLocation === null ? 'Set your home location in your profile to see nearby reports.' : 'Showing reports in ' . activity_layout_escape(activity_location_label($homeLocation)) . '.' ?></p>
    </header>
    <?php if ($errorMessage !== null): ?>
        <section class="activity-panel"><h2>Reports unavailable</h2><p><?= activity_layout_escape($errorMessage) ?></p></section>
    <?php elseif ($homeLocation === null): ?>
        <section class="activity-panel"><h2>No home location selected</h2><p><a href="user-profile.php">Choose a home location in your account profile.</a></p></section>
    <?php elseif ($reports === []): ?>
        <section class="activity-panel"><h2>No reports nearby</h2><p>There are no current reports for this location.</p></section>
    <?php else: ?>
        <?php foreach ($reports as $report): ?>
            <?php $reportId = (int) $report['report_id']; ?>
            <?php render_report_card($report, $mediaByReport[$reportId] ?? [], 'user-report-details.php?id=' . $reportId, 'report-action.php', '../../'); ?>
        <?php endforeach; ?>
    <?php endif; ?>
</main></div>
<script src="../shared-js/notifications.js" defer></script><script src="../shared-js/navbar-user-menu.js" defer></script>
</body>
</html>
