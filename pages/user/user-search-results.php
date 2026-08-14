<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/thread-query.php';
require_once __DIR__ . '/../../includes/report-feed.php';

function escape_html(?string $value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$isAuthenticated = is_authenticated();
$currentUserId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT);
$username = $_SESSION['username'] ?? null;
$safeUsername = escape_html((string) ($username ?? ''));

$loginUrl = '../auth/login.php';
$logoutUrl = '../auth/logout.php';
$registerUrl = '../auth/register.php';
$createReportUrl = $isAuthenticated ? 'user-create-report.php' : $registerUrl;
$myReportsUrl = $isAuthenticated ? 'user-my-reports.php' : $loginUrl;
$nearMeUrl = $isAuthenticated ? 'user-reports-near-me.php' : $loginUrl;
$allThreadsUrl = $isAuthenticated ? 'user-threads.php' : $loginUrl;
$activeThreadsUrl = $isAuthenticated ? 'user-active-threads.php' : $loginUrl;
$resolvedThreadsUrl = $isAuthenticated ? 'user-resolved-threads.php' : $loginUrl;

$searchQuery = trim((string) ($_GET['q'] ?? ''));

$categories = [];
$searchResults = [];
$mediaByReport = [];
$loadError = null;

try {
    $db = thread_db();
    $categories = fetch_categories($db);

    if ($searchQuery !== '') {
        $sql = <<<'SQL'
            SELECT 
                r.report_id, r.thread_id, r.title, r.description, r.status, 
                r.upvote_count, r.comment_count, r.verified_by, r.created_at,
                u.username, u.first_name, u.last_name, 
                c.category_name, l.city, l.district
            FROM reports r
            INNER JOIN users u ON u.user_id = r.user_id
            INNER JOIN categories c ON c.category_id = r.category_id
            INNER JOIN locations l ON l.location_id = r.location_id
            WHERE r.is_deleted = FALSE 
              AND (r.title LIKE ? OR l.city LIKE ? OR l.district LIKE ?)
            ORDER BY r.created_at DESC, r.report_id DESC
        SQL;

        $stmt = $db->prepare($sql);
        $likeQuery = '%' . $searchQuery . '%';
        $stmt->bind_param('sss', $likeQuery, $likeQuery, $likeQuery);
        $stmt->execute();
        $searchResults = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        if ($searchResults !== []) {
            $reportIds = array_map(fn($r) => (int) $r['report_id'], $searchResults);
            $placeholders = implode(',', array_fill(0, count($reportIds), '?'));

            $mediaSql = "SELECT report_id, file_url, file_type FROM media_attachments WHERE report_id IN ($placeholders) ORDER BY report_id ASC, media_id ASC";
            $mediaStmt = $db->prepare($mediaSql);
            $mediaStmt->bind_param(str_repeat('i', count($reportIds)), ...$reportIds);
            $mediaStmt->execute();

            $mediaResult = $mediaStmt->get_result();
            while ($mediaRow = $mediaResult->fetch_assoc()) {
                $reportId = (int) $mediaRow['report_id'];
                $mediaByReport[$reportId][] = [
                    'file_url' => normalize_media_url((string) ($mediaRow['file_url'] ?? '')),
                    'file_type' => strtolower((string) ($mediaRow['file_type'] ?? 'photo')),
                ];
            }
        }
    }
} catch (Throwable $error) {
    $loadError = 'Unable to perform search right now.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - LissentialManila</title>
    <link rel="stylesheet" href="../../style/shared/global.css">
    <link rel="stylesheet" href="../../style/shared/navbar.css">
    <link rel="stylesheet" href="../../style/shared/post.css">
    <link rel="stylesheet" href="../../style/user/home.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
          integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
          crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="../shared-js/media-carousel.js" defer></script>
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
            <input type="search" name="q" placeholder="Search by title or location..." value="<?php echo escape_html($searchQuery); ?>" required>
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
                    <a href="<?php echo $nearMeUrl; ?>">Reports Near Me</a>
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
                    <?php $categoryId = (int) ($category['category_id'] ?? 0); ?>
                    <a href="../../index.php?category=<?php echo $categoryId; ?>" class="sidebar-filter-link">
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

<!--====== SEARCH RESULTS FEED ======-->
<div class="main-wrapper">
    <main style="display: flex; flex-direction: column; gap: var(--space-medium);">
        <div class="my-reports-header" style="text-align: center; padding: var(--space-medium) 0 var(--space-xsmall) 0;">
            <h1 style="font-size: var(--font-large); font-weight: 700; color: var(--colorText); margin-bottom: 4px;">Search Results</h1>
            <p style="font-size: var(--font-small); color: var(--color4); margin-bottom: 8px;">
                <?php if ($searchQuery !== ''): ?>
                    Showing results for "<strong><?php echo escape_html($searchQuery); ?></strong>"
                <?php else: ?>
                    Enter a query to search for incidents
                <?php endif; ?>
            </p>
            <hr style="width: 140px; margin: 0 auto; border: none; height: 1px; background-color: var(--color3);">
        </div>

        <?php if ($loadError !== null): ?>
            <section class="post" style="padding: 16px;">
                <h2 style="margin-bottom: 8px; color: var(--colorRed);">Search Unavailable</h2>
                <p style="margin-bottom: 0;"><?php echo escape_html($loadError); ?></p>
            </section>
        <?php elseif ($searchQuery === ''): ?>
            <section class="post" style="padding: 32px; text-align: center;">
                <i class="fa-solid fa-magnifying-glass" style="font-size: 3rem; color: var(--color3); margin-bottom: 16px;"></i>
                <h2 style="margin-bottom: 8px;">Ready to search</h2>
                <p style="margin-bottom: 0; color: var(--color4);">Type an incident title or location in the search bar above.</p>
            </section>
        <?php elseif ($searchResults === []): ?>
            <section class="post" style="padding: 32px; text-align: center;">
                <i class="fa-solid fa-folder-open" style="font-size: 3rem; color: var(--color3); margin-bottom: 16px;"></i>
                <h2 style="margin-bottom: 8px;">No matching incidents found.</h2>
                <p style="margin-bottom: 0; color: var(--color4);">Try searching with different keywords or broader location names.</p>
            </section>
        <?php else: ?>
            <?php foreach ($searchResults as $report): ?>
                <?php
                $reportId = (int) $report['report_id'];

                // Format Username
                $displayUsername = trim((string) ($report['username'] ?? ''));
                if ($displayUsername === '') {
                    $displayUsername = trim(((string) ($report['first_name'] ?? '')) . ' ' . ((string) ($report['last_name'] ?? '')));
                }
                if ($displayUsername === '') {
                    $displayUsername = 'Anonymous';
                }

                // Format location
                $locationParts = [];
                if (!empty($report['district'])) $locationParts[] = $report['district'];
                if (!empty($report['city'])) $locationParts[] = $report['city'];
                $locationLabel = $locationParts !== [] ? implode(', ', $locationParts) : 'Unknown location';

                // Format Status
                $status = (string) $report['status'];
                $statusUpper = strtoupper($status);
                $statusClassSuffix = strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', $status));
                if ($statusClassSuffix === '') $statusClassSuffix = 'pending';
                $isVerified = ((int) ($report['verified_by'] ?? 0) > 0) || in_array($status, ['Verified', 'Resolved'], true);

                // Format Dates
                $timeLabel = relative_time_label((string) ($report['created_at'] ?? ''));
                $dateTimeLabels = report_date_time_labels((string) ($report['created_at'] ?? ''));

                // Media
                $mediaItems = $mediaByReport[$reportId] ?? [];
                ?>
                <a href="user-report-details.php?id=<?php echo $reportId; ?>" class="post-link">
                    <section class="post">
                        <div class="profile-details">
                            <div class="post-pfp"><img src="../../assets/user_images/user1.jpg" alt="Profile"></div>
                            <span class="username"><?php echo escape_html($displayUsername); ?></span>
                            <span> </span>
                            <span class="hours-ago"><?php echo escape_html($timeLabel); ?></span>
                        </div>

                        <div class="post-details">
                            <div class="post-details-box">
                                <i class="fa-solid fa-location-dot" style="color: var(--colorRed);"></i>
                                <span><?php echo escape_html($locationLabel); ?></span>
                            </div>
                            <div class="post-details-box post-details-box-category">
                                <i class="fa-solid fa-layer-group" style="color: var(--colorYellow);"></i>
                                <span><?php echo escape_html($report['category_name'] ?? 'Uncategorized'); ?></span>
                            </div>
                            <div class="post-details-box">
                                <i class="fa-solid fa-clock" style="color: var(--colorGreen);"></i>
                                <span><?php echo escape_html($dateTimeLabels['date']); ?></span> | <span><?php echo escape_html($dateTimeLabels['time']); ?></span>
                            </div>
                        </div>

                        <div class="post-title-and-description">
                            <h2><span class="post-title"><?php echo escape_html($report['title']); ?></span></h2>
                            <span class="post-description"><?php echo escape_html($report['description']); ?></span>
                        </div>

                        <div class="post-media-carousel">
                            <div class="carousel-container">
                                <?php if ($mediaItems === []): ?>
                                    <div class="carousel-slide">
                                        <img src="../../assets/report_media/media1-1.jfif" alt="No media attached">
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($mediaItems as $media): ?>
                                        <?php $mediaPath = '../../' . $media['file_url']; ?>
                                        <div class="carousel-slide">
                                            <?php if (($media['file_type'] ?? 'photo') === 'video'): ?>
                                                <video src="<?php echo escape_html($mediaPath); ?>" controls muted playsinline></video>
                                            <?php else: ?>
                                                <img src="<?php echo escape_html($mediaPath); ?>" alt="Report attachment">
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
                                <button class="post-upvote" onclick="event.preventDefault();">
                                    <i class="fa-solid fa-square-caret-up"></i>
                                    <span><?php echo (int) $report['upvote_count']; ?></span>
                                </button>
                                <button class="post-comment" onclick="event.preventDefault(); window.location.href='user-report-details.php?id=<?php echo $reportId; ?>#comments';">
                                    <i class="fa-solid fa-comment-dots"></i>
                                    <span><?php echo (int) $report['comment_count']; ?></span>
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
                </a>
                <hr>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
</div>
</body>
</html>