<?php
/** @var array<string, mixed> $thread */
$reportCount = (int) ($thread['actual_report_count'] ?? $thread['total_reports'] ?? 0);
$status = (string) ($thread['status'] ?? 'Active');
$statusClass = strtolower($status);
$description = trim((string) ($thread['description'] ?? ''));
?>
<article class="thread-card">
    <div class="thread-card__topline">
        <span class="thread-status thread-status--<?= thread_escape($statusClass) ?>">
            <i class="fa-solid fa-circle"></i>
            <?= thread_escape($status) ?>
        </span>
        <span class="thread-card__updated">Updated <?= thread_escape(thread_date_label($thread['updated_at'] ?? null)) ?></span>
    </div>

    <h2 class="thread-card__title"><?= thread_escape($thread['title'] ?? 'Untitled incident') ?></h2>

    <div class="thread-card__meta">
        <span><i class="fa-solid fa-location-dot"></i><?= thread_escape(thread_location_label($thread)) ?></span>
        <span><i class="fa-solid fa-layer-group"></i><?= thread_escape($thread['category_name'] ?? 'Uncategorized') ?></span>
    </div>

    <p class="thread-card__description">
        <?= thread_escape($description !== '' ? $description : 'No official description has been added to this thread yet.') ?>
    </p>

    <div class="thread-card__footer">
        <div class="thread-card__stats" aria-label="Thread report statistics">
            <span><strong><?= $reportCount ?></strong> linked report<?= $reportCount === 1 ? '' : 's' ?></span>
            <span><strong><?= (int) ($thread['verified_reports'] ?? 0) ?></strong> verified</span>
        </div>
        <a class="thread-card__button" href="thread-details.php?id=<?= (int) $thread['thread_id'] ?>">
            View Thread <i class="fa-solid fa-arrow-right"></i>
        </a>
    </div>
</article>
