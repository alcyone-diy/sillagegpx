<?php
$pageTitle = __('dashboard') . ' - ' . __('site_title');
ob_start();
?>

<div class="dashboard-header">
    <h2><?= __('your_logbook') ?></h2>
    <a href="?route=create_trip" class="btn btn-primary"><?= __('log_new_trip') ?></a>
</div>

<?php if (empty($trips)): ?>
    <div class="empty-state glass-card">
        <div class="empty-icon">🌊</div>
        <h3><?= __('no_trips') ?></h3>
        <p><?= __('no_trips_desc') ?></p>
        <a href="?route=create_trip" class="btn btn-primary mt-4"><?= __('log_first_trip') ?></a>
    </div>
<?php else: ?>
    <div class="trips-grid">
        <?php foreach ($trips as $trip): ?>
            <div class="trip-card glass-card" onclick="window.location.href='?route=trip&id=<?= $trip->id ?>'" style="cursor: pointer;">
                <div class="trip-card-header">
                    <h3 style="margin-bottom: 0; color: var(--accent-primary);"><?= htmlspecialchars($trip->title) ?></h3>
                    <span class="badge badge-<?= htmlspecialchars($trip->visibility) ?>"><?= htmlspecialchars(__($trip->visibility)) ?></span>
                </div>
                
                <div class="trip-card-body" style="margin-top: 1rem;">
                    <?php if ($trip->boat_name): ?>
                        <p class="text-sm"><strong><?= __('boat') ?>:</strong> <?= htmlspecialchars($trip->boat_name) ?></p>
                    <?php endif; ?>
                    
                    <?php if ($trip->start_date): ?>
                        <p class="text-sm"><strong><?= __('date') ?>:</strong> <?= htmlspecialchars($trip->start_date) ?> 
                        <?php if ($trip->end_date && $trip->end_date != $trip->start_date) echo ' ' . __('to') . ' ' . htmlspecialchars($trip->end_date); ?>
                        </p>
                        <?php
                        $daysCount = 1;
                        if ($trip->end_date) {
                            $start = new DateTime($trip->start_date);
                            $end = new DateTime($trip->end_date);
                            $daysCount = $start->diff($end)->days + 1;
                        }
                        ?>
                        <p class="text-sm"><strong><?= __('duration') ?>:</strong> <?= $daysCount ?> <?= $daysCount > 1 ? __('days') : __('day') ?></p>
                    <?php endif; ?>
                    
                    <div class="trip-stats text-muted text-sm mt-2">
                        <span>👁️ <?= $trip->views_count ?> <?= __('views') ?></span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
