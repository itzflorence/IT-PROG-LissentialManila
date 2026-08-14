    // Shared navbar user icon: toggles a small panel with the account's name,
// username, and a log out link, instead of logging out immediately on click.
(function () {
    function setupUserMenu(button) {
        const wrapper = button.closest('.icon-button-wrapper') || button.parentElement;
        const panel = wrapper ? wrapper.querySelector('#userMenuPanel') : null;
        if (!panel) return;

        function closePanel() {
            panel.hidden = true;
            button.setAttribute('aria-expanded', 'false');
        }

        function openPanel() {
            panel.hidden = false;
            button.setAttribute('aria-expanded', 'true');
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
            const clickedInsideButton = target instanceof Node && button.contains(target);
            const clickedInsidePanel = target instanceof Node && panel.contains(target);

            if (!panel.hidden && !clickedInsideButton && !clickedInsidePanel) {
                closePanel();
            }
        }, true);
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.user-menu-btn').forEach(setupUserMenu);
    });
})();
