<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/thread-query.php';
require_once __DIR__ . '/../../includes/report-feed.php';
require_once __DIR__ . '/../../includes/admin-query.php';

require_role(['Admin'], '../auth/login.php', '../../index.php');

$username = $_SESSION['username'] ?? null;
$safeUsername = thread_escape((string) ($username ?? ''));
$logoutUrl = '../auth/logout.php';

$loadError = null;
$userCounts = [];
$reportStatusCounts = [];
$threadCounts = [];
$reportsByCategory = [];
$reportsTimeseries = [];
$topLocations = [];
$recentActivity = [];
$platformTotals = [];

try {
    $db = thread_db();
    $userCounts = admin_fetch_user_counts($db);
    $reportStatusCounts = admin_fetch_report_status_counts($db);
    $threadCounts = thread_status_counts($db);
    $reportsByCategory = admin_fetch_reports_by_category($db);
    $reportsTimeseries = admin_fetch_reports_timeseries($db, 14);
    $topLocations = admin_fetch_top_locations($db, 5);
    $recentActivity = admin_fetch_recent_audit_logs($db, 10);
    $platformTotals = admin_fetch_platform_totals($db);
} catch (Throwable $error) {
    $loadError = 'Unable to load analytics right now. Please make sure MySQL is running and the database has been imported.';
}

$totalUsersActive = 0;
$totalUsersDeleted = 0;
foreach ($userCounts as $roleCounts) {
    $totalUsersActive += $roleCounts['active'];
    $totalUsersDeleted += $roleCounts['deleted'];
}

$totalReports = array_sum($reportStatusCounts);
$pendingReports = $reportStatusCounts['Pending'] ?? 0;
$activeThreads = $threadCounts['Active'] ?? 0;

$reportStatusColors = [
    'Pending' => '#febc01',
    'Verified' => '#5ca3ff',
    'Resolved' => '#34622f',
    'Rejected' => '#cd474b',
];

$threadStatusColors = [
    'Active' => '#34622f',
    'Resolved' => '#5ca3ff',
    'Archived' => '#a0a0a0',
];

$categoryPalette = ['#5ca3ff', '#febc01', '#34622f', '#cd474b', '#8e7cc3', '#ff9f6b', '#4bc0c0', '#c9cbcf', '#9966ff'];

function activity_icon_for_action(string $action): string
{
    if (str_contains($action, 'User')) return 'fa-user-gear';
    if (str_contains($action, 'Report')) return 'fa-flag';
    if (str_contains($action, 'Thread')) return 'fa-layer-group';
    if (str_contains($action, 'Advisory')) return 'fa-bullhorn';
    return 'fa-clock-rotate-left';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Platform Analytics - LissentialManila</title>

    <link rel="stylesheet" href="../../style/shared/global.css">
    <link rel="stylesheet" href="../../style/shared/navbar.css">
    <link rel="stylesheet" href="../../style/user/home.css">
    <link rel="stylesheet" href="../../style/official/official.css">
    <link rel="stylesheet" href="../../style/admin/platform-analytics.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
          integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
          crossorigin="anonymous" referrerpolicy="no-referrer" />

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.4/chart.umd.min.js" defer></script>
</head>

<body>
<nav>
    <header class="navbar">
        <div class="navbar-logo">
            <a href="admin-manage-accounts.php">
                <img src="../../assets/LOGO/logo_normal.png" alt="LissentialManila Logo">
            </a>
        </div>

        <div class="searchbar">
            <input type="search" placeholder="Search what you need...">
            <i class="fa-solid fa-magnifying-glass"></i>
        </div>

        <div class="auth-state-pill auth-state-pill--user">
            Logged in as <?= $safeUsername ?> (Admin)
        </div>

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
                    <span class="user-menu-name"><?= htmlspecialchars((string) ($_SESSION['full_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?: $safeUsername ?></span>
                    <span class="user-menu-username">@<?= $safeUsername ?></span>
                </div>
                <a class="user-menu-logout" href="<?= thread_escape($logoutUrl) ?>">
                    <i class="fa-solid fa-right-from-bracket"></i> Log out
                </a>
            </div>
        </div>
    </header>

    <aside class="sidebar">
        <div class="sidebar-options-wrapper">
            <span class="sidebar-title">ADMINISTRATION</span>
            <div class="sidebar-options">
                <a href="admin-manage-accounts.php">Manage Accounts</a>
                <a href="admin-platform-analytics.php" style="font-weight: bold;">Platform Analytics</a>
            </div>
            <hr>
        </div>

        <div class="sidebar-options-wrapper">
            <span class="sidebar-title">GENERAL</span>
            <div class="sidebar-options">
                <a href="../user/user-profile.php">Account Profile</a>
            </div>
        </div>

        <span class="copyright-footer">IT-PROG © 2026. All rights reserved.</span>
    </aside>
</nav>

<div class="main-wrapper" style="margin-right: 0;">
    <main style="align-items: stretch;">
        <div class="analytics-container">

            <div class="analytics-header">
                <h1>Platform Analytics</h1>
                <p>Live overview of accounts, reports, threads, and community engagement.</p>
            </div>

            <?php if ($loadError !== null): ?>
                <div class="flash-banner flash-banner--error">
                    <i class="fa-solid fa-triangle-exclamation"></i> <?= thread_escape($loadError) ?>
                </div>
            <?php else: ?>

                <!-- KPI CARDS -->
                <div class="kpi-grid">
                    <div class="kpi-card">
                        <div class="kpi-card-icon kpi-blue"><i class="fa-solid fa-users"></i></div>
                        <span class="kpi-card-value"><?= $totalUsersActive ?></span>
                        <span class="kpi-card-label">Active Accounts</span>
                        <span class="kpi-card-sub"><?= $totalUsersDeleted ?> deactivated</span>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-card-icon kpi-yellow"><i class="fa-solid fa-hourglass-half"></i></div>
                        <span class="kpi-card-value"><?= $pendingReports ?></span>
                        <span class="kpi-card-label">Pending Reports</span>
                        <span class="kpi-card-sub">of <?= $totalReports ?> total reports</span>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-card-icon kpi-green"><i class="fa-solid fa-layer-group"></i></div>
                        <span class="kpi-card-value"><?= $activeThreads ?></span>
                        <span class="kpi-card-label">Active Threads</span>
                        <span class="kpi-card-sub"><?= $threadCounts['all'] ?? 0 ?> threads total</span>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-card-icon kpi-red"><i class="fa-solid fa-bullhorn"></i></div>
                        <span class="kpi-card-value"><?= $platformTotals['active_advisories'] ?? 0 ?></span>
                        <span class="kpi-card-label">Active Advisories</span>
                        <span class="kpi-card-sub">currently published</span>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-card-icon kpi-blue"><i class="fa-solid fa-comments"></i></div>
                        <span class="kpi-card-value"><?= $platformTotals['total_comments'] ?? 0 ?></span>
                        <span class="kpi-card-label">Total Comments</span>
                        <span class="kpi-card-sub"><?= $platformTotals['total_upvotes'] ?? 0 ?> total upvotes</span>
                    </div>
                </div>

                <!-- CHARTS -->
                <div class="chart-grid">
                    <div class="chart-card chart-card--wide">
                        <h3>Reports Submitted (Last 14 Days)</h3>
                        <p class="chart-card-sub">Daily volume of newly submitted incident reports.</p>
                        <div class="chart-canvas-wrapper">
                            <canvas id="chart-reports-trend"></canvas>
                        </div>
                    </div>

                    <div class="chart-card">
                        <h3>Reports by Status</h3>
                        <div class="chart-canvas-wrapper">
                            <canvas id="chart-report-status"></canvas>
                        </div>
                    </div>

                    <div class="chart-card">
                        <h3>Threads by Status</h3>
                        <div class="chart-canvas-wrapper">
                            <canvas id="chart-thread-status"></canvas>
                        </div>
                    </div>

                    <div class="chart-card">
                        <h3>Reports by Category</h3>
                        <div class="chart-canvas-wrapper">
                            <canvas id="chart-category"></canvas>
                        </div>
                    </div>

                    <div class="chart-card">
                        <h3>Top 5 Incident Hotspots</h3>
                        <div class="chart-canvas-wrapper">
                            <canvas id="chart-locations"></canvas>
                        </div>
                    </div>
                </div>

                <!-- RECENT ACTIVITY -->
                <div class="chart-card">
                    <h3>Recent Admin / Official Activity</h3>
                    <p class="chart-card-sub">Latest entries from the audit log.</p>
                    <?php if ($recentActivity === []): ?>
                        <div class="chart-empty-note">No audit log activity yet.</div>
                    <?php else: ?>
                        <div class="activity-list">
                            <?php foreach ($recentActivity as $entry): ?>
                                <div class="activity-item">
                                    <div class="activity-icon"><i class="fa-solid <?= activity_icon_for_action((string) $entry['action']) ?>"></i></div>
                                    <div class="activity-body">
                                        <strong><?= thread_escape((string) $entry['action']) ?></strong>
                                        <span>
                                            by @<?= thread_escape((string) $entry['username']) ?> (<?= thread_escape((string) $entry['role']) ?>)
                                            &mdash; <?= thread_escape((string) $entry['description']) ?>
                                        </span>
                                    </div>
                                    <span class="activity-time"><?= thread_escape(relative_time_label((string) $entry['created_at'])) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            <?php endif; ?>
        </div>
    </main>
</div>

<?php if ($loadError === null): ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const reportsTrend = <?= json_encode($reportsTimeseries) ?>;
    const reportStatusCounts = <?= json_encode($reportStatusCounts) ?>;
    const reportStatusColors = <?= json_encode($reportStatusColors) ?>;
    const threadCounts = <?= json_encode($threadCounts) ?>;
    const threadStatusColors = <?= json_encode($threadStatusColors) ?>;
    const reportsByCategory = <?= json_encode($reportsByCategory) ?>;
    const categoryPalette = <?= json_encode($categoryPalette) ?>;
    const topLocations = <?= json_encode($topLocations) ?>;

    const gridColor = '#ebebeb';
    const textColor = '#707070';
    Chart.defaults.font.family = "'Google Sans', sans-serif";
    Chart.defaults.color = textColor;

    // Reports trend (line)
    new Chart(document.getElementById('chart-reports-trend'), {
        type: 'line',
        data: {
            labels: reportsTrend.map(p => new Date(p.date + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric' })),
            datasets: [{
                label: 'Reports',
                data: reportsTrend.map(p => p.total),
                borderColor: '#5ca3ff',
                backgroundColor: 'rgba(92, 163, 255, 0.15)',
                fill: true,
                tension: 0.3,
                pointRadius: 3,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: gridColor } },
                x: { grid: { display: false } }
            }
        }
    });

    // Reports by status (doughnut)
    const statusLabels = Object.keys(reportStatusCounts);
    new Chart(document.getElementById('chart-report-status'), {
        type: 'doughnut',
        data: {
            labels: statusLabels,
            datasets: [{
                data: statusLabels.map(s => reportStatusCounts[s]),
                backgroundColor: statusLabels.map(s => reportStatusColors[s]),
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } }
        }
    });

    // Threads by status (doughnut)
    const threadLabels = ['Active', 'Resolved', 'Archived'];
    new Chart(document.getElementById('chart-thread-status'), {
        type: 'doughnut',
        data: {
            labels: threadLabels,
            datasets: [{
                data: threadLabels.map(s => threadCounts[s] || 0),
                backgroundColor: threadLabels.map(s => threadStatusColors[s]),
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } }
        }
    });

    // Reports by category (bar)
    new Chart(document.getElementById('chart-category'), {
        type: 'bar',
        data: {
            labels: reportsByCategory.map(c => c.category_name),
            datasets: [{
                label: 'Reports',
                data: reportsByCategory.map(c => c.total),
                backgroundColor: reportsByCategory.map((_, i) => categoryPalette[i % categoryPalette.length]),
                borderRadius: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: gridColor } },
                x: { grid: { display: false }, ticks: { autoSkip: false, maxRotation: 45, minRotation: 30 } }
            }
        }
    });

    // Top locations (horizontal bar)
    new Chart(document.getElementById('chart-locations'), {
        type: 'bar',
        data: {
            labels: topLocations.map(l => l.district + ', ' + l.city),
            datasets: [{
                label: 'Reports',
                data: topLocations.map(l => l.total),
                backgroundColor: '#cd474b',
                borderRadius: 6,
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: gridColor } },
                y: { grid: { display: false } }
            }
        }
    });
});
</script>
<?php endif; ?>

<script src="../shared-js/notifications.js" defer></script>
<script src="../shared-js/navbar-user-menu.js" defer></script>
</body>
</html>
