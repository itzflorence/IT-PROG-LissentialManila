<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/thread-query.php';
require_once __DIR__ . '/../../includes/official-query.php';
require_once __DIR__ . '/../../includes/admin-query.php';

require_role(['Admin'], '../auth/login.php', '../../index.php');

$username = $_SESSION['username'] ?? null;
$safeUsername = thread_escape((string) ($username ?? ''));
$logoutUrl = '../auth/logout.php';

$currentUserId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$currentUserId = $currentUserId === false ? null : $currentUserId;

$successFlag = trim((string) ($_GET['success'] ?? ''));
$errorFlag = trim((string) ($_GET['error'] ?? ''));

$successMessages = [
    'created' => 'Account created successfully.',
    'updated' => 'Account updated successfully.',
    'deleted' => 'Account deactivated.',
    'restored' => 'Account restored.',
];

$accounts = [];
$locationsGrouped = [];
$loadError = null;

try {
    $db = thread_db();
    $accounts = admin_fetch_accounts($db);
    $locationsGrouped = official_fetch_locations_grouped($db);
} catch (Throwable $error) {
    $loadError = 'Unable to load accounts right now. Please make sure MySQL is running and the database has been imported.';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Accounts - LissentialManila</title>

    <link rel="stylesheet" href="../../style/shared/global.css">
    <link rel="stylesheet" href="../../style/shared/navbar.css">
    <link rel="stylesheet" href="../../style/user/home.css">
    <link rel="stylesheet" href="../../style/official/official.css">
    <link rel="stylesheet" href="../../style/admin/manage-accounts.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
          integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
          crossorigin="anonymous" referrerpolicy="no-referrer" />
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
            <button type="button" class="icon-button">
                <i class="fa-solid fa-bell"></i>
            </button>
            <button type="button" class="icon-button" title="Log out" onclick="window.location.href='<?= thread_escape($logoutUrl) ?>'">
                <i class="fa-solid fa-user"></i>
            </button>
        </div>
    </header>

    <aside class="sidebar">
        <div class="sidebar-options-wrapper">
            <span class="sidebar-title">ADMINISTRATION</span>
            <div class="sidebar-options">
                <a href="admin-manage-accounts.php" style="font-weight: bold;">Manage Accounts</a>
                <a href="#">Platform Analytics</a>
                <a href="#">Audit Logs</a>
            </div>
            <hr>
        </div>

        <div class="sidebar-options-wrapper">
            <span class="sidebar-title">CONTENT</span>
            <div class="sidebar-options">
                <a href="#">Official Advisories</a>
                <a href="../user/user-threads.php">All Threads</a>
                <a href="#">Archived Threads</a>
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

<div class="main-wrapper" style="margin-right: 0;">
    <main style="align-items: stretch;">
        <div class="accounts-container">

            <div class="accounts-header">
                <div>
                    <h1>Manage Accounts</h1>
                    <p>View, add, edit, and deactivate Student, Official, and Admin accounts.</p>
                </div>
                <button type="button" class="btn-add-account" onclick="openAddAccountModal()">
                    <i class="fa-solid fa-plus"></i> Add Account
                </button>
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

            <?php if ($loadError !== null): ?>
                <div class="flash-banner flash-banner--error">
                    <i class="fa-solid fa-triangle-exclamation"></i> <?= thread_escape($loadError) ?>
                </div>
            <?php else: ?>

                <div class="accounts-toolbar">
                    <div class="accounts-search">
                        <input type="text" id="accounts-search-input" placeholder="Search by name, username, or phone number...">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </div>
                    <div class="accounts-filter">
                        <select id="accounts-role-filter">
                            <option value="All">All Roles</option>
                            <option value="Student">Student</option>
                            <option value="Official">Official</option>
                            <option value="Admin">Admin</option>
                        </select>
                    </div>
                </div>

                <div class="accounts-table-wrapper">
                    <table class="accounts-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Phone Number</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Assigned Location</th>
                                <th>Home Location</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="accounts-table-body">
                            <?php foreach ($accounts as $account): ?>
                                <?php
                                $userId = (int) $account['user_id'];
                                $firstName = (string) $account['first_name'];
                                $lastName = (string) $account['last_name'];
                                $usernameCell = (string) $account['username'];
                                $email = (string) ($account['email'] ?? '');
                                $phone = (string) $account['phone_number'];
                                $role = (string) $account['role'];
                                $isDeleted = (bool) $account['is_deleted'];
                                $assignedLabel = admin_location_label($account['assigned_district'] ?? null, $account['assigned_city'] ?? null);
                                $homeLabel = admin_location_label($account['home_district'] ?? null, $account['home_city'] ?? null);
                                $roleBadgeClass = 'badge-role-' . strtolower($role);
                                ?>
                                <tr class="<?= $isDeleted ? 'row-deleted' : '' ?>"
                                    data-user-id="<?= $userId ?>"
                                    data-first-name="<?= thread_escape($firstName) ?>"
                                    data-last-name="<?= thread_escape($lastName) ?>"
                                    data-username="<?= thread_escape($usernameCell) ?>"
                                    data-email="<?= thread_escape($email) ?>"
                                    data-phone="<?= thread_escape($phone) ?>"
                                    data-role="<?= thread_escape($role) ?>"
                                    data-assigned-location-id="<?= (int) ($account['assigned_location_id'] ?? 0) ?>"
                                    data-home-location-id="<?= (int) $account['home_location_id'] ?>"
                                    data-is-deleted="<?= $isDeleted ? 'true' : 'false' ?>">
                                    <td class="account-name-cell">
                                        <span class="account-fullname"><?= thread_escape($firstName . ' ' . $lastName) ?></span>
                                        <span class="account-username">@<?= thread_escape($usernameCell) ?></span>
                                    </td>
                                    <td><?= thread_escape($phone) ?></td>
                                    <td><?= thread_escape($email !== '' ? $email : '—') ?></td>
                                    <td><span class="badge <?= $roleBadgeClass ?>"><?= thread_escape($role) ?></span></td>
                                    <td><?= thread_escape($assignedLabel) ?></td>
                                    <td><?= thread_escape($homeLabel) ?></td>
                                    <td><span class="badge badge-status <?= $isDeleted ? 'badge-status-deleted' : 'badge-status-active' ?>"><?= $isDeleted ? 'Deleted' : 'Active' ?></span></td>
                                    <td class="account-actions">
                                        <button type="button" class="btn-edit-account" onclick="openEditAccountModal(this)">
                                            <i class="fa-solid fa-pen-to-square"></i> Edit
                                        </button>
                                        <?php if ($isDeleted): ?>
                                            <form method="POST" action="admin-account-process.php" style="display: inline;">
                                                <input type="hidden" name="action" value="restore">
                                                <input type="hidden" name="user_id" value="<?= $userId ?>">
                                                <button type="submit" class="btn-restore-account">
                                                    <i class="fa-solid fa-rotate-left"></i> Restore
                                                </button>
                                            </form>
                                        <?php elseif ($userId !== $currentUserId): ?>
                                            <button type="button" class="btn-delete-account" onclick="openDeleteConfirm(this)">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div id="accounts-empty-state" class="accounts-empty-state" style="display: <?= $accounts === [] ? 'block' : 'none' ?>;">
                        <i class="fa-solid fa-user-slash" style="font-size: 2rem; margin-bottom: 8px;"></i>
                        <p>No accounts match your search.</p>
                    </div>
                </div>

            <?php endif; ?>
        </div>
    </main>
</div>

<!-- ADD ACCOUNT MODAL -->
<div class="modal-overlay" id="modal-add-account">
    <div class="modal-box">
        <div class="modal-header">
            <h2>Add Account</h2>
            <button type="button" class="modal-close-btn" onclick="closeModal('modal-add-account')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="add-account-form" action="admin-account-process.php" method="POST">
            <input type="hidden" name="action" value="create">

            <div class="modal-form-row">
                <div class="modal-form-group">
                    <label for="add-first-name">First Name</label>
                    <input type="text" id="add-first-name" name="first_name" required>
                </div>
                <div class="modal-form-group">
                    <label for="add-last-name">Last Name</label>
                    <input type="text" id="add-last-name" name="last_name" required>
                </div>
            </div>

            <div class="modal-form-row">
                <div class="modal-form-group">
                    <label for="add-username">Username</label>
                    <input type="text" id="add-username" name="username" required>
                </div>
                <div class="modal-form-group">
                    <label for="add-email">Email</label>
                    <input type="email" id="add-email" name="email">
                </div>
            </div>

            <div class="modal-form-row">
                <div class="modal-form-group">
                    <label for="add-phone">Phone Number</label>
                    <input type="tel" id="add-phone" name="phone_number" required>
                </div>
                <div class="modal-form-group">
                    <label for="add-password">Temporary Password</label>
                    <input type="password" id="add-password" name="password" required minlength="8">
                </div>
            </div>

            <div class="modal-form-row">
                <div class="modal-form-group">
                    <label for="add-role">Role</label>
                    <select id="add-role" name="role" required>
                        <option value="Student">Student</option>
                        <option value="Official">Official</option>
                        <option value="Admin">Admin</option>
                    </select>
                </div>
                <div class="modal-form-group" id="add-assigned-location-group">
                    <label for="add-assigned-location">Assigned Location</label>
                    <select id="add-assigned-location" name="assigned_location_id">
                        <option value="">— None —</option>
                        <?php foreach ($locationsGrouped as $city => $districts): ?>
                            <optgroup label="<?= thread_escape($city) ?>">
                                <?php foreach ($districts as $district): ?>
                                    <option value="<?= (int) $district['location_id'] ?>"><?= thread_escape($district['district']) ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="modal-form-group">
                <label for="add-home-location">Home Location</label>
                <select id="add-home-location" name="home_location_id" required>
                    <option value="" disabled selected>Select a location</option>
                    <?php foreach ($locationsGrouped as $city => $districts): ?>
                        <optgroup label="<?= thread_escape($city) ?>">
                            <?php foreach ($districts as $district): ?>
                                <option value="<?= (int) $district['location_id'] ?>"><?= thread_escape($district['district']) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
                <small>Determines which incident notifications this account receives by default.</small>
            </div>

            <div class="modal-footer">
                <button type="button" class="modal-btn modal-btn-cancel" onclick="closeModal('modal-add-account')">Cancel</button>
                <button type="submit" class="modal-btn modal-btn-confirm">Create Account</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT ACCOUNT MODAL -->
<div class="modal-overlay" id="modal-edit-account">
    <div class="modal-box">
        <div class="modal-header">
            <h2>Edit Account</h2>
            <button type="button" class="modal-close-btn" onclick="closeModal('modal-edit-account')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="edit-account-form" action="admin-account-process.php" method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" id="edit-user-id" name="user_id">

            <div class="modal-form-row">
                <div class="modal-form-group">
                    <label for="edit-first-name">First Name</label>
                    <input type="text" id="edit-first-name" name="first_name" required>
                </div>
                <div class="modal-form-group">
                    <label for="edit-last-name">Last Name</label>
                    <input type="text" id="edit-last-name" name="last_name" required>
                </div>
            </div>

            <div class="modal-form-row">
                <div class="modal-form-group">
                    <label for="edit-username">Username</label>
                    <input type="text" id="edit-username" name="username" required>
                </div>
                <div class="modal-form-group">
                    <label for="edit-email">Email</label>
                    <input type="email" id="edit-email" name="email">
                </div>
            </div>

            <div class="modal-form-group">
                <label for="edit-phone">Phone Number</label>
                <input type="tel" id="edit-phone" name="phone_number" required>
                <small>Changing this will require phone re-verification (pending_phone_number flow).</small>
            </div>

            <div class="modal-form-row">
                <div class="modal-form-group">
                    <label for="edit-role">Role</label>
                    <select id="edit-role" name="role" required>
                        <option value="Student">Student</option>
                        <option value="Official">Official</option>
                        <option value="Admin">Admin</option>
                    </select>
                </div>
                <div class="modal-form-group" id="edit-assigned-location-group">
                    <label for="edit-assigned-location">Assigned Location</label>
                    <select id="edit-assigned-location" name="assigned_location_id">
                        <option value="">— None —</option>
                        <?php foreach ($locationsGrouped as $city => $districts): ?>
                            <optgroup label="<?= thread_escape($city) ?>">
                                <?php foreach ($districts as $district): ?>
                                    <option value="<?= (int) $district['location_id'] ?>"><?= thread_escape($district['district']) ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="modal-form-group">
                <label for="edit-home-location">Home Location</label>
                <select id="edit-home-location" name="home_location_id" required>
                    <?php foreach ($locationsGrouped as $city => $districts): ?>
                        <optgroup label="<?= thread_escape($city) ?>">
                            <?php foreach ($districts as $district): ?>
                                <option value="<?= (int) $district['location_id'] ?>"><?= thread_escape($district['district']) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="modal-form-group">
                <label for="edit-password">Reset Password</label>
                <input type="password" id="edit-password" name="password" minlength="8" placeholder="Leave blank to keep current password">
            </div>

            <div class="modal-footer">
                <button type="button" class="modal-btn modal-btn-cancel" onclick="closeModal('modal-edit-account')">Cancel</button>
                <button type="submit" class="modal-btn modal-btn-confirm">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- DELETE CONFIRMATION MODAL -->
<div class="modal-overlay" id="modal-delete-confirm">
    <div class="modal-box modal-box-small">
        <div class="modal-header">
            <h2>Delete Account</h2>
            <button type="button" class="modal-close-btn" onclick="closeModal('modal-delete-confirm')">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <p style="color: var(--color4); font-size: var(--font-small); margin-bottom: var(--space-medium);">
            Are you sure you want to delete <strong id="delete-confirm-name" style="color: var(--colorText);"></strong>'s account?
            This is a soft delete — the account can be restored later from this same page.
        </p>

        <form id="delete-confirm-form" method="POST" action="admin-account-process.php">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" id="delete-confirm-user-id" name="user_id">
            <div class="modal-footer">
                <button type="button" class="modal-btn modal-btn-cancel" onclick="closeModal('modal-delete-confirm')">Cancel</button>
                <button type="submit" class="modal-btn modal-btn-danger">Delete Account</button>
            </div>
        </form>
    </div>
</div>

<script src="admin.js" defer></script>
</body>

</html>
