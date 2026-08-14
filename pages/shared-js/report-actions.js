document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-report-details-url]').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();

            if (button.dataset.loginRequired === 'true') {
                return;
            }

            window.location.href = button.dataset.reportDetailsUrl;
        });
    });

    document.querySelectorAll('[data-report-action]').forEach((button) => {
        button.addEventListener('click', async (event) => {
            event.preventDefault();
            event.stopPropagation();

            if (button.dataset.loginRequired === 'true') {
                return;
            }

            if (button.disabled) {
                return;
            }

            const action = button.dataset.reportAction;
            const count = button.querySelector('span');
            button.disabled = true;

            try {
                const formData = new FormData();
                formData.set('action', action);
                formData.set('report_id', button.dataset.reportId || '');

                const response = await fetch(button.dataset.actionUrl || 'report-action.php', {
                    method: 'POST',
                    body: formData,
                });
                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.error || 'Unable to update this report.');
                }

                if (count) {
                    count.textContent = String(result.count);
                }

                button.classList.toggle('is-active', result.active);
                button.setAttribute('aria-pressed', result.active ? 'true' : 'false');
                if (action === 'upvote') {
                    button.title = result.active ? 'Upvoted (click to undo)' : 'Upvote this report';
                } else {
                    button.title = result.active ? 'Marked resolved (click to undo)' : 'Mark as resolved';
                }
            } catch (error) {
                window.alert(error instanceof Error ? error.message : 'Unable to update this report.');
            } finally {
                button.disabled = false;
            }
        });
    });
});