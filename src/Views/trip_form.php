<?php
$isEdit = isset($trip) && $trip;
$pageTitle = $isEdit ? 'Edit Trip - SillageGPX' : 'Log a Trip - SillageGPX';
ob_start();
?>

<div class="form-container">
    <div class="glass-card">
        <h2><?= $isEdit ? 'Edit Trip' : 'Log a New Trip' ?></h2>
        
        <form action="?route=<?= $isEdit ? 'edit_trip' : 'create_trip' ?>" method="POST" enctype="multipart/form-data" class="main-form">
            <?php if ($isEdit): ?>
                <input type="hidden" name="trip_id" value="<?= $trip->id ?>">
            <?php endif; ?>
            
            <div class="form-section">
                <h3>General Details</h3>
                
                <div class="form-group">
                    <label for="title">Trip Title <span class="required">*</span></label>
                    <input type="text" id="title" name="title" required class="form-control glass-input" placeholder="e.g., Summer Cruise in Brittany" value="<?= $isEdit ? htmlspecialchars($trip->title) : '' ?>">
                </div>
                
                <div class="form-group">
                    <label for="boat_name">Boat Name</label>
                    <input type="text" id="boat_name" name="boat_name" class="form-control glass-input" value="<?= $isEdit ? htmlspecialchars($trip->boat_name ?? '') : '' ?>">
                </div>
                
                <div class="form-group">
                    <label for="comment">Captain's Log</label>
                    <textarea id="comment" name="comment" rows="5" class="form-control glass-input" placeholder="Write your comments about this trip..."><?= $isEdit ? htmlspecialchars($trip->comment ?? '') : '' ?></textarea>
                </div>
            </div>
            
            <div class="form-section">
                <h3><?= $isEdit ? 'Tracks (GPX)' : 'Tracks (GPX)' ?></h3>
                
                <?php if ($isEdit && !empty($existingSteps)): ?>
                    <div class="existing-tracks mb-4">
                        <p class="text-sm"><strong>Existing Tracks:</strong></p>
                        <ul class="file-list mt-2" id="existingTracksList">
                            <?php foreach ($existingSteps as $step): ?>
                                <li class="file-list-item glass" id="step-<?= $step->id ?>">
                                    <span class="file-name"><?= htmlspecialchars($step->title) ?></span>
                                    <button type="button" class="remove-file delete-existing-track" data-id="<?= $step->id ?>" title="Delete this track">&times;</button>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <p class="text-muted text-sm">Upload one or multiple GPX files. Each file will be processed as a separate 'step' of your trip.</p>
                
                <div class="form-group">
                    <div class="file-upload-wrapper glass-input" id="dropZone">
                        <input type="file" id="gpx_files" name="gpx_files[]" multiple accept=".gpx" class="file-input">
                        <div class="file-upload-display">
                            <span class="file-icon">📁</span>
                            <span class="file-text">Choose <?= $isEdit ? 'more ' : '' ?>GPX files or drag & drop them here</span>
                        </div>
                    </div>
                    <ul id="fileList" class="file-list mt-2"></ul>
                </div>
            </div>
            
            <div class="form-section">
                <h3>Privacy</h3>
                <div class="radio-group">
                    <label class="radio-label">
                        <input type="radio" name="visibility" value="private" <?= (!$isEdit || $trip->visibility === 'private') ? 'checked' : '' ?>>
                        <div class="radio-content">
                            <strong>Private</strong>
                            <span class="text-sm">Only you can see this trip.</span>
                        </div>
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="visibility" value="unlisted" <?= ($isEdit && $trip->visibility === 'unlisted') ? 'checked' : '' ?>>
                        <div class="radio-content">
                            <strong>Unlisted</strong>
                            <span class="text-sm">Anyone with the unique link can view it.</span>
                        </div>
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="visibility" value="public" <?= ($isEdit && $trip->visibility === 'public') ? 'checked' : '' ?>>
                        <div class="radio-content">
                            <strong>Public</strong>
                            <span class="text-sm">Visible on your public profile.</span>
                        </div>
                    </label>
                </div>
            </div>
            
            <div class="form-actions">
                <a href="<?= $isEdit ? '?route=trip&id=' . $trip->id : '?route=dashboard' ?>" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Save Changes' : 'Save Trip' ?></button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
