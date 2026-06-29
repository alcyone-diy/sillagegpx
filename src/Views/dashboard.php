<?php
$pageTitle = 'Dashboard - SillageGPX';
ob_start();
?>

<div class="dashboard-header">
    <h2>Your Logbook</h2>
    <a href="?route=create_trip" class="btn btn-primary">+ Log New Trip</a>
</div>

<?php if (empty($trips)): ?>
    <div class="empty-state glass-card">
        <div class="empty-icon">🌊</div>
        <h3>No trips logged yet</h3>
        <p>Start recording your adventures on the water by creating your first trip and uploading a GPX file.</p>
        <a href="?route=create_trip" class="btn btn-primary mt-4">Log your first trip</a>
    </div>
<?php else: ?>
    <div class="trips-grid">
        <?php foreach ($trips as $trip): ?>
            <div class="trip-card glass-card" onclick="window.location.href='?route=trip&id=<?= $trip->id ?>'" style="cursor: pointer;">
                <div class="trip-card-header">
                    <h3 style="margin-bottom: 0; color: var(--accent-primary);"><?= htmlspecialchars($trip->title) ?></h3>
                    <span class="badge badge-<?= htmlspecialchars($trip->visibility) ?>"><?= htmlspecialchars($trip->visibility) ?></span>
                </div>
                
                <div class="trip-card-body" style="margin-top: 1rem;">
                    <?php if ($trip->boat_name): ?>
                        <p class="text-sm"><strong>Boat:</strong> <?= htmlspecialchars($trip->boat_name) ?></p>
                    <?php endif; ?>
                    
                    <?php if ($trip->start_date): ?>
                        <p class="text-sm"><strong>Date:</strong> <?= htmlspecialchars($trip->start_date) ?> 
                        <?php if ($trip->end_date && $trip->end_date != $trip->start_date) echo ' to ' . htmlspecialchars($trip->end_date); ?>
                        </p>
                    <?php endif; ?>
                    
                    <div class="trip-stats text-muted text-sm mt-2">
                        <span>👁️ <?= $trip->views_count ?> views</span>
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
