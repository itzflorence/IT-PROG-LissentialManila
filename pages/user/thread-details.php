<?php

declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/thread-query.php';
require_once __DIR__ . '/../../includes/report-feed.php';

// Block this page unless the visitor is authenticated
require_login('../auth/login.php');

// Current auth/session state used by the shared navbar/sidebar
$isAuthenticated = is_authenticated();
$username = $_SESSION['username'] ?? null;
$safeUsername = thread_escape((string) ($username ?? ''));

// Centralized navigation targets so links stay consistent across user pages
$loginUrl = '../auth/login.php';
$logoutUrl = '../auth/logout.php';
$registerUrl = '../auth/register.php';
$createReportUrl = $isAuthenticated ? 'user-create-report.php' : $registerUrl;
$myReportsUrl = $isAuthenticated ? 'user-my-reports.php' : $loginUrl;
$allThreadsUrl = $isAuthenticated ? 'user-threads.php' : $loginUrl;
$activeThreadsUrl = $isAuthenticated ? 'user-active-threads.php' : $loginUrl;
$resolvedThreadsUrl = $isAuthenticated ? 'user-resolved-threads.php' : $loginUrl;
$archivedThreadsUrl = $isAuthenticated ? 'user-archived-threads.php' : $loginUrl;
$nearMeUrl = $isAuthenticated ? 'user-reports-near-me.php' : $loginUrl;
$profileUrl = $isAuthenticated ? 'user-profile.php' : $loginUrl;

// Sidebar category links can preserve a valid report status filter
$allowedStatuses = ['Pending', 'Verified', 'Resolved', 'Rejected'];
$selectedStatus = trim((string) ($_GET['status'] ?? ''));
if (!in_array($selectedStatus, $allowedStatuses, true)) {
    $selectedStatus = '';
}

// Optional category filter used to highlight the active category link
$selectedCategoryId = filter_input(
    INPUT_GET,
    'category',
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);
$selectedCategoryId = $selectedCategoryId === false ? null : $selectedCategoryId;

// Thread ID determines which official thread details page is shown
$threadId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$thread = null;
$reports = [];
$categories = [];
$errorMessage = null;

if (!$threadId || $threadId < 1) {
    http_response_code(400);
    $errorMessage = 'A valid thread ID is required.';
} else {
    try {
        $db = thread_db();
        // Sidebar categories are shared with other user pages
        $categories = fetch_categories($db);

        // Primary thread record + linked community reports
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
          integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
          crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="../../style/user/threads.css">
</head>
<body>
<nav>
    <header class="navbar">
        <div class="navbar-logo">
            <a href="../../index.php"><img src="../../assets/LOGO/logo_normal.png" alt="Lissential Manila logo"></a>
        </div>

        <div class="searchbar">
            <input type="search" placeholder="Search for a report...">
            <i class="fa-solid fa-magnifying-glass"></i>
        </div>

        <?php if ($isAuthenticated): ?>
        <div class="auth-state-pill auth-state-pill--user">
            Logged in as <?= $safeUsername ?>
        </div>
        <?php endif; ?>

        <?php if ($isAuthenticated): ?>
        <div class="icon-button-wrapper">
            <button type="button" class="icon-button" aria-label="Notifications"><i class="fa-solid fa-bell"></i></button>

            <button type="button" class="icon-button" title="Account Profile" onclick="window.location.href='<?= thread_escape($profileUrl) ?>'">
                <i class="fa-solid fa-user"></i>
            </button>

            <button type="button" class="icon-button" title="Log out" onclick="window.location.href='<?= thread_escape($logoutUrl) ?>'">
                <i class="fa-solid fa-right-from-bracket"></i>
            </button>
        </div>
        <?php else: ?>
        <div class="login-button">
            <button type="button" onclick="window.location.href='<?= thread_escape($loginUrl) ?>'">LOG IN</button>
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
            <button type="button" onclick="window.location.href='<?= thread_escape($createReportUrl) ?>'">
                <?= $isAuthenticated ? 'CREATE REPORT' : 'CREATE ACCOUNT' ?>
            </button>
        </div>

        <div class="sidebar-options-wrapper">
            <span class="sidebar-title">FEED</span>
            <div class="sidebar-options">
                <a href="../../index.php">All Reports</a>
                <a href="#">Official Advisories</a>
            </div>
            <hr>
        </div>

        <?php if ($isAuthenticated): ?>
        <div class="sidebar-options-wrapper">
            <span class="sidebar-title">MY ACTIVITY</span>
            <div class="sidebar-options">
                <a href="<?= thread_escape($myReportsUrl) ?>">My Reports</a>
                <a href="<?= thread_escape($nearMeUrl) ?>">Reports Near Me</a>
                <a href="#">Saved Locations</a>
                <a href="#">My Comments</a>
                <a href="<?= thread_escape($profileUrl) ?>">Account Profile</a>
            </div>
            <hr>
        </div>
        <?php endif; ?>

        <div class="sidebar-options-wrapper">
            <span class="sidebar-title">CATEGORIES</span>
            <div class="sidebar-options">
                <a
                    href="<?= thread_escape(build_filter_url('../../index.php', $selectedStatus !== '' ? $selectedStatus : null, null)) ?>"
                    class="sidebar-filter-link<?= $selectedCategoryId === null ? ' is-active' : '' ?>"
                >
                    All Categories
                </a>
                <?php foreach ($categories as $category): ?>
                    <?php $categoryId = (int) ($category['category_id'] ?? 0); ?>
                    <a
                        href="<?= thread_escape(build_filter_url('../../index.php', $selectedStatus !== '' ? $selectedStatus : null, $categoryId)) ?>"
                        class="sidebar-filter-link<?= $selectedCategoryId === $categoryId ? ' is-active' : '' ?>"
                    >
                        <?= thread_escape((string) ($category['category_name'] ?? '')) ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <hr>
        </div>

        <div class="sidebar-options-wrapper">
            <span class="sidebar-title">THREADS</span>
            <div class="sidebar-options">
                <a href="<?= thread_escape($allThreadsUrl) ?>">All</a>
                <a href="<?= thread_escape($activeThreadsUrl) ?>">Active</a>
                <a href="<?= thread_escape($resolvedThreadsUrl) ?>">Resolved</a>
                <a href="<?= thread_escape($archivedThreadsUrl) ?>">Archived</a>
            </div>
        </div>

        <span class="copyright-footer">IT-PROG © 2026. All rights reserved.</span>
    </aside>
</nav>

<div class="threads-main-wrapper">
<main class="thread-details-page">
    <a href="user-threads.php" class="details-navbar__back"><i class="fa-solid fa-arrow-left"></i> Back to Threads</a>
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
</div>
</body>
</html>
