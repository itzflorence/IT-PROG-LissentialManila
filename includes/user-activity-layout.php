<?php
declare(strict_types=1);

function activity_layout_escape(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** @param list<array{category_id:int,category_name:string}> $categories */
function render_activity_navigation(array $categories, string $activePage): void
{
    $username = (string) ($_SESSION['username'] ?? '');
    $fullName = (string) ($_SESSION['full_name'] ?? '');
    ?>
    <nav>
        <header class="navbar">
            <div class="navbar-logo"><a href="../../index.php"><img src="../../assets/LOGO/logo_normal.png" alt="LissentialManila Logo"></a></div>
            <div class="searchbar"><input type="search" placeholder="Search for a report..."><i class="fa-solid fa-magnifying-glass"></i></div>
            <div class="auth-state-pill auth-state-pill--user">Logged in as <?= activity_layout_escape($username) ?></div>
            <div class="icon-button-wrapper">
                <button type="button" class="icon-button notif-bell-btn" id="notifBellBtn" data-notif-api="../../includes/notifications-api.php" aria-haspopup="true" aria-expanded="false" aria-label="Notifications"><i class="fa-solid fa-bell"></i></button>
                <div class="notification-panel" id="notifPanel" hidden><div class="notification-panel-header">Nearby Alerts</div><div class="notification-panel-body" id="notifPanelBody"></div></div>
                <button type="button" class="icon-button user-menu-btn" id="userMenuBtn" aria-haspopup="true" aria-expanded="false" aria-label="Account menu"><i class="fa-solid fa-user"></i></button>
                <div class="user-menu-panel" id="userMenuPanel" hidden>
                    <div class="user-menu-info"><span class="user-menu-name"><?= activity_layout_escape($fullName !== '' ? $fullName : $username) ?></span><span class="user-menu-username">@<?= activity_layout_escape($username) ?></span></div>
                    <a class="user-menu-logout" href="../auth/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Log out</a>
                </div>
            </div>
        </header>
        <aside class="sidebar">
            <div class="create-report"><button type="button" onclick="window.location.href='user-create-report.php'">CREATE REPORT</button></div>
            <div class="sidebar-options-wrapper"><span class="sidebar-title">FEED</span><div class="sidebar-options"><a href="../../index.php">All Reports</a><a href="#">Official Advisories</a></div><hr></div>
            <div class="sidebar-options-wrapper">
                <span class="sidebar-title">MY ACTIVITY</span>
                <div class="sidebar-options">
                    <a href="user-my-reports.php"<?= $activePage === 'my-reports' ? ' class="is-active"' : '' ?>>My Reports</a>
                    <a href="user-reports-near-me.php"<?= $activePage === 'nearby' ? ' class="is-active"' : '' ?>>Reports Near Me</a>
                    <a href="user-saved-locations.php"<?= $activePage === 'saved' ? ' class="is-active"' : '' ?>>Saved Locations</a>
                    <a href="user-my-comments.php"<?= $activePage === 'comments' ? ' class="is-active"' : '' ?>>My Comments</a>
                    <a href="user-profile.php">Account Profile</a>
                </div><hr>
            </div>
            <div class="sidebar-options-wrapper"><span class="sidebar-title">CATEGORIES</span><div class="sidebar-options"><a href="../../index.php">All Categories</a><?php foreach ($categories as $category): ?><a href="../../index.php?category=<?= (int) $category['category_id'] ?>"><?= activity_layout_escape($category['category_name']) ?></a><?php endforeach; ?></div><hr></div>
            <div class="sidebar-options-wrapper"><span class="sidebar-title">THREADS</span><div class="sidebar-options"><a href="user-threads.php">All</a><a href="user-active-threads.php">Active</a><a href="user-resolved-threads.php">Resolved</a></div></div>
            <span class="copyright-footer">IT-PROG &copy; 2026. All rights reserved.</span>
        </aside>
    </nav>
    <?php
}