<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/thread-query.php';
require_once __DIR__ . '/report-feed.php';

// Block this page unless the visitor is authenticated
require_login('../auth/login.php');

/** @var string $pageStatus */
/** @var string $pageTitle */
/** @var string $pageSubtitle */

// Search term used by thread query helper (tabs still control status)
$search = trim((string) ($_GET['q'] ?? ''));
$threads = [];
$counts = ['all' => 0, 'Active' => 0, 'Resolved' => 0, 'Archived' => 0];
$errorMessage = null;

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

$categories = [];

try {
    $db = thread_db();
    // Thread cards + per-status counters used by hero/sidebar/tab UI
    $threads = thread_fetch_all($db, $pageStatus === 'All' ? null : $pageStatus, $search);
    $counts = thread_status_counts($db);

    // Sidebar categories are shared across user pages.
    $categories = fetch_categories($db);
} catch (Throwable $error) {
    $errorMessage = $error->getMessage();
}

// Needed so "clear search" stays on the current threads page variant
$currentFile = basename((string) ($_SERVER['PHP_SELF'] ?? 'user-threads.php'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= thread_escape($pageTitle) ?> | Lissential Manila</title>
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

            <button type="button" class="icon-button" title="Log out" onclick="window.location.href='<?= thread_escape($logoutUrl) ?>'">
                <i class="fa-solid fa-user"></i>
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
                <a href="#">Reports Near Me</a>
                <a href="#">Saved Locations</a>
                <a href="#">My Comments</a>
                <a href="#">Account Profile</a>
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
            </div>
        </div>

        <span class="copyright-footer">IT-PROG © 2026. All rights reserved.</span>
    </aside>
</nav>

<div class="threads-main-wrapper">
    <main class="threads-page">
        <section class="threads-hero">
            <div>
                <p class="threads-eyebrow">INCIDENT THREADS</p>
                <h1><?= thread_escape($pageTitle) ?></h1>
                <p><?= thread_escape($pageSubtitle) ?></p>
            </div>
            <div class="threads-hero__count">
                <strong><?= count($threads) ?></strong>
                <span>result<?= count($threads) === 1 ? '' : 's' ?></span>
            </div>
        </section>

        <nav class="thread-tabs" aria-label="Thread status filters">
            <a class="<?= $pageStatus === 'All' ? 'is-active' : '' ?>" href="user-threads.php">All</a>
            <a class="<?= $pageStatus === 'Active' ? 'is-active' : '' ?>" href="user-active-threads.php">Active</a>
            <a class="<?= $pageStatus === 'Resolved' ? 'is-active' : '' ?>" href="user-resolved-threads.php">Resolved</a>
        </nav>

        <?php if ($errorMessage !== null): ?>
            <section class="thread-state thread-state--error">
                <i class="fa-solid fa-database"></i>
                <h2>Unable to load incident threads</h2>
                <p>Make sure MySQL is running and the <code>lissential_manila_db</code> database has been imported.</p>
                <details><summary>Technical details</summary><pre><?= thread_escape($errorMessage) ?></pre></details>
            </section>
        <?php elseif ($threads === []): ?>
            <section class="thread-state">
                <i class="fa-regular fa-folder-open"></i>
                <h2>No <?= $pageStatus === 'All' ? '' : strtolower($pageStatus) . ' ' ?>threads found</h2>
                <p><?= $search !== '' ? 'Try another search term or clear the search box.' : 'Threads will appear here after officials group related incident reports.' ?></p>
                <?php if ($search !== ''): ?><a href="<?= thread_escape($currentFile) ?>">Clear search</a><?php endif; ?>
            </section>
        <?php else: ?>
            <section class="thread-grid" aria-label="Incident thread results">
                <?php foreach ($threads as $thread): ?>
                    <?php require __DIR__ . '/thread-card.php'; ?>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </main>
</div>
</body>
</html>
