// Shared navbar notification bell: toggles a panel showing nearby alerts
// from the user's home/saved locations (see includes/notifications-api.php).
(function () {
    function timeAgo(dateString) {
        const then = new Date(dateString.replace(' ', 'T'));
        const diffMs = Date.now() - then.getTime();
        const minutes = Math.floor(diffMs / 60000);
        if (minutes < 1) return 'Just now';
        if (minutes < 60) return `${minutes}m ago`;
        const hours = Math.floor(minutes / 60);
        if (hours < 24) return `${hours}h ago`;
        const days = Math.floor(hours / 24);
        return `${days}d ago`;
    }

    function buildThreadUrl(threadId) {
        const basePath = window.location.pathname.includes('/pages/')
            ? '../user/thread-details.php'
            : 'pages/user/thread-details.php';

        const url = new URL(basePath, window.location.href);
        url.searchParams.set('id', String(threadId));
        return url.toString();
    }

    function renderNotifications(panelBody, notifications, button, closePanel) {
        panelBody.innerHTML = '';

        if (!notifications.length) {
            const empty = document.createElement('p');
            empty.className = 'notification-empty';
            empty.textContent = "No alerts yet from your home or saved locations.";
            panelBody.appendChild(empty);
            return;
        }

        notifications.forEach((item) => {
            const entry = document.createElement('a');
            entry.className = 'notification-item';
            entry.href = buildThreadUrl(item.thread_id);
            entry.setAttribute('aria-label', `Open thread: ${item.title}`);

            entry.innerHTML = `
                <div class="notification-item-icon"><i class="fa-solid fa-location-dot"></i></div>
                <div class="notification-item-content">
                    <span class="notification-item-title">${escapeHtml(item.title)}</span>
                    <span class="notification-item-meta">${escapeHtml(item.category_name)} • ${escapeHtml(item.location_label)}</span>
                    <span class="notification-item-time">${timeAgo(item.created_at)}</span>
                </div>
            `;

            entry.addEventListener('click', () => {
                closePanel();
            });

            panelBody.appendChild(entry);
        });
    }

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value ?? '';
        return div.innerHTML;
    }

    function setupBell(button) {
        const wrapper = button.closest('.icon-button-wrapper') || button.parentElement;
        const panel = wrapper ? wrapper.querySelector('#notifPanel') : null;
        const panelBody = panel ? panel.querySelector('#notifPanelBody') : null;
        const apiUrl = button.dataset.notifApi;
        if (!panel || !panelBody || !apiUrl) return;

        let loaded = false;

        function closePanel() {
            panel.hidden = true;
            button.setAttribute('aria-expanded', 'false');
        }

        function openPanel() {
            panel.hidden = false;
            button.setAttribute('aria-expanded', 'true');
            if (!loaded) {
                loadNotifications();
            }
        }

        function loadNotifications() {
            panelBody.innerHTML = '<p class="notification-empty">Loading nearby alerts...</p>';
            fetch(apiUrl, { credentials: 'same-origin' })
                .then((response) => response.json())
                .then((data) => {
                    if (data.error) {
                        panelBody.innerHTML = `<p class="notification-empty">${escapeHtml(data.error)}</p>`;
                        return;
                    }
                    loaded = true;
                    renderNotifications(panelBody, data.notifications || [], button, closePanel);
                })
                .catch(() => {
                    panelBody.innerHTML = '<p class="notification-empty">Unable to load alerts right now.</p>';
                });
        }

        button.addEventListener('click', (event) => {
            event.stopPropagation();
            if (panel.hidden) {
                openPanel();
            } else {
                closePanel();
            }
        });

        panel.addEventListener('click', (event) => event.stopPropagation());

        document.addEventListener('pointerdown', (event) => {
            const target = event.target;
            const clickedInsideBell = target instanceof Node && button.contains(target);
            const clickedInsidePanel = target instanceof Node && panel.contains(target);

            if (!panel.hidden && !clickedInsideBell && !clickedInsidePanel) {
                closePanel();
            }
        }, true);
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.notif-bell-btn').forEach(setupBell);
    });
})();
