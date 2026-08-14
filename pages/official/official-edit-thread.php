<?php

declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/thread-query.php';
require_once __DIR__ . '/../../includes/report-feed.php';
require_once __DIR__ . '/../../includes/official-query.php';

require_role(['Official', 'Admin'], '../auth/login.php', '../../index.php');

$isAuthenticated = is_authenticated();
$username = $_SESSION['username'] ?? null;
$safeUsername = thread_escape((string) ($username ?? ''));
$loginUrl = '../auth/login.php';
$logoutUrl = '../auth/logout.php';

$threadId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$threadId = $threadId === false ? null : $threadId;

$thread = null;
$reports = [];
$errorMessage = null;

$flashUpdated = ($_GET['updated'] ?? '') === '1';
$flashCreated = ($_GET['created'] ?? '') === '1';
$flashError = trim((string) ($_GET['error'] ?? ''));

if ($threadId === null) {
    http_response_code(400);
    $errorMessage = 'A valid thread ID is required.';
} else {
    try {
        $db = thread_db();
        $thread = thread_fetch_one($db, $threadId);

        if ($thread === null) {
            http_response_code(404);
            $errorMessage = 'The requested thread does not exist.';
        } else {
            $reports = thread_fetch_reports($db, $threadId);
        }
    } catch (Throwable $error) {
        http_response_code(500);
        $errorMessage = 'Unable to load this thread right now. Make sure MySQL is running and the database has been imported.';
    }
}

$allowedThreadStatuses = ['Active', 'Resolved', 'Archived'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $thread ? thread_escape((string) $thread['title']) : 'Edit Thread' ?> | Lissential Manila</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
          integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
          crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="../../style/user/threads.css">
    <link rel="stylesheet" href="../../style/official/official.css">
</head>
<body>
<nav>
    <header class="navbar">
        <div class="navbar-logo">
            <a href="official-home.php"><img src="../../assets/LOGO/logo_normal.png" alt="Lissential Manila logo"></a>
        </div>
        <div class="searchbar">
            <input type="search" placeholder="Search for a report...">
            <i class="fa-solid fa-magnifying-glass"></i>
        </div>
        <div class="auth-state-pill auth-state-pill--user">
            Logged in as <?= $safeUsername ?>
        </div>
        <div class="icon-button-wrapper">
            <button type="button" class="icon-button" aria-label="Notifications"><i class="fa-solid fa-bell"></i></button>
            <button type="button" class="icon-button" title="Log out" onclick="window.location.href='<?= thread_escape($logoutUrl) ?>'">
                <i class="fa-solid fa-user"></i>
            </button>
        </div>
    </header>

    <aside class="sidebar">
        <div class="create-report">
            <button type="button" onclick="window.location.href='official-create-thread.php'">CREATE THREAD</button>
        </div>

        <div class="sidebar-options-wrapper">
            <span class="sidebar-title">OFFICIAL ACTIONS</span>
            <div class="sidebar-options">
                <a href="official-home.php">Review Queue</a>
                <a href="official-assigned-area.php">Assigned Area</a>
                <a href="official-create-thread.php">Create Thread</a>
            </div>
            <hr>
        </div>

        <div class="sidebar-options-wrapper">
            <span class="sidebar-title">INCIDENT THREADS</span>
            <div class="sidebar-options">
                <a href="official-threads.php">All</a>
                <a href="official-active-threads.php">Active</a>
                <a href="official-resolved-threads.php">Resolved</a>
                <a href="official-archived-threads.php">Archived</a>
            </div>
            <hr>
        </div>

        <div class="sidebar-options-wrapper">
            <span class="sidebar-title">GENERAL</span>
            <div class="sidebar-options">
                <a href="official-home.php">Review Queue</a>
                <a href="#">Account Profile</a>
            </div>
        </div>

        <span class="copyright-footer">IT-PROG © 2026. All rights reserved.</span>
    </aside>
</nav>

<div class="threads-main-wrapper">
<main class="thread-details-page">
    <a href="../user/thread-details.php?id=<?= (int) $threadId ?>" class="details-navbar__back"><i class="fa-solid fa-arrow-left"></i> Back to Thread</a>

    <?php if ($errorMessage !== null): ?>
        <section class="thread-state thread-state--error" style="margin-top: var(--space-medium);">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <h1>Thread unavailable</h1>
            <p><?= thread_escape($errorMessage) ?></p>
            <a href="official-home.php">Return to Review Queue</a>
        </section>
    <?php else: ?>

        <?php if ($flashUpdated || $flashCreated): ?>
            <div class="flash-banner flash-banner--success" style="margin-top: var(--space-medium);">
                <i class="fa-solid fa-circle-check"></i> <?= $flashCreated ? 'Thread created successfully.' : 'Thread updated successfully.' ?>
            </div>
        <?php endif; ?>

        <?php if ($flashError !== ''): ?>
            <div class="flash-banner flash-banner--error" style="margin-top: var(--space-medium);">
                <i class="fa-solid fa-triangle-exclamation"></i> <?= thread_escape($flashError) ?>
            </div>
        <?php endif; ?>

        <section class="thread-details-hero" style="margin-top: var(--space-medium);">
            <div class="thread-details-hero__topline">
                <span class="thread-status thread-status--<?= strtolower(thread_escape((string) $thread['status'])) ?>"><i class="fa-solid fa-circle"></i><?= thread_escape($thread['status']) ?></span>
                <span>Last updated <?= thread_escape(thread_date_label($thread['updated_at'])) ?></span>
            </div>
            <h1 style="font-size: clamp(1.75rem, 4vw, 2.75rem);"><?= thread_escape($thread['title']) ?></h1>
            <div class="thread-details-hero__meta">
                <span><i class="fa-solid fa-location-dot"></i><?= thread_escape(thread_location_label($thread)) ?></span>
                <span><i class="fa-solid fa-layer-group"></i><?= thread_escape($thread['category_name']) ?></span>
                <span><i class="fa-solid fa-user-shield"></i>Created by <?= thread_escape($thread['creator_name']) ?></span>
            </div>
        </section>

        <div class="linked-reports" style="margin-top: var(--space-medium);">
            <div class="linked-reports__heading">
                <div><p class="threads-eyebrow">EDIT THREAD</p><h2>Title, description &amp; status</h2></div>
            </div>

            <form method="POST" action="official-edit-thread-process.php">
                <input type="hidden" name="thread_id" value="<?= (int) $threadId ?>">

                <div class="verification-panel" style="border: none; padding: 0;">
                    <div class="verification-grid">
                        <div class="verification-field verification-field--full">
                            <label for="edit-thread-title">Thread Title</label>
                            <input type="text" id="edit-thread-title" name="title" value="<?= thread_escape((string) $thread['title']) ?>" maxlength="255" required>
                        </div>

                        <div class="verification-field">
                            <label for="edit-thread-status">Status</label>
                            <select id="edit-thread-status" name="status">
                                <?php foreach ($allowedThreadStatuses as $statusOption): ?>
                                    <option value="<?= $statusOption ?>" <?= (string) $thread['status'] === $statusOption ? 'selected' : '' ?>><?= $statusOption ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small>
                                Setting <strong>Resolved</strong> marks every linked report as Resolved too.
                                Archiving only changes this thread. linked reports keep their own status,
                                since a report record has no "Archived" state.
                            </small>
                        </div>

                        <div class="verification-field">
                            <label>Location &amp; Category</label>
                            <div style="padding: var(--space-small) 0; color: var(--color4); font-size: var(--font-small);">
                                <?= thread_escape(thread_location_label($thread)) ?> &middot; <?= thread_escape($thread['category_name']) ?>
                            </div>
                            <small>Not editable here. Reassign individual reports from Edit Report instead.</small>
                        </div>

                        <div class="verification-field verification-field--full">
                            <label for="edit-thread-description">Description / Remarks</label>
                            <textarea id="edit-thread-description" name="description" placeholder="Explain the incident this thread is tracking..."><?= thread_escape((string) ($thread['description'] ?? '')) ?></textarea>
                        </div>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: var(--space-small); margin-top: var(--space-medium);">
                    <button type="button" class="btn-post-report" style="background-color: var(--color3); color: var(--colorText);" onclick="window.location.href='official-home.php'">Cancel</button>
                    <button type="submit" class="btn-post-report">Save Changes</button>
                </div>
            </form>
        </div>

        <section class="linked-reports" style="margin-top: var(--space-medium);">
            <div class="linked-reports__heading">
                <div><p class="threads-eyebrow">COMMUNITY REPORTS</p><h2>Reports linked to this thread</h2></div>
            </div>

            <?php if ($reports === []): ?>
                <div class="thread-state thread-state--compact"><i class="fa-regular fa-file-lines"></i><h3>No linked reports yet</h3><p>Link reports to this thread from each report's Edit Report page.</p></div>
            <?php else: ?>
                <div class="report-list">
                    <?php foreach ($reports as $report): ?>
                        <a href="official-edit-report.php?id=<?= (int) $report['report_id'] ?>" style="text-decoration: none; color: inherit;">
                            <article class="linked-report-card">
                                <div class="linked-report-card__topline">
                                    <span class="report-status report-status--<?= strtolower(thread_escape((string) $report['status'])) ?>"><?= thread_escape($report['status']) ?></span>
                                    <span><?= thread_escape(thread_date_label($report['created_at'])) ?></span>
                                </div>
                                <h3><?= thread_escape($report['title']) ?></h3>
                                <p><?= thread_escape($report['description']) ?></p>
                                <div class="linked-report-card__meta">
                                    <span><i class="fa-solid fa-user"></i><?= thread_escape($report['username'] ?: $report['reporter_name']) ?></span>
                                    <span><i class="fa-solid fa-location-dot"></i><?= thread_escape($report['district'] . ', ' . $report['city']) ?></span>
                                    <span><i class="fa-solid fa-pen-to-square"></i>Click to review</span>
                                </div>
                            </article>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</main>
</div>
</body>
</html>