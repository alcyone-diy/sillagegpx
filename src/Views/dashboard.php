<?php
$pageTitle = __('dashboard') . ' - ' . __('site_title');
$decPoint = ($_SESSION['lang'] ?? 'fr') === 'fr' ? ',' : '.';
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
    <div class="dashboard-stats">
        <div class="glass-card dashboard-stat-card">
            <div class="dashboard-stat-icon">⛵</div>
            <div class="dashboard-stat-info">
                <div class="dashboard-stat-value">
                    <?= number_format($stats['total_nm'] ?? 0, 1, $decPoint, ' ') ?>
                    <span class="dashboard-stat-unit"><?= __('distance_unit') ?></span>
                </div>
                <div class="dashboard-stat-label"><?= __('total_distance') ?></div>
                <div class="dashboard-stat-sub">
                    <span><?= $stats['total_trips'] ?? 0 ?> <?= ($stats['total_trips'] ?? 0) > 1 ? __('navs_count') : __('nav_count') ?></span>
                    <span class="stat-bullet">•</span>
                    <span><?= $stats['total_days'] ?? 0 ?> <?= ($stats['total_days'] ?? 0) > 1 ? __('days_count') : __('day_count') ?></span>
                </div>
            </div>
        </div>
        <div class="glass-card dashboard-stat-card">
            <div class="dashboard-stat-icon">🧑‍✈️</div>
            <div class="dashboard-stat-info">
                <div class="dashboard-stat-value">
                    <?= number_format($stats['skipper_nm'] ?? 0, 1, $decPoint, ' ') ?>
                    <span class="dashboard-stat-unit"><?= __('distance_unit') ?></span>
                </div>
                <div class="dashboard-stat-label"><?= __('as_skipper') ?></div>
                <div class="dashboard-stat-sub">
                    <span><?= $stats['skipper_trips'] ?? 0 ?> <?= ($stats['skipper_trips'] ?? 0) > 1 ? __('navs_count') : __('nav_count') ?></span>
                    <span class="stat-bullet">•</span>
                    <span><?= $stats['skipper_days'] ?? 0 ?> <?= ($stats['skipper_days'] ?? 0) > 1 ? __('days_count') : __('day_count') ?></span>
                </div>
            </div>
        </div>
        <div class="glass-card dashboard-stat-card">
            <div class="dashboard-stat-icon">👥</div>
            <div class="dashboard-stat-info">
                <div class="dashboard-stat-value">
                    <?= number_format($stats['crew_nm'] ?? 0, 1, $decPoint, ' ') ?>
                    <span class="dashboard-stat-unit"><?= __('distance_unit') ?></span>
                </div>
                <div class="dashboard-stat-label"><?= __('as_crew') ?></div>
                <div class="dashboard-stat-sub">
                    <span><?= $stats['crew_trips'] ?? 0 ?> <?= ($stats['crew_trips'] ?? 0) > 1 ? __('navs_count') : __('nav_count') ?></span>
                    <span class="stat-bullet">•</span>
                    <span><?= $stats['crew_days'] ?? 0 ?> <?= ($stats['crew_days'] ?? 0) > 1 ? __('days_count') : __('day_count') ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="trips-grid">
        <?php foreach ($trips as $trip): ?>
            <a href="?route=trip&id=<?= $trip->id ?>" class="trip-card glass-card">
                <div class="trip-card-header">
                    <h3 style="margin-bottom: 0; color: var(--accent-primary);"><?= htmlspecialchars($trip->title) ?></h3>
                    <div class="d-flex align-items-center" style="gap: 0.4rem; flex-wrap: wrap; justify-content: flex-end;">
                        <span class="badge <?= $trip->isSkipper() ? 'badge-skipper' : 'badge-crew' ?>">
                            <?= $trip->isSkipper() ? '🧑‍✈️ ' . __('is_skipper') : '👥 ' . __('crew_member') ?>
                        </span>
                        <span class="badge badge-<?= htmlspecialchars($trip->visibility) ?>"><?= htmlspecialchars(__($trip->visibility)) ?></span>
                    </div>
                </div>
                
                <div class="trip-card-body" style="margin-top: 1rem;">
                    <?php if ($trip->boat_name): ?>
                        <p class="text-sm"><strong><?= __('boat') ?>:</strong> <?= htmlspecialchars($trip->boat_name) ?></p>
                    <?php endif; ?>
                    
                    <?php if ($trip->start_date): ?>
                        <p class="text-sm"><strong><?= __('date') ?>:</strong> <?= htmlspecialchars($trip->start_date) ?> 
                        <?php if ($trip->end_date && $trip->end_date != $trip->start_date) echo ' ' . __('to') . ' ' . htmlspecialchars($trip->end_date); ?>
                        </p>
                        <?php $daysCount = $trip->getDurationDays(); ?>
                        <p class="text-sm"><strong><?= __('duration') ?>:</strong> <?= $daysCount ?> <?= $daysCount > 1 ? __('days') : __('day') ?></p>
                    <?php endif; ?>
                    
                    <div class="trip-stats text-muted text-sm mt-2">
                        <span>👁️ <?= $trip->views_count ?> <?= __('views') ?></span>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
