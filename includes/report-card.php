<?php
declare(strict_types=1);

function report_card_escape(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * @param array<string,mixed> $report
 * @param list<array{file_url:string,file_type:string}> $mediaItems
 */
function render_report_card(array $report, array $mediaItems, string $detailsUrl, string $actionUrl, string $assetBase = '', ?string $editUrl = null): void
{
    $reportId = (int) ($report['report_id'] ?? 0);
    $displayName = trim((string) ($report['username'] ?? ''));
    if ($displayName === '') {
        $displayName = trim((string) ($report['first_name'] ?? '') . ' ' . (string) ($report['last_name'] ?? ''));
    }
    $displayName = $displayName !== '' ? $displayName : 'Anonymous';

    $locationLabel = trim((string) ($report['district'] ?? '') . ', ' . (string) ($report['city'] ?? ''), ', ');
    $status = (string) ($report['status'] ?? 'Pending');
    $statusClass = strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', $status));
    $isVerified = !empty($report['verified_by']) || in_array($status, ['Verified', 'Resolved'], true);
    $dateLabels = report_date_time_labels((string) ($report['created_at'] ?? ''));
    ?>
    <section class="post">
        <a href="<?= report_card_escape($detailsUrl) ?>" class="post-link post-content-link">
            <div class="profile-details">
                <!-- <div class="post-pfp"><img src="<?= report_card_escape($assetBase) ?>assets/user_images/user1.jpg" alt=""></div> -->
                <span class="username"><?= report_card_escape($displayName) ?></span>
                <span>&bull;</span>
                <span class="hours-ago"><?= report_card_escape(relative_time_label((string) ($report['created_at'] ?? ''))) ?></span>
            </div>
            <div class="post-details">
                <div class="post-details-box"><i class="fa-solid fa-location-dot" style="color: var(--colorRed);"></i><span><?= report_card_escape($locationLabel !== '' ? $locationLabel : 'Unknown location') ?></span></div>
                <div class="post-details-box post-details-box-category"><i class="fa-solid fa-layer-group" style="color: var(--colorYellow);"></i><span class="post-category-badge"><?= report_card_escape((string) ($report['category_name'] ?? 'Uncategorized')) ?></span></div>
                <div class="post-details-box"><i class="fa-solid fa-clock" style="color: var(--colorGreen);"></i><span><?= report_card_escape($dateLabels['date']) ?></span> | <span><?= report_card_escape($dateLabels['time']) ?></span></div>
            </div>
            <div class="post-title-and-description">
                <h2><span class="post-title"><?= report_card_escape((string) ($report['title'] ?? 'Untitled report')) ?></span></h2>
                <span class="post-description"><?= report_card_escape((string) ($report['description'] ?? '')) ?></span>
            </div>
            <?php if ($mediaItems !== []): ?>
                <div class="post-media-carousel">
                    <div class="carousel-container">
                        <?php foreach ($mediaItems as $media): ?>
                            <?php $mediaPath = normalize_media_url((string) ($media['file_url'] ?? '')); ?>
                            <div class="carousel-slide">
                                <?php if (($media['file_type'] ?? 'photo') === 'video'): ?>
                                    <video src="<?= report_card_escape($assetBase . $mediaPath) ?>" controls muted playsinline></video>
                                <?php else: ?>
                                    <img src="<?= report_card_escape($assetBase . $mediaPath) ?>" alt="Report attachment">
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($mediaItems) > 1): ?>
                        <button type="button" class="carousel-btn prev" aria-label="Previous slide" onclick="moveCarousel(this, -1)"><i class="fa-solid fa-chevron-left"></i></button>
                        <button type="button" class="carousel-btn next" aria-label="Next slide" onclick="moveCarousel(this, 1)"><i class="fa-solid fa-chevron-right"></i></button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </a>
        <div class="post-buttons">
            <div class="post-buttons-left">
                <?php $hasUpvoted = !empty($report['has_upvoted']); ?>
                <button type="button" class="post-upvote<?= $hasUpvoted ? ' is-active' : '' ?>" data-report-action="upvote" data-report-id="<?= $reportId ?>" data-action-url="<?= report_card_escape($actionUrl) ?>" aria-pressed="<?= $hasUpvoted ? 'true' : 'false' ?>"><i class="fa-solid fa-square-caret-up"></i><span><?= (int) ($report['upvote_count'] ?? 0) ?></span></button>
                <button type="button" class="comment post-comment" data-report-details-url="<?= report_card_escape($detailsUrl . '#comments') ?>"><i class="fa-solid fa-comment-dots"></i><span><?= (int) ($report['comment_count'] ?? 0) ?></span></button>
                <?php $hasResolved = !empty($report['has_resolved']); ?>
                <button type="button" class="post-resolved<?= $hasResolved ? ' is-active' : '' ?>" data-report-action="resolved" data-report-id="<?= $reportId ?>" data-action-url="<?= report_card_escape($actionUrl) ?>" aria-pressed="<?= $hasResolved ? 'true' : 'false' ?>"><i class="fa-solid fa-circle-check"></i>Resolved | <span><?= (int) ($report['resolved_count'] ?? 0) ?></span></button>
                <?php if ($editUrl !== null): ?>
                    <button type="button" class="post-edit" onclick="window.location.href='<?= report_card_escape($editUrl) ?>'"><i class="fa-solid fa-pen-to-square"></i> Edit</button>
                <?php endif; ?>
            </div>
            <div class="post-buttons-right">
                <?php if ($isVerified): ?><span class="verified"><i class="fa-solid fa-user-check"></i> Verified by Officials</span><?php endif; ?>
                <span class="status status-pill status-<?= report_card_escape($statusClass !== '' ? $statusClass : 'pending') ?>">Status: <?= report_card_escape(strtoupper($status)) ?></span>
            </div>
        </div>
    </section>
    <?php
}