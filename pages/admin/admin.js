/* MODAL OPEN / CLOSE HELPERS                                     */
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
    toggleAssignedLocation('add');
    openModal('modal-add-account');
}

/* EDIT ACCOUNT — populate modal fields from the row's data-* attrs */
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
    document.getElementById('edit-assigned-location').value = data.assignedLocationId && data.assignedLocationId !== '0' ? data.assignedLocationId : '';
    document.getElementById('edit-home-location').value = data.homeLocationId || '';
    document.getElementById('edit-password').value = '';

    toggleAssignedLocation('edit');
    openModal('modal-edit-account');
}

/* SHOW/HIDE "Assigned Location" FIELD — only relevant for Officials */
function toggleAssignedLocation(prefix) {
    const roleSelect = document.getElementById(prefix + '-role');
    const locationGroup = document.getElementById(prefix + '-assigned-location-group');
    if (roleSelect.value === 'Official') {
        locationGroup.classList.remove('modal-hidden-field');
    } else {
        locationGroup.classList.add('modal-hidden-field');
    }
}

document.getElementById('add-role').addEventListener('change', () => toggleAssignedLocation('add'));
document.getElementById('edit-role').addEventListener('change', () => toggleAssignedLocation('edit'));

/* DELETE CONFIRMATION — submits the real delete form on confirm  */
function openDeleteConfirm(button) {
    const row = button.closest('tr');
    const name = row.dataset.firstName + ' ' + row.dataset.lastName;

    document.getElementById('delete-confirm-name').textContent = name;
    document.getElementById('delete-confirm-user-id').value = row.dataset.userId;

    openModal('modal-delete-confirm');
}

/* LIVE SEARCH + ROLE FILTER (client-side, over the PHP-rendered rows) */
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

    const emptyState = document.getElementById('accounts-empty-state');
    if (emptyState) {
        emptyState.style.display = visibleCount === 0 ? 'block' : 'none';
    }
}

const searchInput = document.getElementById('accounts-search-input');
const roleFilterSelect = document.getElementById('accounts-role-filter');
if (searchInput) searchInput.addEventListener('input', filterAccountsTable);
if (roleFilterSelect) roleFilterSelect.addEventListener('change', filterAccountsTable);
