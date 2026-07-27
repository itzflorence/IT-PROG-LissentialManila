<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/thread-query.php';

require_login('../auth/login.php');

/** @var string $pageStatus */
/** @var string $pageTitle */
/** @var string $pageSubtitle */

$search = trim((string) ($_GET['q'] ?? ''));
$threads = [];
$counts = ['all' => 0, 'Active' => 0, 'Resolved' => 0, 'Archived' => 0];
$errorMessage = null;

try {
    $db = thread_db();
    $threads = thread_fetch_all($db, $pageStatus === 'All' ? null : $pageStatus, $search);
    $counts = thread_status_counts($db);
} catch (Throwable $error) {
    $errorMessage = $error->getMessage();
}

$currentFile = basename((string) ($_SERVER['PHP_SELF'] ?? 'user-threads.php'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= thread_escape($pageTitle) ?> | Lissential Manila</title>
    <link rel="stylesheet" href="../../style/user/threads.css">
</head>
<body>
<nav>
    <header class="navbar">
        <div class="navbar-logo">
            <a href="../../index.php"><img src="../../assets/LOGO/logo_normal.png" alt="Lissential Manila logo"></a>
        </div>
        <form class="searchbar" method="get" action="<?= thread_escape($currentFile) ?>">
            <input type="search" name="q" value="<?= thread_escape($search) ?>" placeholder="Search threads by title, category, or location" aria-label="Search threads">
            <button type="submit" aria-label="Submit search"><i class="fa-solid fa-magnifying-glass"></i></button>
        </form>
        <div class="icon-button-wrapper">
            <button type="button" class="icon-button" aria-label="Notifications"><i class="fa-solid fa-bell"></i></button>
            <button type="button" class="icon-button" aria-label="Account"><i class="fa-solid fa-user"></i></button>
        </div>
    </header>

    <aside class="sidebar">
        <div class="create-report"><a href="user-create-report.php">CREATE REPORT</a></div>

        <div class="sidebar-options-wrapper">
            <span class="sidebar-title">FEED</span>
            <div class="sidebar-options"><a href="../../index.php">All Reports</a></div>
            <hr>
        </div>

        <div class="sidebar-options-wrapper">
            <span class="sidebar-title">THREADS</span>
            <div class="sidebar-options">
                <a class="<?= $pageStatus === 'All' ? 'is-active' : '' ?>" href="user-threads.php">All Threads <span><?= $counts['all'] ?></span></a>
                <a class="<?= $pageStatus === 'Active' ? 'is-active' : '' ?>" href="user-active-threads.php">Active <span><?= $counts['Active'] ?></span></a>
                <a class="<?= $pageStatus === 'Resolved' ? 'is-active' : '' ?>" href="user-resolved-threads.php">Resolved <span><?= $counts['Resolved'] ?></span></a>
                <a href="user-threads.php?status=Archived" aria-disabled="true" title="Archived view is outside the assigned scope">Archived <span><?= $counts['Archived'] ?></span></a>
            </div>
            <hr>
        </div>

        <div class="sidebar-options-wrapper">
            <span class="sidebar-title">MY ACTIVITY</span>
            <div class="sidebar-options"><a href="user-my-reports.php">My Reports</a></div>
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
