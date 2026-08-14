<?php declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/thread-query.php';
require_once __DIR__ . '/../../includes/report-feed.php';
require_once __DIR__ . '/../../includes/official-query.php';

function escape_html(?string $value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

require_role(['Official', 'Admin'], '../auth/login.php', '../../index.php');

$isAuthenticated = is_authenticated();
$username = $_SESSION['username'] ?? null;
$safeUsername = escape_html((string) ($username ?? ''));
$loginUrl = '../auth/login.php';
$logoutUrl = '../auth/logout.php';

// Optional seed report: pre-fills title/category/location and gets linked to the new thread once it's created
$sourceReportId = filter_input(INPUT_GET, 'report_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$sourceReportId = $sourceReportId === false ? null : $sourceReportId;

$sourceReport = null;
$categories = [];
$locationsGrouped = [];
$flashError = trim((string) ($_GET['error'] ?? ''));

try {
    $db = thread_db();
    $categories = fetch_categories($db);
    $locationsGrouped = official_fetch_locations_grouped($db);

    if ($sourceReportId !== null) {
        $sourceReport = official_fetch_report_for_edit($db, $sourceReportId);
    }
} catch (Throwable $error) {
    $flashError = $flashError !== '' ? $flashError : 'Unable to load form data right now.';
}

// Prefill defaults: from the seed report if provided, else blank/first option
$prefillTitle = $sourceReport ? (string) $sourceReport['title'] : '';
$prefillCategoryId = $sourceReport ? (int) $sourceReport['category_id'] : null;
$prefillLocationId = $sourceReport ? (int) $sourceReport['location_id'] : null;
$prefillDescription = $sourceReport ? (string) $sourceReport['description'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Thread | Lissential Manila</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
          integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
          crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="../../style/user/threads.css">
    <link rel="stylesheet" href="../../style/official/official.css">
</head>
<body>
<nav>
    <header class="navbar">
        <div class="navbar-logo">
            <a href="official-home.php"><img src="../../assets/LOGO/logo_normal.png" alt="Lissential Manila logo"></a>
        </div>

        <!-- Updated Search Bar -->
        <form class="searchbar" action="official-home.php" method="GET">
            <input type="search" name="q" placeholder="Search for a report..." required>
            <button type="submit" class="search-btn" aria-label="Submit search">
                <i class="fa-solid fa-magnifying-glass"></i>
            </button>
        </form>

        <div class="auth-state-pill auth-state-pill--user">
            Logged in as <?php echo $safeUsername; ?>
        </div>
        <div class="icon-button-wrapper">
            <button type="button" class="icon-button" aria-label="Notifications"><i class="fa-solid fa-bell"></i></button>
            <button type="button" class="icon-button" title="Log out" onclick="window.location.href='<?php echo $logoutUrl; ?>'">
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
                <a href="official-create-thread.php" style="font-weight: bold;">Create Thread</a>
            </div>
            <hr>
        </div>
        <div class="sidebar-options-wrapper">
            <span class="sidebar-title">INCIDENT THREADS</span>
            <div class="sidebar-options">
                <a href="official-threads.php">All</a>
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
    <main class="thread-details-page">
        <a href="<?php echo $sourceReportId !== null ? 'official-edit-report.php?id=' . $sourceReportId : 'official-home.php'; ?>" class="details-navbar__back">
            <i class="fa-solid fa-arrow-left"></i> <?php echo $sourceReportId !== null ? 'Back to Report' : 'Back to Review Queue'; ?>
        </a>

        <section class="thread-details-hero" style="margin-top: var(--space-medium);">
            <div class="thread-details-hero__topline">
                <span>NEW INCIDENT THREAD</span>
            </div>
            <h1 style="font-size: clamp(1.75rem, 3vw, 2.5rem);">Create Thread</h1>
            <p>
                <?php if ($sourceReport !== null): ?>
                    Group report #<?php echo (int) $sourceReport['report_id']; ?> ("<?php echo escape_html((string) $sourceReport['title']); ?>") under a new official thread. The report links to it automatically once created.
                <?php else: ?>
                    Start a new official thread to group related incident reports at a location, or post an official advisory.
                <?php endif; ?>
            </p>
        </section>

        <?php if ($flashError !== ''): ?>
            <div class="flash-banner flash-banner--error" style="margin-top: var(--space-medium);">
                <i class="fa-solid fa-triangle-exclamation"></i> <?php echo escape_html($flashError); ?>
            </div>
        <?php endif; ?>

        <div class="linked-reports" style="margin-top: var(--space-medium);">
            <form method="POST" action="official-create-thread-process.php">
                <?php if ($sourceReportId !== null): ?>
                    <input type="hidden" name="report_id" value="<?php echo $sourceReportId; ?>">
                <?php endif; ?>

                <div class="verification-panel" style="border: none; padding: 0;">
                    <div class="verification-grid">

                        <!-- Post Type Toggle -->
                        <div class="verification-field verification-field--full" <?php echo $sourceReportId !== null ? 'style="display:none;"' : ''; ?>>
                            <label>Post Type</label>
                            <div style="display: flex; gap: var(--space-medium); margin-top: 8px;">
                                <label style="cursor: pointer;">
                                    <input type="radio" name="post_type" value="thread" checked onchange="togglePostType()"> Incident Thread
                                </label>
                                <label style="cursor: pointer;">
                                    <input type="radio" name="post_type" value="advisory" onchange="togglePostType()"> Official Advisory
                                </label>
                            </div>
                            <small>Advisories are broadcasted publicly on the main feed.</small>
                        </div>

                        <div class="verification-field verification-field--full">
                            <label id="title-label" for="thread-title">Thread Title</label>
                            <input type="text" id="thread-title" name="title" value="<?php echo escape_html($prefillTitle); ?>" maxlength="255" placeholder="e.g. Taft Avenue Localized Flooding" required>
                        </div>

                        <div class="verification-field" id="category-container">
                            <label for="thread-category">Category</label>
                            <select id="thread-category" name="category_id" required>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo (int) $category['category_id']; ?>" <?php echo $prefillCategoryId === (int) $category['category_id'] ? 'selected' : ''; ?>>
                                        <?php echo escape_html((string) $category['category_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="verification-field" id="location-container">
                            <label for="thread-location">Location</label>
                            <select id="thread-location" name="location_id" required>
                                <?php foreach ($locationsGrouped as $city => $districts): ?>
                                    <optgroup label="<?php echo escape_html((string) $city); ?>">
                                        <?php foreach ($districts as $district): ?>
                                            <option value="<?php echo (int) $district['location_id']; ?>" <?php echo $prefillLocationId === (int) $district['location_id'] ? 'selected' : ''; ?>>
                                                <?php echo escape_html((string) $district['district']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="verification-field" id="status-container">
                            <label for="thread-status">Initial Status</label>
                            <select id="thread-status" name="status">
                                <option value="Active" selected>Active</option>
                                <option value="Resolved">Resolved</option>
                                <option value="Archived">Archived</option>
                            </select>
                            <small>Most new threads start Active.</small>
                        </div>

                        <div class="verification-field verification-field--full">
                            <label id="desc-label" for="thread-description">Description / Remarks</label>
                            <textarea id="thread-description" name="description" placeholder="Explain the incident this thread is tracking..." style="min-height: 120px;" required><?php echo escape_html($prefillDescription); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="form-submit-wrapper" style="display: flex; justify-content: flex-end; gap: var(--space-small); margin-top: var(--space-medium);">
                    <button type="button" class="btn-post-report" style="background-color: var(--color3); color: var(--colorText);" onclick="window.location.href='<?php echo $sourceReportId !== null ? 'official-edit-report.php?id=' . $sourceReportId : 'official-home.php'; ?>'">Cancel</button>
                    <button type="submit" id="submit-btn" class="btn-post-report">Create Thread</button>
                </div>
            </form>
        </div>
    </main>
</div>

<script>
    function togglePostType() {
        const isAdvisory = document.querySelector('input[name="post_type"]:checked').value === 'advisory';

        // Toggle visibility of fields
        document.getElementById('category-container').style.display = isAdvisory ? 'none' : 'block';
        document.getElementById('status-container').style.display = isAdvisory ? 'none' : 'block';

        // Update labels and placeholders
        document.getElementById('title-label').textContent = isAdvisory ? 'Headline / Title' : 'Thread Title';
        document.getElementById('thread-title').placeholder = isAdvisory ? 'e.g. Heavy Rain Warning' : 'e.g. Taft Avenue Localized Flooding';

        document.getElementById('desc-label').textContent = isAdvisory ? 'Announcement Details' : 'Description / Remarks';
        document.getElementById('thread-description').placeholder = isAdvisory ? 'Provide details, instructions, or updates for the public...' : 'Explain the incident this thread is tracking...';

        // Update Button Text
        document.getElementById('submit-btn').textContent = isAdvisory ? 'Post Advisory' : 'Create Thread';

        // Toggle required attributes so form validation doesn't block submission
        document.getElementById('thread-category').required = !isAdvisory;
    }
</script>
</body>
</html>