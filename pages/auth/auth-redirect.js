/* Redirects unauthenticated users to login when interacting with posts */
const isAuthenticated = window.isAuthenticated || false;

document.addEventListener('DOMContentLoaded', () => {
    if (!isAuthenticated) {
        // Redirect post links to login
        document.querySelectorAll('.post-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                window.location.href = 'login.php';
            });
        });

        // Redirect interactive buttons to login
        document.querySelectorAll('.post-upvote, .post-comment, .post-resolved').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                window.location.href = 'login.php';
            });
        });
    }
});