<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/thread-query.php';
require_once __DIR__ . '/../../includes/report-feed.php';

// Block this page unless the visitor is authenticated.
require_login('../auth/login.php');

// HTML-escape helper for safe output inside templates.
function escape_html(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Current auth/session state used by the shared navbar/sidebar.
$isAuthenticated = is_authenticated();

$currentUserId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$currentUserId = $currentUserId === false ? null : $currentUserId;

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

// Loaded once for sidebar category rendering
$categories = [];

// Report ID determines which report details and comments to load
$reportId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);
$reportId = $reportId === false ? null : $reportId;

$report = null;
$mediaItems = [];
$comments = [];
$errorMessage = null;
$commentError = null;
$commentDraft = '';
$hasUpvoted = false;
$hasResolved = false;

if ($reportId === null) {
    http_response_code(400);
    $errorMessage = 'A valid report ID is required.';
} else {
    try {
        $db = thread_db();
        $categories = fetch_categories($db);

        // Handle comment submission on the same page before reading fresh data
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $postAction = trim((string) ($_POST['action'] ?? 'add_comment'));

            if ($postAction === 'delete_comment') {
                $postedReportId = filter_input(
                    INPUT_POST,
                    'report_id',
                    FILTER_VALIDATE_INT,
                    ['options' => ['min_range' => 1]]
                );
                $postedReportId = $postedReportId === false ? null : $postedReportId;
                $commentId = filter_input(
                    INPUT_POST,
                    'comment_id',
                    FILTER_VALIDATE_INT,
                    ['options' => ['min_range' => 1]]
                );
                $commentId = $commentId === false ? null : $commentId;

                if ($currentUserId === null) {
                    $commentError = 'Your session expired. Please log in again.';
                } elseif ($postedReportId !== $reportId || $commentId === null) {
                    $commentError = 'Invalid comment reference.';
                } else {
                    // Soft-delete only the logged-in user's own comment on this report.
                    $deleteComment = $db->prepare(
                        'UPDATE comments
                         SET is_deleted = TRUE
                         WHERE comment_id = ?
                           AND report_id = ?
                           AND user_id = ?
                           AND is_deleted = FALSE'
                    );
                    $deleteComment->bind_param('iii', $commentId, $reportId, $currentUserId);
                    $deleteComment->execute();

                    if ($deleteComment->affected_rows < 1) {
                        $commentError = 'Unable to delete that comment.';
                    } else {
                        $refreshCommentCount = $db->prepare('UPDATE reports SET comment_count = (SELECT COUNT(*) FROM comments WHERE report_id = ? AND is_deleted = FALSE) WHERE report_id = ?');
                        $refreshCommentCount->bind_param('ii', $reportId, $reportId);
                        $refreshCommentCount->execute();

                        // PRG pattern: redirect to avoid duplicate form resubmissions
                        header('Location: user-report-details.php?id=' . $reportId . '#comments');
                        exit;
                    }
                }
            } else {
                $postedReportId = filter_input(
                    INPUT_POST,
                    'report_id',
                    FILTER_VALIDATE_INT,
                    ['options' => ['min_range' => 1]]
                );
                $postedReportId = $postedReportId === false ? null : $postedReportId;
                $commentText = trim((string) ($_POST['comment_text'] ?? ''));
                $commentDraft = $commentText;

                // Guardrails for comment input/session integrity
                if ($currentUserId === null) {
                    $commentError = 'Your session expired. Please log in again.';
                } elseif ($postedReportId !== $reportId) {
                    $commentError = 'Invalid report reference.';
                } elseif ($commentText === '') {
                    $commentError = 'Comment cannot be empty.';
                } elseif ((function_exists('mb_strlen') ? mb_strlen($commentText) : strlen($commentText)) > 1000) {
                    $commentError = 'Comment is too long. Maximum is 1000 characters.';
                } else {
                    // Insert only if the report still exists and is not deleted
                    $insertComment = $db->prepare(
                        'INSERT INTO comments (user_id, report_id, comment_text)
                         SELECT ?, ?, ?
                         FROM reports
                         WHERE report_id = ?
                           AND is_deleted = FALSE'
                    );
                    $insertComment->bind_param('iisi', $currentUserId, $reportId, $commentText, $reportId);
                    $insertComment->execute();

                    if ($insertComment->affected_rows < 1) {
                        $commentError = 'This report is unavailable for comments.';
                    } else {
                        // Keep report.comment_count aligned with actual comment rows
                        $refreshCommentCount = $db->prepare('UPDATE reports SET comment_count = (SELECT COUNT(*) FROM comments WHERE report_id = ? AND is_deleted = FALSE) WHERE report_id = ?');
                        $refreshCommentCount->bind_param('ii', $reportId, $reportId);
                        $refreshCommentCount->execute();

                        // PRG pattern: redirect to avoid duplicate form resubmissions
                        header('Location: user-report-details.php?id=' . $reportId . '#comments');
                        exit;
                    }
                }
            }
        }

        // Main report payload for the details card
        $reportSql = <<<'SQL'
            SELECT
                r.report_id,
                r.thread_id,
                r.title,
                r.description,
                r.status,
                r.upvote_count,
                r.comment_count,
                r.resolved_count,
                r.verified_by,
                r.created_at,
                u.username,
                u.first_name,
                u.last_name,
                c.category_name,
                l.city,
                l.district
            FROM reports r
            INNER JOIN users u ON u.user_id = r.user_id
            INNER JOIN categories c ON c.category_id = r.category_id
            INNER JOIN locations l ON l.location_id = r.location_id
            WHERE r.report_id = ?
              AND r.is_deleted = FALSE
            LIMIT 1
        SQL;

        $reportStatement = $db->prepare($reportSql);
        $reportStatement->bind_param('i', $reportId);
        $reportStatement->execute();
        $report = $reportStatement->get_result()->fetch_assoc();

        if (!$report) {
            http_response_code(404);
            $errorMessage = 'The report you requested does not exist.';
        } else {
            // Report media (images/videos) for the carousel, capped at REPORT_MEDIA_DISPLAY_LIMIT
            $mediaStatement = $db->prepare('SELECT file_url, file_type FROM media_attachments WHERE report_id = ? ORDER BY media_id ASC LIMIT ' . REPORT_MEDIA_DISPLAY_LIMIT);
            $mediaStatement->bind_param('i', $reportId);
            $mediaStatement->execute();

            $mediaResult = $mediaStatement->get_result();
            while ($row = $mediaResult->fetch_assoc()) {
                $mediaItems[] = [
                    'file_url' => normalize_media_url((string) ($row['file_url'] ?? '')),
                    'file_type' => strtolower((string) ($row['file_type'] ?? 'photo')),
                ];
            }

            // Whether the current viewer has already upvoted/marked this report resolved
            if ($currentUserId !== null) {
                $upvotedStatement = $db->prepare('SELECT 1 FROM upvotes WHERE user_id = ? AND report_id = ? LIMIT 1');
                $upvotedStatement->bind_param('ii', $currentUserId, $reportId);
                $upvotedStatement->execute();
                $hasUpvoted = (bool) $upvotedStatement->get_result()->fetch_row();

                $resolvedStatement = $db->prepare('SELECT 1 FROM resolved_marks WHERE user_id = ? AND report_id = ? LIMIT 1');
                $resolvedStatement->bind_param('ii', $currentUserId, $reportId);
                $resolvedStatement->execute();
                $hasResolved = (bool) $resolvedStatement->get_result()->fetch_row();
            }

            // Comment thread for the right-side panel
            $commentsStatement = $db->prepare(
                'SELECT c.comment_id, c.user_id, c.comment_text, c.created_at, u.username, u.first_name, u.last_name
                 FROM comments c
                 INNER JOIN users u ON u.user_id = c.user_id
                 WHERE c.report_id = ?
                   AND c.is_deleted = FALSE
                 ORDER BY c.created_at ASC, c.comment_id ASC'
            );
            $commentsStatement->bind_param('i', $reportId);
            $commentsStatement->execute();
            $comments = $commentsStatement->get_result()->fetch_all(MYSQLI_ASSOC);
        }
    } catch (Throwable $error) {
        http_response_code(500);
        $errorMessage = 'Unable to load this report right now.';
    }
}

// Derived view-model values so template markup stays simple
$displayUsername = '';
$locationLabel = 'Unknown location';
$status = 'Pending';
$statusUpper = 'PENDING';
$statusClassSuffix = 'pending';
$isVerified = false;
$timeLabel = 'Unknown time';
$dateTimeLabels = ['date' => 'Unknown date', 'time' => '--:--'];

if (is_array($report)) {
    $displayUsername = trim((string) ($report['username'] ?? ''));
    if ($displayUsername === '') {
        $displayUsername = trim(((string) ($report['first_name'] ?? '')) . ' ' . ((string) ($report['last_name'] ?? '')));
    }
    if ($displayUsername === '') {
        $displayUsername = 'Anonymous';
    }

    $locationParts = [];
    if (!empty($report['district'])) {
        $locationParts[] = (string) $report['district'];
    }
    if (!empty($report['city'])) {
        $locationParts[] = (string) $report['city'];
    }
    if ($locationParts !== []) {
        $locationLabel = implode(', ', $locationParts);
    }

    $status = (string) ($report['status'] ?? 'Pending');
    $statusUpper = strtoupper($status);
    $statusClassSuffix = strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', $status));
    if ($statusClassSuffix === '') {
        $statusClassSuffix = 'pending';
    }
    $isVerified = ((int) ($report['verified_by'] ?? 0) > 0) || in_array($status, ['Verified', 'Resolved'], true);
    $timeLabel = relative_time_label((string) ($report['created_at'] ?? ''));
    $dateTimeLabels = report_date_time_labels((string) ($report['created_at'] ?? ''));
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escape_html((string) ($report['title'] ?? 'Report Details')); ?> - LissentialManila</title>
    <link rel="stylesheet" href="../../style/shared/global.css">
    <link rel="stylesheet" href="../../style/shared/navbar.css">
    <link rel="stylesheet" href="../../style/user/home.css">
    <link rel="stylesheet" href="../../style/user/report-details.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="../../style/shared/post.css">

    <script src="../shared-js/media-carousel.js" defer></script>
    <script src="../shared-js/report-actions.js" defer></script>
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
                <button type="submit" class="search-btn" aria-label="Submit search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
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
                    <a href="user-reports-near-me.php">Reports Near Me</a>
                    <a href="user-saved-locations.php">Saved Locations</a>
                    <a href="user-my-comments.php">My Comments</a>
                    <a href="user-profile.php">Account Profile</a>
                </div>
                <hr>
            </div>
            <?php endif; ?>

            <div class="sidebar-options-wrapper">
                <span class="sidebar-title">THREADS</span>
                <div class="sidebar-options">
                    <a href="<?php echo $allThreadsUrl; ?>">All</a>
                    <a href="<?php echo $activeThreadsUrl; ?>">Active</a>
                    <a href="<?php echo $resolvedThreadsUrl; ?>">Resolved</a>
                </div>
                <hr>
            </div>

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
            </div>

            <span class="copyright-footer">IT-PROG © 2026. All rights reserved.</span>
        </aside>
    </nav>

    <aside class="threads-wrapper comments-panel" id="comments">
        <div class="comments-panel__header">
            <h2>Comments</h2>
            <span><?php echo count($comments); ?></span>
        </div>

        <?php if ($errorMessage === null && $reportId !== null): ?>
            <form method="post" class="comment-form">
                <input type="hidden" name="report_id" value="<?php echo (int) $reportId; ?>">
                <label for="comment_text">Add a comment</label>
                <textarea id="comment_text" name="comment_text" rows="3" maxlength="1000" placeholder="Share updates or helpful context..."><?php echo escape_html($commentDraft); ?></textarea>
                <?php if ($commentError !== null): ?>
                    <p class="comment-form__error"><?php echo escape_html($commentError); ?></p>
                <?php endif; ?>
                <button type="submit">Post Comment</button>
            </form>
        <?php endif; ?>

        <div class="comments-list">
            <?php if ($errorMessage !== null): ?>
                <p class="comments-empty"><?php echo escape_html($errorMessage); ?></p>
            <?php elseif ($comments === []): ?>
                <p class="comments-empty">No comments yet. Be the first to add one.</p>
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
                        <?php if ($currentUserId !== null && (int) ($comment['user_id'] ?? 0) === $currentUserId): ?>
                            <form method="post" class="comment-delete-form" onsubmit="return confirm('Delete this comment?');">
                                <input type="hidden" name="action" value="delete_comment">
                                <input type="hidden" name="report_id" value="<?php echo (int) $reportId; ?>">
                                <input type="hidden" name="comment_id" value="<?php echo (int) ($comment['comment_id'] ?? 0); ?>">
                                <button type="submit" class="comment-delete-button">Delete</button>
                            </form>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </aside>

    <div class="main-wrapper">
        <main>
            <?php if ($errorMessage !== null): ?>
                <section class="post" style="padding: 16px;">
                    <h2 style="margin-bottom: 8px;">Report unavailable</h2>
                    <p style="margin-bottom: 0;"><?php echo escape_html($errorMessage); ?></p>
                </section>
            <?php else: ?>
                <section class="post">
                    <div class="profile-details">
                        <!-- <div class="post-pfp"><img src="../../assets/user_images/user1.jpg" alt=""></div> -->
                        <span class="username"><?php echo escape_html($displayUsername); ?></span>
                        <span>•</span>
                        <span class="hours-ago"><?php echo escape_html($timeLabel); ?></span>
                    </div>

                    <div class="post-details">
                        <div class="post-details-box">
                            <i class="fa-solid fa-location-dot" style="color: var(--colorRed);"></i>
                            <span><?php echo escape_html($locationLabel); ?></span>
                        </div>

                        <div class="post-details-box post-details-box-category">
                            <i class="fa-solid fa-layer-group" style="color: var(--colorYellow);"></i>
                            <span class="post-category-badge"><?php echo escape_html((string) ($report['category_name'] ?? 'Uncategorized')); ?></span>
                        </div>

                        <div class="post-details-box">
                            <i class="fa-solid fa-clock" style="color: var(--colorGreen);"></i>
                            <span><?php echo escape_html($dateTimeLabels['date']); ?></span> | <span><?php echo escape_html($dateTimeLabels['time']); ?></span>
                        </div>
                    </div>

                    <div class="post-title-and-description">
                        <h2><span class="post-title"><?php echo escape_html((string) ($report['title'] ?? 'Untitled report')); ?></span></h2>
                        <span class="post-description"><?php echo escape_html((string) ($report['description'] ?? '')); ?></span>
                    </div>

                    <div class="post-media-carousel">
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
                            <button class="carousel-btn prev" aria-label="Previous slide" onclick="moveCarousel(this, -1)">
                                <i class="fa-solid fa-chevron-left"></i>
                            </button>
                            <button class="carousel-btn next" aria-label="Next slide" onclick="moveCarousel(this, 1)">
                                <i class="fa-solid fa-chevron-right"></i>
                            </button>
                        <?php endif; ?>
                    </div>

                    <div class="post-buttons">
                        <div class="post-buttons-left">
                            <button type="button" class="post-upvote<?php echo $hasUpvoted ? ' is-active' : ''; ?>" data-report-action="upvote" data-report-id="<?php echo (int) $reportId; ?>" data-action-url="report-action.php" aria-pressed="<?php echo $hasUpvoted ? 'true' : 'false'; ?>">
                                <i class="fa-solid fa-square-caret-up"></i>
                                <span><?php echo (int) ($report['upvote_count'] ?? 0); ?></span>
                            </button>
                            <button type="button" class="comment post-comment" onclick="window.location.href='#comments'">
                                <i class="fa-solid fa-comment-dots"></i>
                                <span><?php echo (int) ($report['comment_count'] ?? 0); ?></span>
                            </button>
                            <button type="button" class="post-resolved<?php echo $hasResolved ? ' is-active' : ''; ?>" data-report-action="resolved" data-report-id="<?php echo (int) $reportId; ?>" data-action-url="report-action.php" aria-pressed="<?php echo $hasResolved ? 'true' : 'false'; ?>">
                                <i class="fa-solid fa-circle-check"></i>
                                Resolved | <span><?php echo (int) ($report['resolved_count'] ?? 0); ?></span>
                            </button>
                        </div>

                        <div class="post-buttons-right">
                            <?php if ($isVerified): ?>
                                <button class="verified">
                                    <i class="fa-solid fa-user-check"></i>
                                    Verified by Officials
                                </button>
                            <?php endif; ?>
                            <button class="status status-pill status-<?php echo escape_html($statusClassSuffix); ?>">
                                Status: <?php echo escape_html($statusUpper); ?>
                            </button>
                        </div>
                    </div>
                </section>
            <?php endif; ?>
        </main>
    </div>
<script src="../shared-js/notifications.js" defer></script>
<script src="../shared-js/navbar-user-menu.js" defer></script>
</body>

</html>
