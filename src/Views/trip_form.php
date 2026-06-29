<?php
$isEdit = isset($trip) && $trip;
$pageTitle = $isEdit ? __('edit_trip') . ' - ' . __('site_title') : __('log_a_trip') . ' - ' . __('site_title');
ob_start();
?>

<div class="form-container">
    <div class="glass-card mb-4" style="text-align: center;">
        <h2 style="margin-bottom: 0; font-size: 2rem; font-weight: 700;"><?= $isEdit ? __('edit_trip') : __('log_a_trip') ?></h2>
    </div>
    
    <form action="?route=<?= $isEdit ? 'edit_trip' : 'create_trip' ?>" method="POST" enctype="multipart/form-data" class="main-form">
        <?php if ($isEdit): ?>
            <input type="hidden" name="trip_id" value="<?= $trip->id ?>">
        <?php endif; ?>
        
        <div class="form-section glass-card mb-4">
            <h3 style="margin-bottom: 1.5rem; padding-bottom: 0.5rem; border-bottom: 1px solid rgba(0,0,0,0.05);"><?= __('general_details') ?></h3>
            
            <div class="form-group">
                <label for="title"><?= __('trip_title') ?> <span class="required">*</span></label>
                <input type="text" id="title" name="title" required class="form-control glass-input" placeholder="<?= __('title_placeholder') ?>" value="<?= $isEdit ? htmlspecialchars($trip->title) : '' ?>">
            </div>
            
            <div class="form-group">
                <label for="boat_name"><?= __('boat_name') ?></label>
                <input type="text" id="boat_name" name="boat_name" class="form-control glass-input" value="<?= $isEdit ? htmlspecialchars($trip->boat_name ?? '') : '' ?>">
            </div>
            
            <div class="form-group">
                <label for="comment"><?= __('captains_log') ?></label>
                <textarea id="comment" name="comment" rows="5" class="form-control glass-input" placeholder="<?= __('comment_placeholder') ?>"><?= $isEdit ? htmlspecialchars($trip->comment ?? '') : '' ?></textarea>
            </div>
        </div>
        
        <div class="form-section glass-card mb-4">
            <h3 style="margin-bottom: 1.5rem; padding-bottom: 0.5rem; border-bottom: 1px solid rgba(0,0,0,0.05);"><?= __('tracks_gpx') ?></h3>
            
            <?php if ($isEdit && !empty($existingSteps)): ?>
                <div class="existing-tracks mb-4">
                    <p class="text-sm"><strong><?= __('existing_tracks') ?></strong></p>
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
            
            <p class="text-muted text-sm mb-2"><?= __('upload_help') ?></p>
            
            <div class="form-group">
                <div class="file-upload-wrapper glass-input" id="dropZone">
                    <input type="file" id="gpx_files" name="gpx_files[]" multiple accept=".gpx" class="file-input">
                    <div class="file-upload-display">
                        <span class="file-icon">📁</span>
                        <span class="file-text"><?= $isEdit ? __('choose_more_files') : __('choose_files') ?></span>
                    </div>
                </div>
                <ul id="fileList" class="file-list mt-2"></ul>
            </div>
        </div>
        
        <div class="form-section glass-card mb-4">
            <h3 style="margin-bottom: 1.5rem; padding-bottom: 0.5rem; border-bottom: 1px solid rgba(0,0,0,0.05);"><?= __('privacy') ?></h3>
            <div class="radio-group">
                <label class="radio-label">
                    <input type="radio" name="visibility" value="private" <?= (!$isEdit || $trip->visibility === 'private') ? 'checked' : '' ?>>
                    <div class="radio-content">
                        <strong><?= __('private') ?></strong>
                        <span class="text-sm"><?= __('private_desc') ?></span>
                    </div>
                </label>
                <label class="radio-label">
                    <input type="radio" name="visibility" value="unlisted" <?= ($isEdit && $trip->visibility === 'unlisted') ? 'checked' : '' ?>>
                    <div class="radio-content">
                        <strong><?= __('unlisted') ?></strong>
                        <span class="text-sm"><?= __('unlisted_desc') ?></span>
                    </div>
                </label>
                <label class="radio-label">
                    <input type="radio" name="visibility" value="public" <?= ($isEdit && $trip->visibility === 'public') ? 'checked' : '' ?>>
                    <div class="radio-content">
                        <strong><?= __('public') ?></strong>
                        <span class="text-sm"><?= __('public_desc') ?></span>
                    </div>
                </label>
            </div>
        </div>
        
        <div class="form-actions glass-card" style="display: flex; justify-content: flex-end; gap: 1rem; align-items: center;">
            <a href="<?= $isEdit ? '?route=trip&id=' . $trip->id : '?route=dashboard' ?>" class="btn btn-outline"><?= __('cancel') ?></a>
            <button type="submit" class="btn btn-primary" style="margin: 0;"><?= $isEdit ? __('save_changes') : __('save_trip') ?></button>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
