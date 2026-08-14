<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/thread-query.php';
require_once __DIR__ . '/../../includes/report-feed.php';
require_once __DIR__ . '/../../includes/report-card.php';
require_once __DIR__ . '/../../includes/user-activity-query.php';
require_once __DIR__ . '/../../includes/user-activity-layout.php';

require_login('../auth/login.php');

$currentUserId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$currentUserId = $currentUserId === false ? null : $currentUserId;
$categories = [];
$locationsByCity = [];
$savedLocations = [];
$reports = [];
$mediaByReport = [];
$errorMessage = null;

try {
    $db = thread_db();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $currentUserId !== null) {
        $submittedLocationIds = $_POST['location_ids'] ?? [];
        $locationIds = is_array($submittedLocationIds)
            ? array_map('intval', $submittedLocationIds)
            : [];
        activity_replace_saved_locations($db, $currentUserId, $locationIds);
        header('Location: user-saved-locations.php?updated=1');
        exit;
    }

    $categories = fetch_categories($db);
    $locationsByCity = activity_fetch_locations_grouped_by_city($db);
    if ($currentUserId !== null) {
        $savedLocations = activity_fetch_saved_locations($db, $currentUserId);
        $savedLocationIds = array_map(static fn(array $location): int => (int) $location['location_id'], $savedLocations);
        $reportData = fetch_reports_and_media_by_location_ids($db, $savedLocationIds, $currentUserId);
        $reports = $reportData['reports'];
        $mediaByReport = $reportData['mediaByReport'];
    }
} catch (Throwable $error) {
    $errorMessage = 'Unable to update your saved locations right now.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saved Locations - LissentialManila</title>
    <link rel="stylesheet" href="../../style/user/activity.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <script src="../shared-js/media-carousel.js" defer></script>
    <script src="../shared-js/report-actions.js" defer></script>
</head>
<body>
<?php render_activity_navigation($categories, 'saved'); ?>
<div class="activity-main"><main>
    <header class="activity-heading"><h1>Saved Locations</h1><p>Choose the locations whose reports you want to follow.</p></header>
    <section class="activity-panel">
        <h2>Your locations</h2><p>Pick a city, then a barangay/district in that city to add it to your saved list.</p>
        <?php if ($errorMessage !== null): ?><p class="comment-form__error"><?= activity_layout_escape($errorMessage) ?></p><?php endif; ?>
        <?php if (isset($_GET['updated'])): ?><p style="color: var(--colorGreen);">Saved locations updated.</p><?php endif; ?>
        <form method="post" id="saved-locations-form">
            <div class="location-picker">
                <div class="location-picker-row">
                    <select id="city-select" aria-label="City">
                        <option value="">Select city&hellip;</option>
                        <?php foreach (array_keys($locationsByCity) as $city): ?>
                            <option value="<?= activity_layout_escape($city) ?>"><?= activity_layout_escape($city) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select id="barangay-select" aria-label="Barangay / District" disabled>
                        <option value="">Select barangay&hellip;</option>
                    </select>
                    <button type="button" id="add-location-btn" class="activity-submit" disabled>Add</button>
                </div>
                <ul class="selected-locations" id="selected-locations"></ul>
                <div id="hidden-location-inputs"></div>
            </div>
            <button type="submit" class="activity-submit">Save Locations</button>
        </form>
    </section>
    <?php if ($reports === []): ?>
        <section class="activity-panel"><h2>No saved-location reports</h2><p>Add a location above to view its reports.</p></section>
    <?php else: ?>
        <?php foreach ($reports as $report): ?>
            <?php $reportId = (int) $report['report_id']; ?>
            <?php render_report_card($report, $mediaByReport[$reportId] ?? [], 'user-report-details.php?id=' . $reportId, 'report-action.php', '../../'); ?>
        <?php endforeach; ?>
    <?php endif; ?>
</main></div>
<script>
    const locationsByCity = <?= json_encode($locationsByCity, JSON_HEX_TAG | JSON_HEX_APOS) ?>;
    const initiallySaved = <?= json_encode(array_map(static fn(array $location): array => [
        'location_id' => (int) $location['location_id'],
        'label' => activity_location_label($location),
    ], $savedLocations), JSON_HEX_TAG | JSON_HEX_APOS) ?>;

    const citySelect = document.getElementById('city-select');
    const barangaySelect = document.getElementById('barangay-select');
    const addBtn = document.getElementById('add-location-btn');
    const list = document.getElementById('selected-locations');
    const hiddenInputs = document.getElementById('hidden-location-inputs');
    const selected = new Map(initiallySaved.map((location) => [location.location_id, location.label]));

    function renderChips() {
        list.innerHTML = '';
        hiddenInputs.innerHTML = '';

        selected.forEach((label, locationId) => {
            const chip = document.createElement('li');
            chip.className = 'location-chip';
            chip.innerHTML = `<span>${label}</span>`;

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'location-chip-remove';
            removeBtn.setAttribute('aria-label', `Remove ${label}`);
            removeBtn.textContent = '\u00d7';
            removeBtn.addEventListener('click', () => {
                selected.delete(locationId);
                renderChips();
            });
            chip.appendChild(removeBtn);
            list.appendChild(chip);

            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'location_ids[]';
            hidden.value = String(locationId);
            hiddenInputs.appendChild(hidden);
        });
    }

    citySelect.addEventListener('change', () => {
        const city = citySelect.value;
        barangaySelect.innerHTML = '<option value="">Select barangay&hellip;</option>';

        if (city && locationsByCity[city]) {
            locationsByCity[city].forEach((location) => {
                const option = document.createElement('option');
                option.value = String(location.location_id);
                option.textContent = location.district + (location.landmark ? ` (${location.landmark})` : '');
                barangaySelect.appendChild(option);
            });
        }

        barangaySelect.disabled = !city;
        addBtn.disabled = true;
    });

    barangaySelect.addEventListener('change', () => {
        addBtn.disabled = !barangaySelect.value;
    });

    addBtn.addEventListener('click', () => {
        const locationId = parseInt(barangaySelect.value, 10);
        if (!locationId) {
            return;
        }
        const city = citySelect.value;
        const district = barangaySelect.options[barangaySelect.selectedIndex].textContent.split(' (')[0];
        selected.set(locationId, `${district}, ${city}`);
        renderChips();
    });

    renderChips();
</script>
<script src="../shared-js/notifications.js" defer></script><script src="../shared-js/navbar-user-menu.js" defer></script>
</body>
</html>