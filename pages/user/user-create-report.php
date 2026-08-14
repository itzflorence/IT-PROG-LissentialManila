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
$selectedStatus = trim((string) ($_GET['status'] ?? ''));
$selectedCategoryId = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT);

$categories = [];
$locationsGrouped = [];
$errorMessage = trim((string) ($_GET['error'] ?? ''));
$errorMessage = $errorMessage !== '' ? $errorMessage : null;

try {
    $db = thread_db();
    $categories = fetch_categories($db);
    $locationsGrouped = official_fetch_locations_grouped($db);
} catch (Throwable $error) {
    $categories = [];
    $locationsGrouped = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Report - LissentialManila</title>
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

        <span class="copyright-footer">IT-PROG © 2026. All rights reserved.</span>
    </aside>
</nav>

<div class="main-wrapper" style="margin-right: 0;">
    <main>
        <form class="create-report-container" action="user-report-process.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="create">

            <?php if ($errorMessage !== null): ?>
                <p class="form-error"><?php echo escape_html($errorMessage); ?></p>
            <?php endif; ?>

        <form id="report-form" class="create-report-container" action="user-report-process.php" method="POST" enctype="multipart/form-data">
            <div class="form-header">
                <input type="text" name="title" class="input-report-title" placeholder="Report Title*" maxlength="255" required>
                <input type="text" name="description" class="input-report-desc" placeholder="Description (optional, required for 'Other')">
            </div>

            <div class="form-meta-row">
                <div class="meta-pill">
                    <label for="location-input">LOCATION:</label>
                    <select id="location-input" name="location_id" required>
                        <option value="" disabled selected>---</option>
                        <?php foreach ($locationsGrouped as $city => $districts): ?>
                            <optgroup label="<?php echo escape_html((string) $city); ?>">
                                <?php foreach ($districts as $district): ?>
                                    <option value="<?php echo (int) $district['location_id']; ?>"><?php echo escape_html((string) $district['district']); ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="meta-pill">
                    <i class="fa-solid fa-shapes category-icon"></i>
                    <label for="category-select">CATEGORY:</label>
                    <select id="category-select" name="category_id" required>
                        <option value="" disabled selected>*dropdown*</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo (int) ($category['category_id'] ?? 0); ?>"><?php echo escape_html((string) ($category['category_name'] ?? '')); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="media-upload-area">
                <input type="file" id="media-file-input" name="media[]" multiple accept="image/*,video/*" hidden>
                <label for="media-file-input" class="media-upload-label">
                    <div class="upload-icon-wrapper">
                        <i class="fa-solid fa-images"></i>
                        <i class="fa-solid fa-arrow-up upload-arrow"></i>
                    </div>
                    <span class="upload-text">Upload Media (up to 4 files)</span>
                </label>
            </div>

            <div class="form-submit-wrapper">
                <button type="submit" class="btn-post-report">Post Report</button>
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
                ? `${fileInput.files.length} file(s) selected`
                : 'Upload Media (up to 4 files)';
        });
    })();
</script>
<script src="../shared-js/notifications.js" defer></script>
<script src="../shared-js/navbar-user-menu.js" defer></script>
</body>

<script>
    const fileInput = document.getElementById('media-file-input');
    const textSpan = document.querySelector('.upload-text');

    fileInput.addEventListener('change', function(e) {
        const fileCount = e.target.files.length;
        if (fileCount > 0) {
            textSpan.textContent = fileCount + ' file(s) selected';
            textSpan.style.color = '#34622f';
        } else {
            textSpan.textContent = 'Upload Media';
            textSpan.style.color = '#d1d5db';
        }
    });

    document.getElementById('report-form').addEventListener('submit', function(e) {
        if (fileInput.files.length === 0) {
            e.preventDefault();
            alert('Please attach at least one photo or video to your report.');
            textSpan.style.color = 'var(--colorRed)';
        }
    });
</script>
</body>
</html>
