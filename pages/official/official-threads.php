<?php

declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/thread-query.php';
require_once __DIR__ . '/../../includes/report-feed.php';

// Only Officials and Admins use the official thread views (so they can edit)
require_role(['Official', 'Admin'], '../auth/login.php', '../../index.php');

$isAuthenticated = is_authenticated();
$username = $_SESSION['username'] ?? null;
$safeUsername = thread_escape((string) ($username ?? ''));
$loginUrl = '../auth/login.php';
$logoutUrl = '../auth/logout.php';

$search = trim((string) ($_GET['q'] ?? ''));
$threads = [];
$errorMessage = null;

try {
    $db = thread_db();
    $threads = thread_fetch_all($db, null, $search);
} catch (Throwable $error) {
    $errorMessage = $error->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Threads | Lissential Manila</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
          integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
          crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="../../style/user/threads.css">
</head>
<body>
<nav>
    <header class="navbar">
        <div class="navbar-logo">
            <a href="official-home.php"><img src="../../assets/LOGO/logo_normal.png" alt="Lissential Manila logo"></a>
        </div>

        <div class="searchbar">
            <input type="search" placeholder="Search for a report...">
            <i class="fa-solid fa-magnifying-glass"></i>
        </div>

        <div class="auth-state-pill auth-state-pill--user">
            Logged in as <?= $safeUsername ?>
        </div>

        <div class="icon-button-wrapper">
            <button type="button" class="icon-button" aria-label="Notifications"><i class="fa-solid fa-bell"></i></button>
            <button type="button" class="icon-button" title="Log out" onclick="window.location.href='<?= thread_escape($logoutUrl) ?>'">
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
                <a href="official-home.php">Review Queue</a>
                <a href="official-assigned-area.php">Assigned Area</a>
                <a href="official-create-thread.php">Create Thread</a>
            </div>
            <hr>
        </div>

        <div class="sidebar-options-wrapper">
            <span class="sidebar-title">INCIDENT THREADS</span>
            <div class="sidebar-options">
                <a href="official-threads.php" class="is-active">All</a>
                <a href="official-active-threads.php">Active</a>
                <a href="official-resolved-threads.php">Resolved</a>
                <a href="official-archived-threads.php">Archived</a>
            </div>
            <hr>
        </div>

        <div class="sidebar-options-wrapper">
            <span class="sidebar-title">GENERAL</span>
            <div class="sidebar-options">
                <a href="official-home.php">Review Queue</a>
                <a href="../user/user-profile.php">Account Profile</a>
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
                <h1>All Threads</h1>
                <p>Browse grouped traffic incidents reported across Metro Manila. Click any thread to edit it.</p>
            </div>
            <div class="threads-hero__count">
                <strong><?= count($threads) ?></strong>
                <span>result<?= count($threads) === 1 ? '' : 's' ?></span>
            </div>
        </section>

        <nav class="thread-tabs" aria-label="Thread status filters">
            <a class="is-active" href="official-threads.php">All</a>
            <a class="" href="official-active-threads.php">Active</a>
            <a class="" href="official-resolved-threads.php">Resolved</a>
            <a class="" href="official-archived-threads.php">Archived</a>
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
                <h2>No threads found</h2>
                <p><?= $search !== '' ? 'Try another search term or clear the search box.' : 'Threads will appear here after officials group related incident reports.'; ?></p>
                <?php if ($search !== ''): ?><a href="official-threads.php">Clear search</a><?php endif; ?>
            </section>
        <?php else: ?>
            <section class="thread-grid" aria-label="All Threads results">
                <?php foreach ($threads as $thread): ?>
                    <?php
                    $threadCardBasePath = '../user/';
                    $threadCardEditUrl = 'official-edit-thread.php?id=' . (int) $thread['thread_id'];
                    require __DIR__ . '/../../includes/thread-card.php';
                    ?>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </main>
</div>
</body>
</html>