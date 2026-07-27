<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Accounts - LissentialManila</title>

    <link rel="stylesheet" href="../../style/shared/global.css">
    <link rel="stylesheet" href="../../style/shared/navbar.css">
    <link rel="stylesheet" href="../../style/user/home.css">
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
            <input type="search" placeholder="Search for a report...">
            <i class="fa-solid fa-magnifying-glass"></i>
        </div>

        <div class="icon-button-wrapper">
            <button type="button" class="icon-button">
                <i class="fa-solid fa-bell"></i>
            </button>

            <button type="button" class="icon-button">
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
                <a href="#">All Threads</a>
                <a href="#">Archived Threads</a>
            </div>
            <hr>
        </div>

        <div class="sidebar-options-wrapper">
            <span class="sidebar-title">GENERAL</span>
            <div class="sidebar-options">
                <a href="../user/user-home.php">Back to Feed</a>
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
                            <th>Assigned Area</th>
                            <th>Home Location</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="accounts-table-body">

                        <tr data-user-id="1" data-first-name="Green" data-last-name="Archer"
                            data-username="GreenArcher_01" data-email="greenarcher01@gmail.com"
                            data-phone="09171234567" data-role="Student" data-assigned-area=""
                            data-home-location-id="8" data-is-deleted="false">
                            <td class="account-name-cell">
                                <span class="account-fullname">Green Archer</span>
                                <span class="account-username">@GreenArcher_01</span>
                            </td>
                            <td>0917 123 4567</td>
                            <td>greenarcher01@gmail.com</td>
                            <td><span class="badge badge-role-student">Student</span></td>
                            <td>—</td>
                            <td>Taft Avenue, Manila</td>
                            <td><span class="badge badge-status badge-status-active">Active</span></td>
                            <td class="account-actions">
                                <button type="button" class="btn-edit-account" onclick="openEditAccountModal(this)">
                                    <i class="fa-solid fa-pen-to-square"></i> Edit
                                </button>
                                <button type="button" class="btn-delete-account" onclick="openDeleteConfirm(this)">
                                    <i class="fa-solid fa-trash"></i> Delete
                                </button>
                            </td>
                        </tr>

                        <tr data-user-id="2" data-first-name="Michael" data-last-name="delos Santos"
                            data-username="MichaelJackson" data-email="mdelossantos@gmail.com"
                            data-phone="09189876543" data-role="Student" data-assigned-area=""
                            data-home-location-id="45" data-is-deleted="false">
                            <td class="account-name-cell">
                                <span class="account-fullname">Michael delos Santos</span>
                                <span class="account-username">@MichaelJackson</span>
                            </td>
                            <td>0918 987 6543</td>
                            <td>mdelossantos@gmail.com</td>
                            <td><span class="badge badge-role-student">Student</span></td>
                            <td>—</td>
                            <td>Alabang, Muntinlupa</td>
                            <td><span class="badge badge-status badge-status-active">Active</span></td>
                            <td class="account-actions">
                                <button type="button" class="btn-edit-account" onclick="openEditAccountModal(this)">
                                    <i class="fa-solid fa-pen-to-square"></i> Edit
                                </button>
                                <button type="button" class="btn-delete-account" onclick="openDeleteConfirm(this)">
                                    <i class="fa-solid fa-trash"></i> Delete
                                </button>
                            </td>
                        </tr>

                        <tr data-user-id="3" data-first-name="Rosario" data-last-name="Mendoza"
                            data-username="LGU_Marikina_09" data-email="rmendoza@marikina.gov.ph"
                            data-phone="09201112233" data-role="Official" data-assigned-area="Marikina"
                            data-home-location-id="60" data-is-deleted="false">
                            <td class="account-name-cell">
                                <span class="account-fullname">Rosario Mendoza</span>
                                <span class="account-username">@LGU_Marikina_09</span>
                            </td>
                            <td>0920 111 2233</td>
                            <td>rmendoza@marikina.gov.ph</td>
                            <td><span class="badge badge-role-official">Official</span></td>
                            <td>Marikina</td>
                            <td>Marikina Heights, Marikina</td>
                            <td><span class="badge badge-status badge-status-active">Active</span></td>
                            <td class="account-actions">
                                <button type="button" class="btn-edit-account" onclick="openEditAccountModal(this)">
                                    <i class="fa-solid fa-pen-to-square"></i> Edit
                                </button>
                                <button type="button" class="btn-delete-account" onclick="openDeleteConfirm(this)">
                                    <i class="fa-solid fa-trash"></i> Delete
                                </button>
                            </td>
                        </tr>

                        <tr data-user-id="4" data-first-name="Enrico Terrence" data-last-name="Ponciano"
                            data-username="MMDA_Enforcer_14" data-email="eponciano@mmda.gov.ph"
                            data-phone="09221234455" data-role="Official" data-assigned-area="Pasig"
                            data-home-location-id="70" data-is-deleted="true">
                            <td class="account-name-cell">
                                <span class="account-fullname">Enrico Terrence Ponciano</span>
                                <span class="account-username">@MMDA_Enforcer_14</span>
                            </td>
                            <td>0922 123 4455</td>
                            <td>eponciano@mmda.gov.ph</td>
                            <td><span class="badge badge-role-official">Official</span></td>
                            <td>Pasig</td>
                            <td>Ortigas, Pasig</td>
                            <td><span class="badge badge-status badge-status-deleted">Deleted</span></td>
                            <td class="account-actions">
                                <button type="button" class="btn-edit-account" onclick="openEditAccountModal(this)">
                                    <i class="fa-solid fa-pen-to-square"></i> Edit
                                </button>
                                <button type="button" class="btn-restore-account" onclick="openRestoreConfirm(this)">
                                    <i class="fa-solid fa-rotate-left"></i> Restore
                                </button>
                            </td>
                        </tr>

                        <tr data-user-id="5" data-first-name="Max" data-last-name="Gatmaitan"
                            data-username="SysAdmin_Max" data-email="mgatmaitan@lissentialmanila.ph"
                            data-phone="09301239988" data-role="Admin" data-assigned-area=""
                            data-home-location-id="26" data-is-deleted="false">
                            <td class="account-name-cell">
                                <span class="account-fullname">Max Gatmaitan</span>
                                <span class="account-username">@SysAdmin_Max</span>
                            </td>
                            <td>0930 123 9988</td>
                            <td>mgatmaitan@lissentialmanila.ph</td>
                            <td><span class="badge badge-role-admin">Admin</span></td>
                            <td>—</td>
                            <td>Diliman, Quezon City</td>
                            <td><span class="badge badge-status badge-status-active">Active</span></td>
                            <td class="account-actions">
                                <button type="button" class="btn-edit-account" onclick="openEditAccountModal(this)">
                                    <i class="fa-solid fa-pen-to-square"></i> Edit
                                </button>
                                <button type="button" class="btn-delete-account" onclick="openDeleteConfirm(this)">
                                    <i class="fa-solid fa-trash"></i> Delete
                                </button>
                            </td>
                        </tr>

                    </tbody>
                </table>

                <div id="accounts-empty-state" class="accounts-empty-state" style="display: none;">
                    <i class="fa-solid fa-user-slash" style="font-size: 2rem; margin-bottom: 8px;"></i>
                    <p>No accounts match your search.</p>
                </div>
            </div>

        </div>
    </main>
</div>

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
                    <input type="password" id="add-password" name="password" required>
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
                <div class="modal-form-group" id="add-assigned-area-group">
                    <label for="add-assigned-area">Assigned Area</label>
                    <input type="text" id="add-assigned-area" name="assigned_area" placeholder="e.g. Marikina">
                </div>
            </div>

            <div class="modal-form-group">
                <label for="add-home-location">Home Location</label>
                <select id="add-home-location" name="home_location_id" required>
                    <!-- populated from the locations table once db_connect.php exists -->
                    <optgroup label="Manila">
                        <option value="8">Taft Avenue</option>
                        <option value="10">Quiapo</option>
                    </optgroup>
                    <optgroup label="Quezon City">
                        <option value="26">Diliman</option>
                        <option value="27">Katipunan</option>
                    </optgroup>
                    <optgroup label="Pasig">
                        <option value="70">Ortigas</option>
                    </optgroup>
                    <optgroup label="Marikina">
                        <option value="60">Marikina Heights</option>
                    </optgroup>
                    <optgroup label="Muntinlupa">
                        <option value="45">Alabang</option>
                    </optgroup>
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

            <div class="status-toggle-row">
                <span>Account Active</span>
                <label class="switch">
                    <input type="checkbox" id="edit-status-toggle" name="is_active">
                    <span class="switch-slider"></span>
                </label>
            </div>

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
                <div class="modal-form-group" id="edit-assigned-area-group">
                    <label for="edit-assigned-area">Assigned Area</label>
                    <input type="text" id="edit-assigned-area" name="assigned_area" placeholder="e.g. Marikina">
                </div>
            </div>

            <div class="modal-form-group">
                <label for="edit-home-location">Home Location</label>
                <select id="edit-home-location" name="home_location_id" required>
                    <optgroup label="Manila">
                        <option value="8">Taft Avenue</option>
                        <option value="10">Quiapo</option>
                    </optgroup>
                    <optgroup label="Quezon City">
                        <option value="26">Diliman</option>
                        <option value="27">Katipunan</option>
                    </optgroup>
                    <optgroup label="Pasig">
                        <option value="70">Ortigas</option>
                    </optgroup>
                    <optgroup label="Marikina">
                        <option value="60">Marikina Heights</option>
                    </optgroup>
                    <optgroup label="Muntinlupa">
                        <option value="45">Alabang</option>
                    </optgroup>
                </select>
            </div>

            <div class="modal-form-group">
                <label for="edit-password">Reset Password</label>
                <input type="password" id="edit-password" name="password" placeholder="Leave blank to keep current password">
            </div>

            <div class="modal-footer">
                <button type="button" class="modal-btn modal-btn-cancel" onclick="closeModal('modal-edit-account')">Cancel</button>
                <button type="submit" class="modal-btn modal-btn-confirm">Save Changes</button>
            </div>
        </form>
    </div>
</div>

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

        <div class="modal-footer">
            <button type="button" class="modal-btn modal-btn-cancel" onclick="closeModal('modal-delete-confirm')">Cancel</button>
            <button type="button" class="modal-btn modal-btn-danger" onclick="confirmDeleteAccount()">Delete Account</button>
        </div>
    </div>
</div>

<script src="admin.js" defer></script>
</body>

</html>
