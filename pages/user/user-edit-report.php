<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/thread-query.php';
require_once __DIR__ . '/../../includes/report-feed.php';

// HTML-escape helper for safe output inside templates
function escape_html(?string $value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Block this page unless the visitor is authenticated
require_login('../auth/login.php');

// Current auth/session state used by the shared navbar/sidebar
$isAuthenticated = is_authenticated();

$username = $_SESSION['username'] ?? null;
$safeUsername = escape_html((string) ($username ?? ''));

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

// Category list powers the dynamic sidebar categories section
$categories = [];
try {
    $db = thread_db();
    $categories = fetch_categories($db);
} catch (Throwable $error) {
    $categories = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Report - LissentialManila</title>

    <link rel="stylesheet" href="../../style/shared/global.css">
    <link rel="stylesheet" href="../../style/shared/navbar.css">

    <link rel="stylesheet" href="../../style/user/home.css">
    <link rel="stylesheet" href="../../style/user/create-report.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
          integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
          crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>

<body>
<!----------------------------------- NAVIGATION BAR & SIDEBAR ----------------------------------->
<nav>
    <header class="navbar">
        <div class="navbar-logo">
            <a href="../../index.php">
                <img src="../../assets/LOGO/logo_normal.png" alt="LissentialManila Logo">
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
            <button type="button" class="icon-button">
                <i class="fa-solid fa-bell"></i>
            </button>

            <button type="button" class="icon-button" title="Log out" onclick="window.location.href='<?php echo $logoutUrl; ?>'">
                <i class="fa-solid fa-user"></i>
            </button>
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
                <a href="../../index.php">All Reports</a>
                <a href="#">Official Advisories</a>
            </div>
            <hr>
        </div>

        <?php if ($isAuthenticated): ?>
        <div class="sidebar-options-wrapper">
            <span class="sidebar-title">MY ACTIVITY</span>
            <div class="sidebar-options">
                <a href="<?php echo $myReportsUrl; ?>">My Reports</a>
                <a href="#">Reports Near Me</a>
                <a href="#">Saved Locations</a>
                <a href="#">My Comments</a>
                <a href="/IT-PROG-LISSENTIALMANILA-MAIN/pages/user/user-profile.php">Account Profile</a>
            </div>
            <hr>
        </div>
        <?php endif; ?>

        <div class="sidebar-options-wrapper">
            <span class="sidebar-title">CATEGORIES</span>
            <div class="sidebar-options">
                <a
                    href="<?php echo escape_html(build_filter_url('../../index.php', $selectedStatus !== '' ? $selectedStatus : null, null)); ?>"
                    class="sidebar-filter-link<?php echo $selectedCategoryId === null ? ' is-active' : ''; ?>"
                >
                    All Categories
                </a>
                <?php foreach ($categories as $category): ?>
                    <?php $categoryId = (int) ($category['category_id'] ?? 0); ?>
                    <a
                        href="<?php echo escape_html(build_filter_url('../../index.php', $selectedStatus !== '' ? $selectedStatus : null, $categoryId)); ?>"
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
                <a href="<?php echo $allThreadsUrl; ?>">All</a>
                <a href="<?php echo $activeThreadsUrl; ?>">Active</a>
                <a href="<?php echo $resolvedThreadsUrl; ?>">Resolved</a>
            </div>
        </div>

        <span class="copyright-footer">IT-PROG © 2026. All rights reserved.</span>
    </aside>
</nav>

<!--====== THREADS / RIGHT PANEL ======-->
<aside class="threads-wrapper">
</aside>

<!--====== EDIT REPORT FORM ======-->
<div class="main-wrapper">
    <main>
        <form class="create-report-container" action="#" method="POST" enctype="multipart/form-data">

            <div class="form-header">
                <input type="text" class="input-report-title" value="Gutter-deep flooding outside DLSU after sudden downpour" required>
                <input type="text" class="input-report-desc" value="Heavy torrential rain over the last 30 minutes has caused localized flooding along Taft Ave. Light vehicles are slowing down significantly to navigate the water.">
            </div>

            <div class="form-meta-row">
                <div class="meta-pill">
                    <label for="location-input">LOCATION:</label>
                    <input type="text" id="location-input" value="Taft Avenue, Manila" required>
                </div>

                <div class="meta-pill">
                    <i class="fa-solid fa-shapes category-icon"></i>
                    <label for="category-select">CATEGORY:</label>
                    <select id="category-select" required>
                        <option value="Vehicle Accident">Vehicle Accident</option>
                        <option value="Traffic Congestion">Traffic Congestion</option>
                        <option value="Flooding" selected>Flooding</option>
                        <option value="Road Blockage">Road Blockage</option>
                        <option value="Construction">Construction</option>
                        <option value="Stalled Vehicle">Stalled Vehicle</option>
                        <option value="Traffic Light">Traffic Light</option>
                        <option value="Public Transport">Public Transport</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>

            <div class="media-upload-area" style="position: relative; overflow: hidden;">
                <img src="../../assets/report_media/media1-1.jfif" alt="Attached Media" style="width: 100%; height: 100%; object-fit: cover; border-radius: 10px;">

                <input type="file" id="media-file-input" multiple accept="image/*,video/*" hidden>
                <label for="media-file-input" class="media-upload-label" style="position: absolute; background: rgba(0,0,0,0.5); padding: 12px 24px; border-radius: 8px; backdrop-filter: blur(4px);">
                    <span class="upload-text" style="font-size: 1rem; color: #ffffff;"><i class="fa-solid fa-pen"></i> Replace Media</span>
                </label>
            </div>

            <div class="form-submit-wrapper" style="gap: var(--space-small);">
                <button type="button" class="btn-post-report" onclick="window.location.href='user-my-reports.php'" style="background-color: var(--color3); color: var(--colorText);">Cancel</button>
                <button type="submit" class="btn-post-report">Save Changes</button>
            </div>

        </form>
    </main>
</div>
</body>

</html>