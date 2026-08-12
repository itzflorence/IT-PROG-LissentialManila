<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/thread-query.php';
require_once __DIR__ . '/../../includes/report-feed.php';
require_once __DIR__ . '/../../includes/official-query.php';

// HTML-escape helper for safe output inside templates
function escape_html(?string $value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Only Officials and Admins may access the review queue
require_role(['Official', 'Admin'], '../auth/login.php', '../../index.php');

$isAuthenticated = is_authenticated();
$username = $_SESSION['username'] ?? null;
$safeUsername = escape_html((string) ($username ?? ''));

$loginUrl = '../auth/login.php';
$logoutUrl = '../auth/logout.php';

// Status filter for the queue toolbar
$allowedStatuses = OFFICIAL_REPORT_STATUSES;
$selectedStatus = trim((string) ($_GET['status'] ?? ''));
if (!in_array($selectedStatus, $allowedStatuses, true)) {
    $selectedStatus = '';
}
$searchTerm = trim((string) ($_GET['q'] ?? ''));

// Flash messaging after a save/verification action redirects here
$flashSuccess = ($_GET['updated'] ?? '') === '1';
$flashError = trim((string) ($_GET['error'] ?? ''));

$reports = [];
$mediaByReport = [];
$loadError = null;

try {
    $db = thread_db();
    $queue = official_fetch_queue($db, $selectedStatus, $searchTerm);
    $reports = $queue['reports'];
    $mediaByReport = $queue['mediaByReport'];
} catch (Throwable $error) {
    $loadError = 'Unable to load the review queue right now.';
}

$pendingCount = 0;
foreach ($reports as $reportRow) {
    if (($reportRow['status'] ?? '') === 'Pending') {
        $pendingCount++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Queue - LissentialManila</title>

    <link rel="stylesheet" href="../../style/shared/global.css">
    <link rel="stylesheet" href="../../style/shared/navbar.css">
    <link rel="stylesheet" href="../../style/shared/post.css">
    <link rel="stylesheet" href="../../style/user/home.css">
    <link rel="stylesheet" href="../../style/official/official.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
          integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
          crossorigin="anonymous" referrerpolicy="no-referrer" />

    <script src="../shared-js/media-carousel.js" defer></script>
</head>

<body>
<!-- NAVIGATION BAR & SIDEBAR -->
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
            <button type="button" class="icon-button">
                <i class="fa-solid fa-bell"></i>
            </button>

            <button type="button" class="icon-button" title="Log out" onclick="window.location.href='<?php echo $logoutUrl; ?>'">
                <i class="fa-solid fa-user"></i>
            </button>
        </div>
    </header>

    <aside class="sidebar">
        <div class="create-report">
            <button type="button" onclick="window.location.href='official-create-thread.php'">CREATE THREAD</button>
        </div>

        <div class="sidebar-options-wrapper">
            <span class="sidebar-title">OFFICIAL ACTIONS</span>
            <div class="sidebar-options">
                <a href="official-home.php" style="font-weight: bold;">Review Queue</a>
                <a href="official-assigned-area.php">Assigned Area</a>
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

<!-- THREADS / RIGHT PANEL -->
<aside class="threads-wrapper"></aside>

<!-- REVIEW QUEUE -->
<div class="main-wrapper">
    <main>
        <div class="official-container">
            <div class="official-header">
                <div>
                    <h1>Report Review Queue</h1>
                    <p>Verify, resolve, or reject incident reports submitted by users. <?php echo $pendingCount; ?> pending report<?php echo $pendingCount === 1 ? '' : 's'; ?> awaiting action.</p>
                </div>
            </div>

            <?php if ($flashSuccess): ?>
                <div class="flash-banner flash-banner--success">
                    <i class="fa-solid fa-circle-check"></i> Report updated successfully.
                </div>
            <?php endif; ?>

            <?php if ($flashError !== ''): ?>
                <div class="flash-banner flash-banner--error">
                    <i class="fa-solid fa-triangle-exclamation"></i> <?php echo escape_html($flashError); ?>
                </div>
            <?php endif; ?>

            <form method="get" class="filter queue-toolbar">
                <div class="filter-group">
                    <label for="status-filter">Status:</label>
                    <select id="status-filter" name="status" onchange="this.form.submit()">
                        <option value="" <?php echo $selectedStatus === '' ? 'selected' : ''; ?>>All</option>
                        <?php foreach ($allowedStatuses as $statusOption): ?>
                            <option value="<?php echo escape_html($statusOption); ?>" <?php echo $selectedStatus === $statusOption ? 'selected' : ''; ?>>
                                <?php echo escape_html($statusOption); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="q">Search:</label>
                    <input type="text" id="q" name="q" value="<?php echo escape_html($searchTerm); ?>" placeholder="Title, location, or reporter...">
                </div>

                <button type="submit" style="padding: 8px 16px; border-radius: 8px;">Apply</button>
            </form>

            <?php if ($loadError !== null): ?>
                <section class="post" style="padding: 16px;">
                    <h2 style="margin-bottom: 8px;">Unable to load reports</h2>
                    <p style="margin-bottom: 0;">Please make sure MySQL is running and the database has been imported.</p>
                </section>
            <?php elseif ($reports === []): ?>
                <div class="queue-empty-state">
                    <i class="fa-solid fa-clipboard-check" style="font-size: 2rem; margin-bottom: 8px; display: block;"></i>
                    No reports match this filter.
                </div>
            <?php else: ?>
                <?php foreach ($reports as $report): ?>
                    <?php
                    $reportId = (int) ($report['report_id'] ?? 0);
                    $reporterName = trim((string) ($report['username'] ?? ''));
                    if ($reporterName === '') {
                        $reporterName = trim(((string) ($report['first_name'] ?? '')) . ' ' . ((string) ($report['last_name'] ?? '')));
                    }
                    if ($reporterName === '') {
                        $reporterName = 'Anonymous';
                    }

                    $locationParts = [];
                    if (!empty($report['district'])) {
                        $locationParts[] = (string) $report['district'];
                    }
                    if (!empty($report['city'])) {
                        $locationParts[] = (string) $report['city'];
                    }
                    $locationLabel = $locationParts !== [] ? implode(', ', $locationParts) : 'Unknown location';

                    $status = (string) ($report['status'] ?? 'Pending');
                    $statusUpper = strtoupper($status);
                    $statusClassSuffix = strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', $status));
                    if ($statusClassSuffix === '') {
                        $statusClassSuffix = 'pending';
                    }
                    $timeLabel = relative_time_label((string) ($report['created_at'] ?? ''));
                    $dateTimeLabels = report_date_time_labels((string) ($report['created_at'] ?? ''));
                    $mediaItems = $mediaByReport[$reportId] ?? [];
                    ?>

                    <section class="post">
                        <div class="profile-details">
                            <div class="post-pfp"><img src="../../assets/user_images/user1.jpg" alt=""></div>
                            <span class="username"><?php echo escape_html($reporterName); ?></span>
                            <span>•</span>
                            <span class="hours-ago"><?php echo escape_html($timeLabel); ?></span>
                            <?php if ($status === 'Pending'): ?>
                                <span class="badge-needs-review"><i class="fa-solid fa-hourglass-half"></i> Needs Review</span>
                            <?php endif; ?>
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

                            <div class="post-details-box">
                                <i class="fa-solid fa-clock" style="color: var(--colorGreen);"></i>
                                <span><?php echo escape_html($dateTimeLabels['date']); ?></span> | <span><?php echo escape_html($dateTimeLabels['time']); ?></span>
                            </div>
                        </div>

                        <div class="post-title-and-description">
                            <h2><span class="post-title"><?php echo escape_html((string) ($report['title'] ?? 'Untitled report')); ?></span></h2>
                            <span class="post-description"><?php echo escape_html((string) ($report['description'] ?? '')); ?></span>
                        </div>

                        <div class="post-media-carousel">
                            <div class="carousel-container">
                                <?php if ($mediaItems === []): ?>
                                    <div class="carousel-slide">
                                        <img src="../../assets/report_media/media1-1.jfif" alt="No media attached">
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($mediaItems as $media): ?>
                                        <div class="carousel-slide">
                                            <?php if (($media['file_type'] ?? 'photo') === 'video'): ?>
                                                <video src="../../<?php echo escape_html((string) $media['file_url']); ?>" controls muted playsinline></video>
                                            <?php else: ?>
                                                <img src="../../<?php echo escape_html((string) $media['file_url']); ?>" alt="Report attachment">
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <?php if (count($mediaItems) > 1): ?>
                                <button class="carousel-btn prev" aria-label="Previous slide" onclick="moveCarousel(this, -1)">
                                    <i class="fa-solid fa-chevron-left"></i>
                                </button>
                                <button class="carousel-btn next" aria-label="Next slide" onclick="moveCarousel(this, 1)">
                                    <i class="fa-solid fa-chevron-right"></i>
                                </button>
                            <?php endif; ?>
                        </div>

                        <div class="post-buttons">
                            <div class="post-buttons-left">
                                <button type="button" class="post-upvote">
                                    <i class="fa-solid fa-square-caret-up"></i>
                                    <span><?php echo (int) ($report['upvote_count'] ?? 0); ?></span>
                                </button>
                                <button type="button" class="post-comment" onclick="window.location.href='official-edit-report.php?id=<?php echo $reportId; ?>#comments'">
                                    <i class="fa-solid fa-comment-dots"></i>
                                    <span><?php echo (int) ($report['comment_count'] ?? 0); ?></span>
                                </button>
                            </div>

                            <div class="post-buttons-right">
                                <button class="status status-pill status-<?php echo escape_html($statusClassSuffix); ?>">
                                    Status: <?php echo escape_html($statusUpper); ?>
                                </button>
                                <button type="button" class="btn-review-report" onclick="window.location.href='official-edit-report.php?id=<?php echo $reportId; ?>'">
                                    <i class="fa-solid fa-pen-to-square"></i> Review Report
                                </button>
                            </div>
                        </div>
                    </section>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>
</body>

</html>