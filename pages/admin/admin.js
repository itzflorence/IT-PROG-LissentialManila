
function openModal(modalId) {
    document.getElementById(modalId).classList.add('modal-open');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('modal-open');
}
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
            overlay.classList.remove('modal-open');
        }
    });
});

/* ADD ACCOUNT                                                    */
function openAddAccountModal() {
    document.getElementById('add-account-form').reset();
    toggleAssignedArea('add');
    openModal('modal-add-account');
}
function openEditAccountModal(button) {
    const row = button.closest('tr');
    const data = row.dataset;

    document.getElementById('edit-user-id').value = data.userId;
    document.getElementById('edit-first-name').value = data.firstName;
    document.getElementById('edit-last-name').value = data.lastName;
    document.getElementById('edit-username').value = data.username;
    document.getElementById('edit-email').value = data.email;
    document.getElementById('edit-phone').value = data.phone;
    document.getElementById('edit-role').value = data.role;
    document.getElementById('edit-assigned-area').value = data.assignedArea || '';
    document.getElementById('edit-home-location').value = data.homeLocationId || '';
    document.getElementById('edit-status-toggle').checked = data.isDeleted !== 'true';

    toggleAssignedArea('edit');
    openModal('modal-edit-account');
}

function toggleAssignedArea(prefix) {
    const roleSelect = document.getElementById(prefix + '-role');
    const areaGroup = document.getElementById(prefix + '-assigned-area-group');
    if (roleSelect.value === 'Official') {
        areaGroup.classList.remove('modal-hidden-field');
    } else {
        areaGroup.classList.add('modal-hidden-field');
    }
}

document.getElementById('add-role').addEventListener('change', () => toggleAssignedArea('add'));
document.getElementById('edit-role').addEventListener('change', () => toggleAssignedArea('edit'));

let pendingDeleteRow = null;

function openDeleteConfirm(button) {
    pendingDeleteRow = button.closest('tr');
    const name = pendingDeleteRow.dataset.firstName + ' ' + pendingDeleteRow.dataset.lastName;
    document.getElementById('delete-confirm-name').textContent = name;
    openModal('modal-delete-confirm');
}

function confirmDeleteAccount() {
    if (pendingDeleteRow) {
        pendingDeleteRow.classList.add('row-deleted');
        const statusBadge = pendingDeleteRow.querySelector('.badge-status');
        statusBadge.textContent = 'Deleted';
        statusBadge.classList.remove('badge-status-active');
        statusBadge.classList.add('badge-status-deleted');

        const actionsCell = pendingDeleteRow.querySelector('.account-actions');
        const deleteBtn = actionsCell.querySelector('.btn-delete-account');
        deleteBtn.textContent = '';
        deleteBtn.innerHTML = '<i class="fa-solid fa-rotate-left"></i> Restore';
        deleteBtn.classList.remove('btn-delete-account');
        deleteBtn.classList.add('btn-restore-account');
        deleteBtn.setAttribute('onclick', 'openRestoreConfirm(this)');
    }
    closeModal('modal-delete-confirm');
    pendingDeleteRow = null;
}

function openRestoreConfirm(button) {
    const row = button.closest('tr');
    row.classList.remove('row-deleted');
    const statusBadge = row.querySelector('.badge-status');
    statusBadge.textContent = 'Active';
    statusBadge.classList.remove('badge-status-deleted');
    statusBadge.classList.add('badge-status-active');

    button.innerHTML = '<i class="fa-solid fa-trash"></i> Delete';
    button.classList.remove('btn-restore-account');
    button.classList.add('btn-delete-account');
    button.setAttribute('onclick', 'openDeleteConfirm(this)');
}

function filterAccountsTable() {
    const searchTerm = document.getElementById('accounts-search-input').value.toLowerCase();
    const roleFilter = document.getElementById('accounts-role-filter').value;
    const rows = document.querySelectorAll('#accounts-table-body tr');
    let visibleCount = 0;

    rows.forEach(row => {
        const name = (row.dataset.firstName + ' ' + row.dataset.lastName).toLowerCase();
        const username = row.dataset.username.toLowerCase();
        const phone = row.dataset.phone.toLowerCase();
        const role = row.dataset.role;

        const matchesSearch = name.includes(searchTerm) || username.includes(searchTerm) || phone.includes(searchTerm);
        const matchesRole = roleFilter === 'All' || role === roleFilter;

        if (matchesSearch && matchesRole) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    document.getElementById('accounts-empty-state').style.display = visibleCount === 0 ? 'block' : 'none';
}

document.getElementById('accounts-search-input').addEventListener('input', filterAccountsTable);
document.getElementById('accounts-role-filter').addEventListener('change', filterAccountsTable);
