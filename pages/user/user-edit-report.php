<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/thread-query.php';
require_once __DIR__ . '/../../includes/report-feed.php';
require_once __DIR__ . '/../../includes/official-query.php';

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
$report = null;
$mediaItems = [];
$errorMessage = trim((string) ($_GET['error'] ?? ''));
$errorMessage = $errorMessage !== '' ? $errorMessage : null;

$currentUserId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$currentUserId = $currentUserId === false ? null : $currentUserId;

$reportId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$reportId = $reportId === false ? null : $reportId;

if ($reportId === null) {
    header('Location: user-my-reports.php?error=' . urlencode('Invalid report reference.'));
    exit;
}

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
    $locationsGrouped = official_fetch_locations_grouped($db);

    $reportStatement = $db->prepare(
        'SELECT report_id, user_id, location_id, category_id, title, description
         FROM reports
         WHERE report_id = ? AND is_deleted = FALSE
         LIMIT 1'
    );
    $reportStatement->bind_param('i', $reportId);
    $reportStatement->execute();
    $report = $reportStatement->get_result()->fetch_assoc();

    if (!$report || (int) $report['user_id'] !== $currentUserId) {
        header('Location: user-my-reports.php?error=' . urlencode('You can only edit your own reports.'));
        exit;
    }

    $mediaStatement = $db->prepare('SELECT media_id, file_url, file_type FROM media_attachments WHERE report_id = ? ORDER BY media_id ASC');
    $mediaStatement->bind_param('i', $reportId);
    $mediaStatement->execute();
    $mediaItems = $mediaStatement->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Throwable $error) {
    header('Location: user-my-reports.php?error=' . urlencode('Unable to load that report right now.'));
    exit;
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
                <a href="user-reports-near-me.php">Reports Near Me</a>
                <a href="user-saved-locations.php">Saved Locations</a>
                <a href="user-my-comments.php">My Comments</a>
                <a href="/IT-PROG-LISSENTIALMANILA-MAIN/pages/user/user-profile.php">Account Profile</a>
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
        <form class="create-report-container" action="user-report-process.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="report_id" value="<?php echo (int) $reportId; ?>">

            <?php if ($errorMessage !== null): ?>
                <p class="form-error"><?php echo escape_html($errorMessage); ?></p>
            <?php endif; ?>

            <div class="form-header">
                <input type="text" name="title" class="input-report-title" value="<?php echo escape_html((string) $report['title']); ?>" maxlength="255" required>
                <input type="text" name="description" class="input-report-desc" value="<?php echo escape_html((string) $report['description']); ?>" placeholder="Description (optional, required for 'Other')">
            </div>

            <div class="form-meta-row">
                <div class="meta-pill">
                    <label for="location-input">LOCATION:</label>
                    <select id="location-input" name="location_id" required>
                        <?php foreach ($locationsGrouped as $city => $districts): ?>
                            <optgroup label="<?php echo escape_html((string) $city); ?>">
                                <?php foreach ($districts as $district): ?>
                                    <option value="<?php echo (int) $district['location_id']; ?>" <?php echo (int) $district['location_id'] === (int) $report['location_id'] ? 'selected' : ''; ?>><?php echo escape_html((string) $district['district']); ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="meta-pill">
                    <i class="fa-solid fa-shapes category-icon"></i>
                    <label for="category-select">CATEGORY:</label>
                    <select id="category-select" name="category_id" required>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo (int) ($category['category_id'] ?? 0); ?>" <?php echo (int) ($category['category_id'] ?? 0) === (int) $report['category_id'] ? 'selected' : ''; ?>><?php echo escape_html((string) ($category['category_name'] ?? '')); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <?php if ($mediaItems !== []): ?>
                <div class="existing-media-grid" style="display: flex; flex-wrap: wrap; gap: var(--space-small);">
                    <?php foreach ($mediaItems as $media): ?>
                        <?php $mediaPath = normalize_media_url((string) $media['file_url']); ?>
                        <label class="existing-media-item" style="position: relative; width: 140px; height: 140px; border-radius: 10px; overflow: hidden; display: block;">
                            <?php if ($media['file_type'] === 'video'): ?>
                                <video src="../../<?php echo escape_html($mediaPath); ?>" style="width: 100%; height: 100%; object-fit: cover;" muted></video>
                            <?php else: ?>
                                <img src="../../<?php echo escape_html($mediaPath); ?>" alt="Report attachment" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php endif; ?>
                            <span style="position: absolute; top: 6px; right: 6px; background: rgba(0,0,0,0.6); color: #fff; border-radius: 6px; padding: 4px 6px; font-size: 0.75rem; display: flex; align-items: center; gap: 4px;">
                                <input type="checkbox" name="remove_media[]" value="<?php echo (int) $media['media_id']; ?>">
                                Remove
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="media-upload-area">
                <input type="file" id="media-file-input" name="media[]" multiple accept="image/*,video/*" hidden>
                <label for="media-file-input" class="media-upload-label">
                    <div class="upload-icon-wrapper">
                        <i class="fa-solid fa-images"></i>
                        <i class="fa-solid fa-arrow-up upload-arrow"></i>
                    </div>
                    <span class="upload-text">Add More Media (up to 4 total)</span>
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
    (() => {
        const maxFiles = 4;
        const fileInput = document.getElementById('media-file-input');
        const uploadText = document.querySelector('.upload-text');

        fileInput.addEventListener('change', () => {
            if (fileInput.files.length > maxFiles) {
                const trimmed = new DataTransfer();
                Array.from(fileInput.files).slice(0, maxFiles).forEach((file) => trimmed.items.add(file));
                fileInput.files = trimmed.files;
                window.alert(`You can attach up to ${maxFiles} files. Only the first ${maxFiles} were kept.`);
            }
            uploadText.textContent = fileInput.files.length > 0
                ? `${fileInput.files.length} new file(s) selected`
                : 'Add More Media (up to 4 total)';
        });
    })();
</script>
<script src="../shared-js/notifications.js" defer></script>
<script src="../shared-js/navbar-user-menu.js" defer></script>
</body>

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
