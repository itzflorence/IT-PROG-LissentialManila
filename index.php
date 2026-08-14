<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/thread-query.php';
require_once __DIR__ . '/includes/report-feed.php';
require_once __DIR__ . '/includes/advisory-query.php';

function escape_html(?string $value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$isAuthenticated = is_authenticated();

$currentUserId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$currentUserId = $currentUserId === false ? null : $currentUserId;

$username = $_SESSION['username'] ?? null;
$safeUsername = escape_html((string) ($username ?? ''));

$loginUrl = 'pages/auth/login.php';
$logoutUrl = 'pages/auth/logout.php';
$registerUrl = 'pages/auth/register.php';
$createReportUrl = $isAuthenticated ? 'pages/user/user-create-report.php' : $registerUrl;
$myReportsUrl = $isAuthenticated ? 'pages/user/user-my-reports.php' : $loginUrl;
$reportsNearMeUrl = $isAuthenticated ? 'pages/user/user-reports-near-me.php' : $loginUrl;
$savedLocationsUrl = $isAuthenticated ? 'pages/user/user-saved-locations.php' : $loginUrl;
$myCommentsUrl = $isAuthenticated ? 'pages/user/user-my-comments.php' : $loginUrl;
$activeThreadsUrl = $isAuthenticated ? 'pages/user/user-active-threads.php' : $loginUrl;
$resolvedThreadsUrl = $isAuthenticated ? 'pages/user/user-resolved-threads.php' : $loginUrl;
$archivedThreadsUrl = $isAuthenticated ? 'pages/user/user-threads.php?status=Archived' : $loginUrl;

$allowedStatuses = ['Pending', 'Verified', 'Resolved', 'Rejected'];
$selectedStatus = trim((string) ($_GET['status'] ?? ''));
if (!in_array($selectedStatus, $allowedStatuses, true)) {
    $selectedStatus = '';
}

$selectedCategoryId = filter_input(
    INPUT_GET,
    'category',
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);
$selectedCategoryId = $selectedCategoryId === false ? null : $selectedCategoryId;

$categories = [];
$reports = [];
$mediaByReport = [];
$activeThreads = [];
$advisories = [];
$reportLoadError = null;

try {
    $db = thread_db();
    $categories = fetch_categories($db);
    $reportData = fetch_reports_and_media($db, $selectedStatus, $selectedCategoryId, $currentUserId);
    $reports = $reportData['reports'];
    $mediaByReport = $reportData['mediaByReport'];
    $activeThreads = thread_fetch_all($db, 'Active', '');
    $advisories = fetch_active_advisories($db);
} catch (Throwable $error) {
    $reportLoadError = $error->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LissentialManila</title>
    <link rel="stylesheet" href="style/shared/global.css">
    <link rel="stylesheet" href="style/shared/navbar.css">
    <link rel="stylesheet" href="style/user/home.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
        crossorigin="anonymous" referrerpolicy="no-referrer"/>
    <link rel="stylesheet" href="style/shared/post.css">

    <script src="pages/shared-js/media-carousel.js" defer></script>
    <script>
        const isAuthenticated = <?php echo $isAuthenticated ? 'true' : 'false'; ?>;
        const loginUrl = <?php echo json_encode($loginUrl); ?>;

        document.addEventListener('DOMContentLoaded', () => {
            if (!isAuthenticated) {
                document.querySelectorAll('.post-link, .post-upvote, .post-comment, .post-resolved').forEach(element => {
                    element.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        window.location.href = loginUrl;
                    });
                });
            }
        });
    </script>
</head>

<body>
    <nav>
        <header class="navbar">
            <div class="navbar-logo">
                <a href="index.php">
                    <img src="assets/LOGO/logo_normal.png" alt="LissentialManila Logo">
                </a>
            </div>

            <div class="searchbar">
                <input type="search" placeholder="Search for a report...">
                <i class="fa-solid fa-magnifying-glass"></i>
            </div>

            <?php if ($isAuthenticated): ?>
            <div class="auth-state-pill auth-state-pill--user">
                Logged in as <?php echo $safeUsername; ?>
            </div>
            <?php endif; ?>

            <?php if ($isAuthenticated): ?>
            <div class="icon-button-wrapper">
                <button type="button" class="icon-button notif-bell-btn" id="notifBellBtn" data-notif-api="includes/notifications-api.php" aria-haspopup="true" aria-expanded="false" aria-label="Notifications">
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
            <?php else: ?>
            <div class="login-button">
                <button type="button" onclick="window.location.href='<?php echo $loginUrl; ?>'">LOG IN</button>
            </div>
            <?php endif; ?>
        </header>

        <aside class="sidebar">
            <?php if (!$isAuthenticated): ?>
            <div class="sidebar-options-wrapper">
                <span class="sidebar-title sidebar-intro">Join the Anti-Kamote Gang and create an account for LissentialManila!</span>
            </div>
            <?php endif; ?>

            <div class="create-report">
                <button type="button" onclick="window.location.href='<?php echo $createReportUrl; ?>'">
                    <?php echo $isAuthenticated ? 'CREATE REPORT' : 'CREATE ACCOUNT'; ?>
                </button>
            </div>

            <div class="sidebar-options-wrapper">
                <span class="sidebar-title">FEED</span>
                <div class="sidebar-options">
                    <a href="index.php">All Reports</a>
                    <a href="#advisories-section">Official Advisories</a>
                </div>
                <hr>
            </div>

            <?php if ($isAuthenticated): ?>
            <div class="sidebar-options-wrapper">
                <span class="sidebar-title">MY ACTIVITY</span>
                <div class="sidebar-options">
                    <a href="<?php echo $myReportsUrl; ?>">My Reports</a>
                    <a href="<?php echo $reportsNearMeUrl; ?>">Reports Near Me</a>
                    <a href="<?php echo $savedLocationsUrl; ?>">Saved Locations</a>
                    <a href="<?php echo $myCommentsUrl; ?>">My Comments</a>
                    <a href="pages/user/user-profile.php">Account Profile</a>
                </div>
                <hr>
            </div>
            <?php endif; ?>

            <div class="sidebar-options-wrapper">
                <span class="sidebar-title">CATEGORIES</span>
                <div class="sidebar-options">
                    <a
                        href="<?php echo escape_html(build_filter_url('index.php', $selectedStatus !== '' ? $selectedStatus : null, null)); ?>"
                        class="sidebar-filter-link<?php echo $selectedCategoryId === null ? ' is-active' : ''; ?>"
                    >
                        All Categories
                    </a>
                    <?php foreach ($categories as $category): ?>
                        <?php $categoryId = (int) ($category['category_id'] ?? 0); ?>
                        <a
                            href="<?php echo escape_html(build_filter_url('index.php', $selectedStatus !== '' ? $selectedStatus : null, $categoryId)); ?>"
                            class="sidebar-filter-link<?php echo $selectedCategoryId === $categoryId ? ' is-active' : ''; ?>"
                        >
                            <?php echo escape_html((string) ($category['category_name'] ?? '')); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <hr>
            </div>

            <div class="sidebar-options-wrapper">
                <span class="sidebar-title">THREADS</span>

                <div class="sidebar-options">
                    <a href="<?php echo $activeThreadsUrl; ?>">Active</a>
                    <a href="<?php echo $resolvedThreadsUrl; ?>">Resolved</a>
                    <a href="<?php echo $archivedThreadsUrl; ?>">Archived</a>
                </div>
            </div>

            <span class="copyright-footer">IT-PROG © 2026. All rights reserved.</span>
        </aside>
    </nav>

    <aside class="threads-wrapper">
        <div class="threads-wrapper__header">
            <h2>Active Threads</h2>
            <span class="threads-wrapper__count"><?php echo count($activeThreads); ?></span>
        </div>
        <div class="threads-wrapper__list">
            <?php if ($activeThreads === []): ?>
                <p class="threads-wrapper__empty">No active threads right now.</p>
            <?php else: ?>
                <?php foreach ($activeThreads as $thread): ?>
                    <?php
                    $threadId = (int) ($thread['thread_id'] ?? 0);
                    $threadHref = $isAuthenticated ? 'pages/user/thread-details.php?id=' . $threadId : $loginUrl;
                    $threadReportCount = (int) ($thread['actual_report_count'] ?? $thread['total_reports'] ?? 0);
                    ?>
                    <a class="threads-summary-item" href="<?php echo escape_html($threadHref); ?>"<?php echo !$isAuthenticated ? ' data-login-required="true"' : ''; ?>>
                        <div class="threads-summary-item__top">
                            <span class="threads-summary-item__status"><i class="fa-solid fa-circle"></i> Active</span>
                            <span class="threads-summary-item__updated"><?php echo escape_html(thread_date_label($thread['updated_at'] ?? null)); ?></span>
                        </div>
                        <strong class="threads-summary-item__title"><?php echo escape_html((string) ($thread['title'] ?? 'Untitled incident')); ?></strong>
                        <span class="threads-summary-item__meta">
                            <i class="fa-solid fa-location-dot"></i> <?php echo escape_html(thread_location_label($thread)); ?>
                        </span>
                        <span class="threads-summary-item__meta">
                            <i class="fa-solid fa-layer-group"></i> <?php echo escape_html((string) ($thread['category_name'] ?? 'Uncategorized')); ?> &middot; <?php echo $threadReportCount; ?> report<?php echo $threadReportCount === 1 ? '' : 's'; ?>
                        </span>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <a class="threads-wrapper__viewall" href="<?php echo $activeThreadsUrl; ?>">
            View all active threads <i class="fa-solid fa-arrow-right"></i>
        </a>
    </aside>

    <div class="main-wrapper">
        <main>
            <?php if ($advisories !== []): ?>
                <section id="advisories-section" class="advisories-panel">
                    <div class="advisories-panel__header">
                        <i class="fa-solid fa-bullhorn"></i>
                        <h2>Official Advisories</h2>
                    </div>
                    <?php foreach ($advisories as $advisory): ?>
                        <?php
                        $advisoryAuthor = trim((string) ($advisory['username'] ?? ''));
                        if ($advisoryAuthor === '') {
                            $advisoryAuthor = trim((string) ($advisory['first_name'] ?? '') . ' ' . (string) ($advisory['last_name'] ?? ''));
                        }
                        ?>
                        <article class="advisory-card">
                            <div class="advisory-card__meta">
                                <span><i class="fa-solid fa-location-dot"></i> <?php echo escape_html(advisory_location_label($advisory)); ?></span>
                                <span><?php echo escape_html(relative_time_label((string) ($advisory['created_at'] ?? ''))); ?></span>
                            </div>
                            <h3><?php echo escape_html((string) ($advisory['title'] ?? '')); ?></h3>
                            <p><?php echo escape_html((string) ($advisory['content'] ?? '')); ?></p>
                            <span class="advisory-card__author">&mdash; <?php echo escape_html($advisoryAuthor !== '' ? $advisoryAuthor : 'Official'); ?></span>
                        </article>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>

            <form method="get" class="filter">
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
                    <label for="category-filter">Category:</label>
                    <select id="category-filter" name="category" onchange="this.form.submit()">
                        <option value="" <?php echo $selectedCategoryId === null ? 'selected' : ''; ?>>All</option>
                        <?php foreach ($categories as $category): ?>
                            <?php $categoryId = (int) ($category['category_id'] ?? 0); ?>
                            <option value="<?php echo $categoryId; ?>" <?php echo $selectedCategoryId === $categoryId ? 'selected' : ''; ?>>
                                <?php echo escape_html((string) ($category['category_name'] ?? '')); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>

            <?php if ($reportLoadError !== null): ?>
                <section class="post" style="padding: 16px;">
                    <h2 style="margin-bottom: 8px;">Unable to load reports</h2>
                    <p style="margin-bottom: 0;">Please make sure MySQL is running and the database has been imported.</p>
                </section>
            <?php elseif ($reports === []): ?>
                <section class="post" style="padding: 16px;">
                    <h2 style="margin-bottom: 8px;">No reports available.</h2>
                    <p style="margin-bottom: 0;">Try a different filter or create a new report.</p>
                </section>
            <?php else: ?>
                <?php $totalReports = count($reports); ?>
                <?php foreach ($reports as $index => $report): ?>
                    <?php
                    $reportId = (int) ($report['report_id'] ?? 0);
                    $displayUsername = trim((string) ($report['username'] ?? ''));
                    if ($displayUsername === '') {
                        $displayUsername = trim(((string) ($report['first_name'] ?? '')) . ' ' . ((string) ($report['last_name'] ?? '')));
                    }
                    if ($displayUsername === '') {
                        $displayUsername = 'Anonymous';
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
                    $isVerified = ((int) ($report['verified_by'] ?? 0) > 0) || in_array($status, ['Verified', 'Resolved'], true);
                    $timeLabel = relative_time_label((string) ($report['created_at'] ?? ''));
                    $dateTimeLabels = report_date_time_labels((string) ($report['created_at'] ?? ''));
                    $mediaItems = $mediaByReport[$reportId] ?? [];
                    ?>

                    <a href="<?php echo $isAuthenticated ? 'pages/user/user-report-details.php?id=' . $reportId : $loginUrl; ?>" class="post-link"<?php echo !$isAuthenticated ? ' data-login-required="true"' : ''; ?>>
                        <section class="post">
                            <div class="profile-details">
                                <!-- <div class="post-pfp"><img src="assets/user_images/user1.jpg" alt=""></div> -->
                                <span class="username"><?php echo escape_html($displayUsername); ?></span>
                                <span>•</span>
                                <span class="hours-ago"><?php echo escape_html($timeLabel); ?></span>
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

                            <?php if ($mediaItems !== []): ?>
                            <div class="post-media-carousel">
                                <div class="carousel-container">
                                    <?php foreach ($mediaItems as $media): ?>
                                        <?php $mediaPath = normalize_media_url((string) ($media['file_url'] ?? '')); ?>
                                        <div class="carousel-slide">
                                            <?php if (($media['file_type'] ?? 'photo') === 'video'): ?>
                                                <video src="<?php echo escape_html($mediaPath); ?>" controls muted playsinline></video>
                                            <?php else: ?>
                                                <img src="<?php echo escape_html($mediaPath); ?>" alt="Report attachment">
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
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
                            <?php endif; ?>

                            <div class="post-buttons">
                                <div class="post-buttons-left">
                                    <button type="button" class="post-upvote<?php echo !empty($report['has_upvoted']) ? ' is-active' : ''; ?>" data-report-action="upvote" data-report-id="<?php echo $reportId; ?>" data-action-url="pages/user/report-action.php" aria-pressed="<?php echo !empty($report['has_upvoted']) ? 'true' : 'false'; ?>"<?php echo !$isAuthenticated ? ' data-login-required="true"' : ''; ?>>
                                        <i class="fa-solid fa-square-caret-up"></i>
                                        <span><?php echo (int) ($report['upvote_count'] ?? 0); ?></span>
                                    </button>
                                    <button type="button" class="comment post-comment" data-report-details-url="pages/user/user-report-details.php?id=<?php echo $reportId; ?>#comments"<?php echo !$isAuthenticated ? ' data-login-required="true"' : ''; ?>>
                                        <i class="fa-solid fa-comment-dots"></i>
                                        <span><?php echo (int) ($report['comment_count'] ?? 0); ?></span>
                                    </button>
                                    <button type="button" class="post-resolved<?php echo !empty($report['has_resolved']) ? ' is-active' : ''; ?>" data-report-action="resolved" data-report-id="<?php echo $reportId; ?>" data-action-url="pages/user/report-action.php" aria-pressed="<?php echo !empty($report['has_resolved']) ? 'true' : 'false'; ?>"<?php echo !$isAuthenticated ? ' data-login-required="true"' : ''; ?>>
                                        <i class="fa-solid fa-circle-check"></i>
                                        Resolved | <span><?php echo (int) ($report['resolved_count'] ?? 0); ?></span>
                                    </button>
                                </div>

                                <div class="post-buttons-right">
                                    <?php if ($isVerified): ?>
                                        <button class="verified">
                                            <i class="fa-solid fa-user-check"></i>
                                            Verified by Officials
                                        </button>
                                    <?php endif; ?>
                                    <button class="status status-pill status-<?php echo escape_html($statusClassSuffix); ?>">
                                        Status: <?php echo escape_html($statusUpper); ?>
                                    </button>
                                </div>
                            </div>
                        </section>
                    </a>

                    <?php if ($index < $totalReports - 1): ?>
                        <hr>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </main>
    </div>
    <script src="pages/shared-js/report-actions.js" defer></script>
<script src="pages/shared-js/notifications.js" defer></script>
<script src="pages/shared-js/navbar-user-menu.js" defer></script>
</body>
</html>
