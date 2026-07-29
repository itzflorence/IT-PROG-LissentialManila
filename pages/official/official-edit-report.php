<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/thread-query.php';
require_once __DIR__ . '/../../includes/report-feed.php';
require_once __DIR__ . '/../../includes/official-query.php';

// HTML-escape helper for safe output inside templates
function escape_html(?string $value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Only Officials and Admins may verify/edit user-submitted reports
require_role(['Official', 'Admin'], '../auth/login.php', '../../index.php');

$isAuthenticated = is_authenticated();
$username = $_SESSION['username'] ?? null;
$safeUsername = escape_html((string) ($username ?? ''));
$loginUrl = '../auth/login.php';
$logoutUrl = '../auth/logout.php';

// Report ID determines which report is being reviewed/edited
$reportId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$reportId = $reportId === false ? null : $reportId;

$report = null;
$mediaItems = [];
$comments = [];
$categories = [];
$locationsGrouped = [];
$candidateThreads = [];
$errorMessage = null;

// Flash messaging carried over from official-report-process.php via PRG redirect
$flashSuccess = ($_GET['updated'] ?? '') === '1';
$flashError = trim((string) ($_GET['error'] ?? ''));

if ($reportId === null) {
    http_response_code(400);
    $errorMessage = 'A valid report ID is required.';
} else {
    try {
        $db = thread_db();
        $categories = fetch_categories($db);
        $locationsGrouped = official_fetch_locations_grouped($db);
        $report = official_fetch_report_for_edit($db, $reportId);

        if (!$report) {
            http_response_code(404);
            $errorMessage = 'The report you are trying to review does not exist.';
        } else {
            $mediaItems = official_fetch_media($db, $reportId);
            $comments = official_fetch_comments($db, $reportId);
            $candidateThreads = official_fetch_candidate_threads($db, (int) $report['location_id']);
        }
    } catch (Throwable $error) {
        http_response_code(500);
        $errorMessage = 'Unable to load this report right now. Make sure MySQL is running and the database has been imported.';
    }
}

// Reporter display name for the summary strip
$reporterName = '';
if (is_array($report)) {
    $reporterName = trim((string) ($report['username'] ?? ''));
    if ($reporterName === '') {
        $reporterName = trim(((string) ($report['first_name'] ?? '')) . ' ' . ((string) ($report['last_name'] ?? '')));
    }
    if ($reporterName === '') {
        $reporterName = 'Anonymous';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $report ? escape_html((string) $report['title']) : 'Edit Report'; ?> - LissentialManila</title>

    <link rel="stylesheet" href="../../style/shared/global.css">
    <link rel="stylesheet" href="../../style/shared/navbar.css">
    <link rel="stylesheet" href="../../style/shared/post.css">
    <link rel="stylesheet" href="../../style/user/home.css">
    <link rel="stylesheet" href="../../style/user/create-report.css">
    <link rel="stylesheet" href="../../style/user/report-details.css">
    <link rel="stylesheet" href="../../style/official/official.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
          integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
          crossorigin="anonymous" referrerpolicy="no-referrer" />

    <script src="../shared-js/media-carousel.js" defer></script>
</head>

<body>
<!-- NAVIGATION BAR & SIDEBAR -->
<nav>
    <header class="navbar">
        <div class="navbar-logo">
            <a href="official-home.php">
                <img src="../../assets/LOGO/logo_normal.png" alt="LissentialManila Logo">
            </a>
        </div>

        <div class="searchbar">
            <input type="search" placeholder="Search for a report...">
            <i class="fa-solid fa-magnifying-glass"></i>
        </div>

        <div class="auth-state-pill auth-state-pill--user">
            Logged in as <?php echo $safeUsername; ?> (<?php echo escape_html((string) current_role()); ?>)
        </div>

        <div class="icon-button-wrapper">
            <button type="button" class="icon-button">
                <i class="fa-solid fa-bell"></i>
            </button>

            <button type="button" class="icon-button" title="Log out" onclick="window.location.href='<?php echo $logoutUrl; ?>'">
                <i class="fa-solid fa-user"></i>
            </button>
        </div>
    </header>

    <aside class="sidebar">
        <div class="create-report">
            <button type="button" onclick="window.location.href='#'">CREATE THREAD</button>
        </div>

        <div class="sidebar-options-wrapper">
            <span class="sidebar-title">OFFICIAL ACTIONS</span>
            <div class="sidebar-options">
                <a href="official-home.php">Review Queue</a>
                <a href="#">Assigned Area</a>
                <a href="#">Create Thread</a>
            </div>
            <hr>
        </div>

        <div class="sidebar-options-wrapper">
            <span class="sidebar-title">INCIDENT THREADS</span>
            <div class="sidebar-options">
                <a href="../user/user-threads.php">All</a>
                <a href="../user/user-active-threads.php">Active</a>
                <a href="../user/user-resolved-threads.php">Resolved</a>
                <a href="#">Archived</a>
            </div>
            <hr>
        </div>

        <div class="sidebar-options-wrapper">
            <span class="sidebar-title">GENERAL</span>
            <div class="sidebar-options">
                <a href="../../index.php">Back to Feed</a>
                <a href="#">Account Profile</a>
            </div>
        </div>

        <span class="copyright-footer">IT-PROG © 2026. All rights reserved.</span>
    </aside>
</nav>

<!-- COMMENTS PANEL (read-only context for the official) -->
<aside class="threads-wrapper comments-panel" id="comments">
    <div class="comments-panel__header">
        <h2>Comments</h2>
        <span><?php echo count($comments); ?></span>
    </div>

    <div class="comments-list">
        <?php if ($errorMessage !== null): ?>
            <p class="comments-empty">Comments unavailable.</p>
        <?php elseif ($comments === []): ?>
            <p class="comments-empty">No comments yet on this report.</p>
        <?php else: ?>
            <?php foreach ($comments as $comment): ?>
                <?php
                $commentUser = trim((string) ($comment['username'] ?? ''));
                if ($commentUser === '') {
                    $commentUser = trim(((string) ($comment['first_name'] ?? '')) . ' ' . ((string) ($comment['last_name'] ?? '')));
                }
                if ($commentUser === '') {
                    $commentUser = 'Anonymous';
                }
                ?>
                <article class="comment-card">
                    <div class="comment-card__meta">
                        <strong><?php echo escape_html($commentUser); ?></strong>
                        <span><?php echo escape_html(relative_time_label((string) ($comment['created_at'] ?? ''))); ?></span>
                    </div>
                    <p><?php echo nl2br(escape_html((string) ($comment['comment_text'] ?? ''))); ?></p>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</aside>

<!-- EDIT / VERIFY REPORT FORM -->
<div class="main-wrapper">
    <main>
        <?php if ($errorMessage !== null): ?>
            <section class="post" style="padding: 16px;">
                <h2 style="margin-bottom: 8px;">Report unavailable</h2>
                <p style="margin-bottom: 0;"><?php echo escape_html($errorMessage); ?></p>
            </section>
        <?php else: ?>

            <div class="official-container" style="padding-top: var(--space-medium);">
                <?php if ($flashSuccess): ?>
                    <div class="flash-banner flash-banner--success">
                        <i class="fa-solid fa-circle-check"></i> Report updated successfully.
                    </div>
                <?php endif; ?>

                <?php if ($flashError !== ''): ?>
                    <div class="flash-banner flash-banner--error">
                        <i class="fa-solid fa-triangle-exclamation"></i> <?php echo escape_html($flashError); ?>
                    </div>
                <?php endif; ?>
            </div>

            <form class="create-report-container" action="official-report-process.php" method="POST">
                <input type="hidden" name="report_id" value="<?php echo (int) $report['report_id']; ?>">

                <div class="form-header">
                    <input type="text" class="input-report-title" name="title" value="<?php echo escape_html((string) $report['title']); ?>" maxlength="255" required>
                    <textarea class="input-report-desc" name="description" rows="2" style="resize: vertical;" required><?php echo escape_html((string) $report['description']); ?></textarea>
                </div>

                <div class="form-meta-row">
                    <div class="meta-pill">
                        <i class="fa-solid fa-layer-group category-icon"></i>
                        <label for="category-select">CATEGORY:</label>
                        <select id="category-select" name="category_id" required>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo (int) $category['category_id']; ?>" <?php echo (int) $report['category_id'] === (int) $category['category_id'] ? 'selected' : ''; ?>>
                                    <?php echo escape_html((string) $category['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="meta-pill">
                        <i class="fa-solid fa-location-dot category-icon"></i>
                        <label for="location-select">LOCATION:</label>
                        <select id="location-select" name="location_id" required>
                            <?php foreach ($locationsGrouped as $city => $districts): ?>
                                <optgroup label="<?php echo escape_html((string) $city); ?>">
                                    <?php foreach ($districts as $district): ?>
                                        <option value="<?php echo (int) $district['location_id']; ?>" <?php echo (int) $report['location_id'] === (int) $district['location_id'] ? 'selected' : ''; ?>>
                                            <?php echo escape_html((string) $district['district']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="report-media-preview">
                    <div class="carousel-container">
                        <?php if ($mediaItems === []): ?>
                            <div class="carousel-slide">
                                <img src="../../assets/report_media/media1-1.jfif" alt="No media attached">
                            </div>
                        <?php else: ?>
                            <?php foreach ($mediaItems as $media): ?>
                                <div class="carousel-slide">
                                    <?php if (($media['file_type'] ?? 'photo') === 'video'): ?>
                                        <video src="../../<?php echo escape_html((string) $media['file_url']); ?>" controls muted playsinline></video>
                                    <?php else: ?>
                                        <img src="../../<?php echo escape_html((string) $media['file_url']); ?>" alt="Report attachment">
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <?php if (count($mediaItems) > 1): ?>
                        <button type="button" class="carousel-btn prev" aria-label="Previous slide" onclick="moveCarousel(this, -1)">
                            <i class="fa-solid fa-chevron-left"></i>
                        </button>
                        <button type="button" class="carousel-btn next" aria-label="Next slide" onclick="moveCarousel(this, 1)">
                            <i class="fa-solid fa-chevron-right"></i>
                        </button>
                    <?php endif; ?>
                </div>

                <div class="reporter-summary">
                    <div class="reporter-summary-item">
                        <span>Reported by</span>
                        <span><?php echo escape_html($reporterName); ?></span>
                    </div>
                    <div class="reporter-summary-item">
                        <span>Submitted</span>
                        <span><?php echo escape_html(report_date_time_labels((string) ($report['created_at'] ?? ''))['date']); ?> at <?php echo escape_html(report_date_time_labels((string) ($report['created_at'] ?? ''))['time']); ?></span>
                    </div>
                    <div class="reporter-summary-item">
                        <span>Upvotes / Comments</span>
                        <span><?php echo (int) ($report['upvote_count'] ?? 0); ?> / <?php echo (int) ($report['comment_count'] ?? 0); ?></span>
                    </div>
                    <div class="reporter-summary-item">
                        <span>Currently linked thread</span>
                        <span>
                            <?php if (!empty($report['thread_id'])): ?>
                                <a href="../user/thread-details.php?id=<?php echo (int) $report['thread_id']; ?>"><?php echo escape_html((string) $report['thread_title']); ?></a>
                                (<?php echo escape_html((string) $report['thread_status']); ?>)
                            <?php else: ?>
                                — None yet —
                            <?php endif; ?>
                        </span>
                    </div>
                </div>

                <div class="verification-panel">
                    <h3><i class="fa-solid fa-shield-halved"></i> Verification &amp; Thread Assignment</h3>

                    <div class="verification-grid">
                        <div class="verification-field">
                            <label for="status-select">Status</label>
                            <select id="status-select" name="status" required>
                                <?php foreach (OFFICIAL_REPORT_STATUSES as $statusOption): ?>
                                    <option value="<?php echo escape_html($statusOption); ?>" <?php echo (string) $report['status'] === $statusOption ? 'selected' : ''; ?>>
                                        <?php echo escape_html($statusOption); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small>Rejecting a report requires a remark explaining why.</small>
                        </div>

                        <div class="verification-field">
                            <label for="thread-select">Assign to Thread</label>
                            <select id="thread-select" name="thread_id">
                                <option value="">— No Thread —</option>
                                <?php foreach ($candidateThreads as $thread): ?>
                                    <option value="<?php echo (int) $thread['thread_id']; ?>" <?php echo (int) ($report['thread_id'] ?? 0) === (int) $thread['thread_id'] ? 'selected' : ''; ?>>
                                        <?php echo escape_html((string) $thread['title']); ?> (<?php echo escape_html((string) $thread['status']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small>
                                <?php echo $candidateThreads === [] ? 'No existing threads at this location yet.' : 'Only threads at the selected location are listed.'; ?>
                                Need a new one? Use <a href="#">Create Thread</a>.
                            </small>
                        </div>

                        <div class="verification-field verification-field--full">
                            <label for="verification-remarks">Verification Remarks</label>
                            <textarea id="verification-remarks" name="verification_remarks" placeholder="e.g. Confirmed via CCTV footage, or reason for rejection..."><?php echo escape_html((string) ($report['verification_remarks'] ?? '')); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="form-submit-wrapper" style="gap: var(--space-small);">
                    <button type="button" class="btn-post-report" onclick="window.location.href='official-home.php'" style="background-color: var(--color3); color: var(--colorText);">Cancel</button>
                    <button type="submit" class="btn-post-report">Save Changes</button>
                </div>

            </form>
        <?php endif; ?>
    </main>
</div>

<script>
    // Reject requires a remark, mirrored client-side; the server enforces this too.
    document.querySelector('form.create-report-container')?.addEventListener('submit', (event) => {
        const status = document.getElementById('status-select').value;
        const remarks = document.getElementById('verification-remarks').value.trim();

        if (status === 'Rejected' && remarks === '') {
            event.preventDefault();
            alert('Please add a remark explaining why this report is being rejected.');
            document.getElementById('verification-remarks').focus();
        }
    });
</script>
</body>

</html>