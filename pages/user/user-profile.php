<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/thread-query.php';
require_once __DIR__ . '/../../includes/report-feed.php';
require_once __DIR__ . '/../../includes/official-query.php';
require_once __DIR__ . '/../../includes/admin-query.php';
require_once __DIR__ . '/../../includes/profile-query.php';

require_login('../auth/login.php');

$currentUserId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$currentUserId = $currentUserId === false ? null : $currentUserId;
$role = current_role();

$logoutUrl = '../auth/logout.php';

$successFlag = trim((string) ($_GET['success'] ?? ''));
$errorFlag = trim((string) ($_GET['error'] ?? ''));
$devCode = trim((string) ($_GET['devcode'] ?? ''));

$successMessages = [
    'info_updated' => 'Profile information updated.',
    'password_updated' => 'Password changed successfully.',
    'phone_requested' => 'Verification code generated for your new phone number.',
    'phone_confirmed' => 'Phone number updated and verified.',
    'phone_cancelled' => 'Pending phone number change cancelled.',
];

$account = null;
$locationsGrouped = [];
$loadError = null;

try {
    $db = thread_db();
    if ($currentUserId !== null) {
        $account = profile_fetch_account($db, $currentUserId);
    }
    $locationsGrouped = official_fetch_locations_grouped($db);
} catch (Throwable $error) {
    $loadError = 'Unable to load your profile right now. Please make sure MySQL is running and the database has been imported.';
}

$initials = '';
if ($account !== null) {
    $initials = strtoupper(substr((string) $account['first_name'], 0, 1) . substr((string) $account['last_name'], 0, 1));
}

$hasPendingPhone = $account !== null && !empty($account['pending_phone_number']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Profile - LissentialManila</title>

    <link rel="stylesheet" href="../../style/shared/global.css">
    <link rel="stylesheet" href="../../style/shared/navbar.css">
    <link rel="stylesheet" href="../../style/user/home.css">
    <link rel="stylesheet" href="../../style/official/official.css">
    <link rel="stylesheet" href="../../style/user/profile.css">

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

        <div class="searchbar">
            <input type="search" placeholder="Search for a report...">
            <i class="fa-solid fa-magnifying-glass"></i>
        </div>

        <div class="auth-state-pill auth-state-pill--user">
            Logged in as <?= thread_escape((string) ($_SESSION['username'] ?? '')) ?> (<?= thread_escape((string) $role) ?>)
        </div>

        <div class="icon-button-wrapper">
            <button type="button" class="icon-button">
                <i class="fa-solid fa-bell"></i>
            </button>
            <button type="button" class="icon-button" title="Log out" onclick="window.location.href='<?= thread_escape($logoutUrl) ?>'">
                <i class="fa-solid fa-user"></i>
            </button>
        </div>
    </header>

    <aside class="sidebar">
        <?php if ($role === 'Admin'): ?>
            <div class="sidebar-options-wrapper">
                <span class="sidebar-title">ADMINISTRATION</span>
                <div class="sidebar-options">
                    <a href="../admin/admin-manage-accounts.php">Manage Accounts</a>
                    <a href="../admin/admin-platform-analytics.php">Platform Analytics</a>
                </div>
                <hr>
            </div>
        <div class="sidebar-options-wrapper">
            <span class="sidebar-title">GENERAL</span>
            <div class="sidebar-options">
                <a href="user-profile.php" style="font-weight: bold;">Account Profile</a>
            </div>
        </div>
        <?php elseif ($role === 'Official'): ?>
            <div class="create-report">
                <button type="button" onclick="window.location.href='../official/official-create-thread.php'">CREATE THREAD</button>
            </div>
            <div class="sidebar-options-wrapper">
                <span class="sidebar-title">OFFICIAL ACTIONS</span>
                <div class="sidebar-options">
                    <a href="../official/official-home.php">Review Queue</a>
                    <a href="../official/official-assigned-area.php">Assigned Area</a>
                </div>
                <hr>
            </div>

                    <div class="sidebar-options-wrapper">
            <span class="sidebar-title">INCIDENT THREADS</span>
            <div class="sidebar-options">
                <a href="user-threads.php">All</a>
                <a href="user-active-threads.php">Active</a>
                <a href="user-resolved-threads.php">Resolved</a>
            </div>
            <hr>
        </div>

        <div class="sidebar-options-wrapper">
            <span class="sidebar-title">GENERAL</span>
            <div class="sidebar-options">
                <a href="../../index.php">Back to Feed</a>
                <a href="user-profile.php" style="font-weight: bold;">Account Profile</a>
            </div>
        </div>
        <?php else: ?>
            <div class="create-report">
                <button type="button" onclick="window.location.href='user-create-report.php'">CREATE REPORT</button>
            </div>
            <div class="sidebar-options-wrapper">
                <span class="sidebar-title">FEED</span>
                <div class="sidebar-options">
                    <a href="../../index.php">All Reports</a>
                    <a href="user-my-reports.php">My Reports</a>
                </div>
                <hr>
            </div>

                    <div class="sidebar-options-wrapper">
            <span class="sidebar-title">INCIDENT THREADS</span>
            <div class="sidebar-options">
                <a href="user-threads.php">All</a>
                <a href="user-active-threads.php">Active</a>
                <a href="user-resolved-threads.php">Resolved</a>
            </div>
            <hr>
        </div>

        <div class="sidebar-options-wrapper">
            <span class="sidebar-title">GENERAL</span>
            <div class="sidebar-options">
                <a href="../../index.php">Back to Feed</a>
                <a href="user-profile.php" style="font-weight: bold;">Account Profile</a>
            </div>
        </div>
        <?php endif; ?>


        <span class="copyright-footer">IT-PROG © 2026. All rights reserved.</span>
    </aside>
</nav>

<div class="main-wrapper" style="margin-right: 0;">
    <main style="align-items: stretch;">
        <div class="profile-container">

            <div class="profile-header">
                <h1>Account Profile</h1>
                <p>View and update your personal information, password, and phone number.</p>
            </div>

            <?php if ($successFlag !== '' && isset($successMessages[$successFlag])): ?>
                <div class="flash-banner flash-banner--success">
                    <i class="fa-solid fa-circle-check"></i> <?= thread_escape($successMessages[$successFlag]) ?>
                </div>
            <?php endif; ?>

            <?php if ($errorFlag !== ''): ?>
                <div class="flash-banner flash-banner--error">
                    <i class="fa-solid fa-triangle-exclamation"></i> <?= thread_escape($errorFlag) ?>
                </div>
            <?php endif; ?>

            <?php if ($loadError !== null || $account === null): ?>
                <div class="flash-banner flash-banner--error">
                    <i class="fa-solid fa-triangle-exclamation"></i> <?= thread_escape($loadError ?? 'Profile not found.') ?>
                </div>
            <?php else: ?>

                <!-- SUMMARY -->
                <div class="profile-summary-card">
                    <div class="profile-avatar"><?= thread_escape($initials) ?></div>
                    <div class="profile-summary-info">
                        <h2><?= thread_escape($account['first_name'] . ' ' . $account['last_name']) ?></h2>
                        <span class="profile-username">@<?= thread_escape((string) $account['username']) ?></span>
                        <div class="profile-summary-meta">
                            <span><i class="fa-solid fa-user-tag"></i> <?= thread_escape((string) $account['role']) ?></span>
                            <span><i class="fa-solid fa-location-dot"></i> <?= thread_escape(admin_location_label($account['home_district'] ?? null, $account['home_city'] ?? null)) ?></span>
                            <?php if (!empty($account['assigned_city'])): ?>
                                <span><i class="fa-solid fa-map-location-dot"></i> Assigned: <?= thread_escape(admin_location_label($account['assigned_district'] ?? null, $account['assigned_city'] ?? null)) ?></span>
                            <?php endif; ?>
                            <span><i class="fa-regular fa-calendar"></i> Member since <?= thread_escape(thread_date_label((string) $account['created_at'])) ?></span>
                        </div>
                    </div>
                </div>

                <!-- BASIC INFO -->
                <div class="profile-section">
                    <h3>Basic Information</h3>
                    <form method="POST" action="user-profile-process.php">
                        <input type="hidden" name="action" value="update_info">

                        <div class="profile-form-row">
                            <div class="profile-form-group">
                                <label for="first-name">First Name</label>
                                <input type="text" id="first-name" name="first_name" value="<?= thread_escape((string) $account['first_name']) ?>" required>
                            </div>
                            <div class="profile-form-group">
                                <label for="last-name">Last Name</label>
                                <input type="text" id="last-name" name="last_name" value="<?= thread_escape((string) $account['last_name']) ?>" required>
                            </div>
                        </div>

                        <div class="profile-form-row" style="margin-top: var(--space-medium);">
                            <div class="profile-form-group">
                                <label for="username-display">Username</label>
                                <input type="text" id="username-display" value="<?= thread_escape((string) $account['username']) ?>" disabled>
                                <small>Contact an administrator to change your username.</small>
                            </div>
                            <div class="profile-form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" value="<?= thread_escape((string) ($account['email'] ?? '')) ?>">
                            </div>
                        </div>

                        <div class="profile-form-group" style="margin-top: var(--space-medium);">
                            <label for="home-location">Home Location</label>
                            <select id="home-location" name="home_location_id" required>
                                <?php foreach ($locationsGrouped as $city => $districts): ?>
                                    <optgroup label="<?= thread_escape($city) ?>">
                                        <?php foreach ($districts as $district): ?>
                                            <option value="<?= (int) $district['location_id'] ?>" <?= (int) $district['location_id'] === (int) $account['home_location_id'] ? 'selected' : '' ?>><?= thread_escape($district['district']) ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                            <small>Determines which incident notifications you receive by default.</small>
                        </div>

                        <div class="profile-form-actions" style="margin-top: var(--space-medium);">
                            <button type="submit" class="profile-btn">Save Changes</button>
                        </div>
                    </form>
                </div>

                <!-- PASSWORD -->
                <div class="profile-section">
                    <h3>Change Password</h3>
                    <form method="POST" action="user-profile-process.php">
                        <input type="hidden" name="action" value="change_password">

                        <div class="profile-form-group">
                            <label for="current-password">Current Password</label>
                            <input type="password" id="current-password" name="current_password" required autocomplete="current-password">
                        </div>

                        <div class="profile-form-row" style="margin-top: var(--space-medium);">
                            <div class="profile-form-group">
                                <label for="new-password">New Password</label>
                                <input type="password" id="new-password" name="new_password" required minlength="8" autocomplete="new-password">
                            </div>
                            <div class="profile-form-group">
                                <label for="confirm-password">Confirm New Password</label>
                                <input type="password" id="confirm-password" name="confirm_password" required minlength="8" autocomplete="new-password">
                            </div>
                        </div>

                        <div class="profile-form-actions" style="margin-top: var(--space-medium);">
                            <button type="submit" class="profile-btn">Update Password</button>
                        </div>
                    </form>
                </div>

                <!-- PHONE NUMBER -->
                <div class="profile-section">
                    <h3>Phone Number</h3>

                    <?php if ($hasPendingPhone): ?>
                        <div class="phone-pending-banner">
                            <span>
                                <i class="fa-solid fa-clock"></i>
                                Verification pending for <strong><?= thread_escape((string) $account['pending_phone_number']) ?></strong>.
                                Enter the code below to confirm the change.
                            </span>
                            <?php if ($devCode !== ''): ?>
                                <span>
                                    Dev mode (no SMS gateway configured) — your code is:
                                    <span class="phone-dev-code"><?= thread_escape($devCode) ?></span>
                                </span>
                            <?php endif; ?>
                        </div>

                        <form method="POST" action="user-profile-process.php">
                            <input type="hidden" name="action" value="confirm_phone_change">
                            <div class="profile-form-row">
                                <div class="profile-form-group">
                                    <label for="verification-code">Verification Code</label>
                                    <input type="text" id="verification-code" name="verification_code" inputmode="numeric" maxlength="6" required>
                                </div>
                            </div>
                            <div class="profile-form-actions" style="gap: 8px; margin-top: var(--space-medium);">
                                <button type="submit" class="profile-btn">Confirm Change</button>
                            </div>
                        </form>

                        <form method="POST" action="user-profile-process.php" style="margin-top: -8px;">
                            <input type="hidden" name="action" value="cancel_phone_change">
                            <div class="profile-form-actions">
                                <button type="submit" class="profile-btn profile-btn-secondary">Cancel This Change</button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="profile-form-group">
                            <label>Current Phone Number</label>
                            <input type="text" value="<?= thread_escape((string) $account['phone_number']) ?>" disabled>
                        </div>

                        <form method="POST" action="user-profile-process.php" style="margin-top: var(--space-medium);">
                            <input type="hidden" name="action" value="request_phone_change">
                            <div class="profile-form-row">
                                <div class="profile-form-group">
                                    <label for="new-phone">New Phone Number</label>
                                    <input type="tel" id="new-phone" name="new_phone_number" placeholder="+639171234567" required>
                                    <small>Changing your phone number requires re-verification via a one-time code.</small>
                                </div>
                            </div>
                            <div class="profile-form-actions" style="margin-top: var(--space-medium);">
                                <button type="submit" class="profile-btn">Request Change</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>

            <?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>
