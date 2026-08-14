<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/thread-query.php';
require_once __DIR__ . '/../../includes/report-feed.php';
require_once __DIR__ . '/../../includes/official-query.php';

function escape_html(?string $value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

require_role(['Official', 'Admin'], '../auth/login.php', '../../index.php');

$isAuthenticated = is_authenticated();
$username = $_SESSION['username'] ?? null;
$safeUsername = escape_html((string) ($username ?? ''));
$loginUrl = '../auth/login.php';
$logoutUrl = '../auth/logout.php';

$currentUserId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$currentUserId = $currentUserId === false ? null : $currentUserId;

$assignedLocation = null;
$pendingReports = [];
$mediaByReport = [];
$threadsInArea = [];
$loadError = null;

try {
    $db = thread_db();

    if ($currentUserId !== null) {
        $assignedLocation = official_fetch_assigned_location($db, $currentUserId);
    }

    if ($assignedLocation !== null) {
        $locationId = (int) $assignedLocation['location_id'];
        $queue = official_fetch_queue($db, 'Pending', '', $locationId);
        $pendingReports = $queue['reports'];
        $mediaByReport = $queue['mediaByReport'];
        $threadsInArea = official_fetch_threads_by_location($db, $locationId);
    }
} catch (Throwable $error) {
    $loadError = 'Unable to load your assigned area right now.';
}

$activeThreadCount = 0;
foreach ($threadsInArea as $threadRow) {
    if (($threadRow['status'] ?? '') === 'Active') {
        $activeThreadCount++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assigned Area - LissentialManila</title>

    <link rel="stylesheet" href="../../style/shared/global.css">
    <link rel="stylesheet" href="../../style/shared/navbar.css">
    <link rel="stylesheet" href="../../style/shared/post.css">
    <link rel="stylesheet" href="../../style/user/home.css">
    <link rel="stylesheet" href="../../style/user/threads.css">
    <link rel="stylesheet" href="../../style/official/official.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
          integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
          crossorigin="anonymous" referrerpolicy="no-referrer" />

    <script src="../shared-js/media-carousel.js" defer></script>
</head>

<body>
<nav>
    <header class="navbar">
        <div class="navbar-logo">
            <a href="official-home.php">
                <img src="../../assets/LOGO/logo_normal.png" alt="LissentialManila Logo">
            </a>
        </div>

        <div class="searchbar">
            <input type="search" placeholder="Search for a report...">
            <i class="fa-solid fa-magnifying-glass"></i>
        </div>

        <div class="auth-state-pill auth-state-pill--user">
            Logged in as <?php echo $safeUsername; ?> (<?php echo escape_html((string) current_role()); ?>)
        </div>

        <div class="icon-button-wrapper">
            <button type="button" class="icon-button notif-bell-btn" id="notifBellBtn" data-notif-api="../../includes/notifications-api.php" aria-haspopup="true" aria-expanded="false" aria-label="Notifications">
                <i class="fa-solid fa-bell"></i>
            </button>
            <div class="notification-panel" id="notifPanel" hidden>
                <div class="notification-panel-header">Nearby Alerts</div>
                <div class="notification-panel-body" id="notifPanelBody"></div>
            </div>
            <button type="button" class="icon-button user-menu-btn" id="userMenuBtn" aria-haspopup="true" aria-expanded="false" aria-label="Account menu">
                <i class="fa-solid fa-user"></i>
            </button>
            <div class="user-menu-panel" id="userMenuPanel" hidden>
                <div class="user-menu-info">
                    <span class="user-menu-name"><?php echo htmlspecialchars((string) ($_SESSION['full_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?: $safeUsername; ?></span>
                    <span class="user-menu-username">@<?php echo $safeUsername; ?></span>
                </div>
                <a class="user-menu-logout" href="<?php echo $logoutUrl; ?>">
                    <i class="fa-solid fa-right-from-bracket"></i> Log out
                </a>
            </div>
        </div>
    </header>

    <aside class="sidebar">
        <div class="create-report">
            <button type="button" onclick="window.location.href='official-create-thread.php'">CREATE THREAD</button>
        </div>

        <div class="sidebar-options-wrapper">
            <span class="sidebar-title">OFFICIAL ACTIONS</span>
            <div class="sidebar-options">
                <a href="official-home.php">Review Queue</a>
                <a href="official-assigned-area.php" style="font-weight: bold;">Assigned Area</a>
                <a href="official-create-thread.php">Create Thread</a>
            </div>
            <hr>
        </div>

        <div class="sidebar-options-wrapper">
            <span class="sidebar-title">INCIDENT THREADS</span>
            <div class="sidebar-options">
                <a href="../user/user-threads.php">All</a>
                <a href="../user/user-active-threads.php">Active</a>
                <a href="../user/user-resolved-threads.php">Resolved</a>
                <a href="official-archived-threads.php">Archived</a>
            </div>
            <hr>
        </div>

        <div class="sidebar-options-wrapper">
            <span class="sidebar-title">GENERAL</span>
            <div class="sidebar-options">
                <a href="../../index.php">Back to Feed</a>
                <a href="/IT-PROG-LISSENTIALMANILA-MAIN/pages/user/user-profile.php">Account Profile</a>
            </div>
        </div>

        <span class="copyright-footer">IT-PROG © 2026. All rights reserved.</span>
    </aside>
</nav>

<aside class="threads-wrapper"></aside>

<div class="main-wrapper">
    <main>
        <div class="official-container">
            <div class="official-header">
                <div>
                    <h1>Assigned Area</h1>
                    <p>Your designated monitoring area for MMDA/LGU oversight. Assigned by an administrator.</p>
                </div>
            </div>

            <?php if ($loadError !== null): ?>
                <section class="post" style="padding: 16px;">
                    <h2 style="margin-bottom: 8px;">Unable to load this page</h2>
                    <p style="margin-bottom: 0;">Please make sure MySQL is running and the database has been imported.</p>
                </section>
            <?php elseif ($assignedLocation === null): ?>
                <div class="queue-empty-state">
                    <i class="fa-solid fa-map-location-dot" style="font-size: 2rem; margin-bottom: 8px; display: block;"></i>
                    No area has been assigned to you yet. Ask an administrator to set your Assigned Area in Manage Accounts.
                </div>
            <?php else: ?>
                <div class="reporter-summary">
                    <div class="reporter-summary-item">
                        <span>City</span>
                        <span><?php echo escape_html((string) $assignedLocation['city']); ?></span>
                    </div>
                    <div class="reporter-summary-item">
                        <span>District / Barangay</span>
                        <span><?php echo escape_html((string) $assignedLocation['district']); ?></span>
                    </div>
                    <?php if (!empty($assignedLocation['landmark'])): ?>
                        <div class="reporter-summary-item">
                            <span>Landmark</span>
                            <span><?php echo escape_html((string) $assignedLocation['landmark']); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="reporter-summary-item">
                        <span>Pending Reports</span>
                        <span><?php echo count($pendingReports); ?></span>
                    </div>
                    <div class="reporter-summary-item">
                        <span>Active Threads</span>
                        <span><?php echo $activeThreadCount; ?> of <?php echo count($threadsInArea); ?></span>
                    </div>
                </div>

                <h2 style="font-size: var(--font-large); margin-top: var(--space-medium);">Pending Reports in This Area</h2>
                <?php if ($pendingReports === []): ?>
                    <div class="queue-empty-state">
                        <i class="fa-solid fa-circle-check" style="font-size: 1.5rem; margin-bottom: 8px; display: block;"></i>
                        No pending reports in your area right now.
                    </div>
                <?php else: ?>
                    <?php foreach ($pendingReports as $report): ?>
                        <?php
                        $reportId = (int) ($report['report_id'] ?? 0);
                        $reporterName = trim((string) ($report['username'] ?? ''));
                        if ($reporterName === '') {
                            $reporterName = 'Anonymous';
                        }
                        $locationParts = [];
                        if (!empty($report['district'])) { $locationParts[] = (string) $report['district']; }
                        if (!empty($report['city'])) { $locationParts[] = (string) $report['city']; }
                        $locationLabel = $locationParts !== [] ? implode(', ', $locationParts) : 'Unknown location';
                        $timeLabel = relative_time_label((string) ($report['created_at'] ?? ''));
                        $mediaItems = $mediaByReport[$reportId] ?? [];
                        ?>
                        <section class="post">
                            <div class="profile-details">
                                <div class="post-pfp"><img src="../../assets/user_images/user1.jpg" alt=""></div>
                                <span class="username"><?php echo escape_html($reporterName); ?></span>
                                <span>•</span>
                                <span class="hours-ago"><?php echo escape_html($timeLabel); ?></span>
                                <span class="badge-needs-review"><i class="fa-solid fa-hourglass-half"></i> Needs Review</span>
                            </div>
                            <div class="post-details">
                                <div class="post-details-box">
                                    <i class="fa-solid fa-location-dot" style="color: var(--colorRed);"></i>
                                    <span><?php echo escape_html($locationLabel); ?></span>
                                </div>
                                <div class="post-details-box post-details-box-category">
                                    <i class="fa-solid fa-layer-group" style="color: var(--colorYellow);"></i>
                                    <span class="post-category-badge"><?php echo escape_html((string) ($report['category_name'] ?? 'Uncategorized')); ?></span>
                                </div>
                            </div>
                            <div class="post-title-and-description">
                                <h2><span class="post-title"><?php echo escape_html((string) ($report['title'] ?? 'Untitled report')); ?></span></h2>
                                <span class="post-description"><?php echo escape_html((string) ($report['description'] ?? '')); ?></span>
                            </div>
                            <div class="post-buttons">
                                <div class="post-buttons-left"></div>
                                <div class="post-buttons-right">
                                    <button type="button" class="btn-review-report" onclick="window.location.href='official-edit-report.php?id=<?php echo $reportId; ?>'">
                                        <i class="fa-solid fa-pen-to-square"></i> Review Report
                                    </button>
                                </div>
                            </div>
                        </section>
                    <?php endforeach; ?>
                <?php endif; ?>

                <h2 style="font-size: var(--font-large); margin-top: var(--space-medium);">Threads in This Area</h2>
                <?php if ($threadsInArea === []): ?>
                    <div class="queue-empty-state">
                        <i class="fa-regular fa-folder-open" style="font-size: 1.5rem; margin-bottom: 8px; display: block;"></i>
                        No incident threads have been created for this area yet.
                    </div>
                <?php else: ?>
                    <section class="thread-grid" aria-label="Threads in your assigned area" style="width: 100%;">
                        <?php foreach ($threadsInArea as $thread): ?>
                            <?php
                            $threadCardBasePath = '../user/';
                            $threadCardEditUrl = 'official-edit-thread.php?id=' . (int) $thread['thread_id'];
                            require __DIR__ . '/../../includes/thread-card.php';
                            ?>
                        <?php endforeach; ?>
                    </section>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>
</div>
<script src="../shared-js/notifications.js" defer></script>
<script src="../shared-js/navbar-user-menu.js" defer></script>
</body>

</html>