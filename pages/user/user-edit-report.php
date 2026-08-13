<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/thread-query.php';
require_once __DIR__ . '/../../includes/report-feed.php';

function escape_html(?string $value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

require_login('../auth/login.php');

$isAuthenticated = is_authenticated();
$currentUserId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT);
$username = $_SESSION['username'] ?? null;
$safeUsername = escape_html((string) ($username ?? ''));

$loginUrl = '../auth/login.php';
$logoutUrl = '../auth/logout.php';
$registerUrl = '../auth/register.php';
$createReportUrl = $isAuthenticated ? 'user-create-report.php' : $registerUrl;
$myReportsUrl = $isAuthenticated ? 'user-my-reports.php' : $loginUrl;
$allThreadsUrl = $isAuthenticated ? 'user-threads.php' : $loginUrl;
$activeThreadsUrl = $isAuthenticated ? 'user-active-threads.php' : $loginUrl;
$resolvedThreadsUrl = $isAuthenticated ? 'user-resolved-threads.php' : $loginUrl;

$errorFlag = trim((string) ($_GET['error'] ?? ''));

$reportId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$reportId) {
    header('Location: user-my-reports.php?error=' . urlencode('Invalid report ID.'));
    exit;
}

$report = null;
$existingMedia = [];
$categories = [];
$locationsGrouped = [];

try {
    $db = thread_db();

    $stmt = $db->prepare('SELECT * FROM reports WHERE report_id = ? AND user_id = ? AND is_deleted = FALSE LIMIT 1');
    $stmt->bind_param('ii', $reportId, $currentUserId);
    $stmt->execute();
    $report = $stmt->get_result()->fetch_assoc();

    if (!$report) {
        header('Location: user-my-reports.php?error=' . urlencode('Report not found or you do not have permission to edit it.'));
        exit;
    }

    if (in_array($report['status'], ['Verified', 'Resolved'], true)) {
        header('Location: user-my-reports.php?error=' . urlencode('This report has already been processed by officials and can no longer be edited.'));
        exit;
    }

    $mediaStmt = $db->prepare('SELECT file_url, file_type FROM media_attachments WHERE report_id = ? ORDER BY media_id ASC');
    $mediaStmt->bind_param('i', $reportId);
    $mediaStmt->execute();
    $existingMedia = $mediaStmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $categories = fetch_categories($db);
    $locResult = $db->query('SELECT location_id, city, district FROM locations WHERE is_active = TRUE ORDER BY city ASC, district ASC');
    while ($row = $locResult->fetch_assoc()) {
        $locationsGrouped[$row['city']][] = $row;
    }

} catch (Throwable $error) {
    header('Location: user-my-reports.php?error=' . urlencode('A database error occurred.'));
    exit;
}

$previewBg = '../../assets/report_media/media1-1.jfif'; // Default fallback
$previewIsVideo = false;

if (!empty($existingMedia)) {
    $normalizedPath = normalize_media_url($existingMedia[0]['file_url']);
    $previewBg = '../../' . $normalizedPath;
    $previewIsVideo = $existingMedia[0]['file_type'] === 'video';
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
    <link rel="stylesheet" href="../../style/official/official.css">
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

        <form class="searchbar" action="user-search-results.php" method="GET">
            <input type="search" name="q" placeholder="Search by title or location..." required>
            <button type="submit" style="display: none;"></button>
            <i class="fa-solid fa-magnifying-glass"></i>
        </form>

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
                    <a href="user-profile.php">Account Profile</a>
                </div>
                <hr>
            </div>
        <?php endif; ?>

        <div class="sidebar-options-wrapper">
            <span class="sidebar-title">CATEGORIES</span>
            <div class="sidebar-options">
                <a href="../../index.php" class="sidebar-filter-link">All Categories</a>
                <?php foreach ($categories as $category): ?>
                    <a href="../../index.php?category=<?php echo $category['category_id']; ?>" class="sidebar-filter-link">
                        <?php echo escape_html($category['category_name']); ?>
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
        <?php if ($errorFlag !== ''): ?>
            <div class="flash-banner flash-banner--error" style="width: 100%; margin-bottom: 16px; border-radius: 8px;">
                <i class="fa-solid fa-triangle-exclamation"></i> <?php echo escape_html($errorFlag); ?>
            </div>
        <?php endif; ?>

        <form class="create-report-container" action="user-edit-report-process.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="report_id" value="<?php echo (int) $report['report_id']; ?>">

            <div class="form-header">
                <input type="text" name="title" class="input-report-title" value="<?php echo escape_html($report['title']); ?>" required maxlength="255">
                <input type="text" name="description" class="input-report-desc" value="<?php echo escape_html($report['description']); ?>">
            </div>

            <div class="form-meta-row">
                <div class="meta-pill">
                    <label for="location-select">LOCATION:</label>
                    <select id="location-select" name="location_id" required>
                        <?php foreach ($locationsGrouped as $city => $districts): ?>
                            <optgroup label="<?php echo escape_html($city); ?>">
                                <?php foreach ($districts as $dist): ?>
                                    <option value="<?php echo (int) $dist['location_id']; ?>" <?php echo ((int)$report['location_id'] === (int)$dist['location_id']) ? 'selected' : ''; ?>>
                                        <?php echo escape_html($dist['district']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="meta-pill">
                    <i class="fa-solid fa-shapes category-icon"></i>
                    <label for="category-select">CATEGORY:</label>
                    <select id="category-select" name="category_id" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo (int) $cat['category_id']; ?>" <?php echo ((int)$report['category_id'] === (int)$cat['category_id']) ? 'selected' : ''; ?>>
                                <?php echo escape_html($cat['category_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="media-upload-area" style="position: relative; overflow: hidden;">
                <?php if ($previewIsVideo): ?>
                    <video src="<?php echo escape_html($previewBg); ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 10px;" muted playsinline></video>
                <?php else: ?>
                    <img src="<?php echo escape_html($previewBg); ?>" alt="Attached Media" style="width: 100%; height: 100%; object-fit: cover; border-radius: 10px;">
                <?php endif; ?>

                <input type="file" id="media-file-input" name="media_files[]" multiple accept="image/*,video/*" hidden>

                <label for="media-file-input" class="media-upload-label" style="position: absolute; background: rgba(0,0,0,0.5); padding: 12px 24px; border-radius: 8px; backdrop-filter: blur(4px);">
                    <span class="upload-text" style="font-size: 1rem; color: #ffffff;"><i class="fa-solid fa-pen"></i> Replace Media</span>
                </label>
            </div>
            <p style="font-size: 0.8rem; color: var(--color4); text-align: right; margin-top: 4px;">Leave media blank to keep your existing attachments.</p>

            <div class="form-submit-wrapper" style="gap: var(--space-small);">
                <button type="button" class="btn-post-report" onclick="window.location.href='user-my-reports.php'" style="background-color: var(--color3); color: var(--colorText);">Cancel</button>
                <button type="submit" class="btn-post-report">Save Changes</button>
            </div>
        </form>
    </main>
</div>

<script>
    document.getElementById('media-file-input').addEventListener('change', function(e) {
        const fileCount = e.target.files.length;
        const textSpan = document.querySelector('.upload-text');

        if (fileCount > 0) {
            textSpan.innerHTML = '<i class="fa-solid fa-check"></i> ' + fileCount + ' new file(s) selected';
            textSpan.style.color = '#a3cfbb';
        } else {
            textSpan.innerHTML = '<i class="fa-solid fa-pen"></i> Replace Media';
            textSpan.style.color = '#ffffff';
        }
    });
</script>
</body>
</html>
