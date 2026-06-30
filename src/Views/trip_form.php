<?php
$isEdit = isset($trip) && $trip;
$pageTitle = $isEdit ? __('edit_trip') . ' - ' . __('site_title') : __('log_a_trip') . ' - ' . __('site_title');
ob_start();
?>
<style>
.link-entry {
    transition: all 0.2s ease;
    border-radius: 8px;
    padding: 0.5rem;
    margin-left: -0.5rem;
    margin-right: -0.5rem;
    width: calc(100% + 1rem);
}
.link-entry:has(.remove-link:hover) {
    background-color: rgba(220, 53, 69, 0.04);
}
.link-entry:has(.remove-link:hover) .glass-input {
    border-color: rgba(220, 53, 69, 0.4) !important;
    box-shadow: 0 4px 6px -1px rgba(220, 53, 69, 0.1);
}
.link-entry:has(.remove-link:hover) input {
    color: #dc3545;
}
/* Fix focus ring for combined input bubble */
.link-entry .glass-input:has(.link-field-inner:focus) {
    border-color: var(--accent-primary);
    box-shadow: 0 0 0 3px var(--accent-glow);
}
.link-entry .link-field-inner:focus {
    outline: none !important;
    box-shadow: none !important;
}
@media (max-width: 768px) {
    .link-entry .glass-input {
        flex-direction: column;
    }
    .link-entry .glass-input > div {
        width: 100% !important;
        height: 1px;
        margin: 0 !important;
    }
}
.autocomplete-wrapper {
    position: relative;
}
.autocomplete-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    margin-top: 0.25rem;
    background: var(--bg-glass);
    border: 1px solid var(--border-glass);
    border-radius: var(--radius-md, 8px);
    box-shadow: var(--shadow-glass);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    z-index: 1000;
    max-height: 200px;
    overflow-y: auto;
    display: none;
}
.autocomplete-dropdown.show {
    display: block;
}
.autocomplete-item {
    padding: 0.75rem 1rem;
    cursor: pointer;
    transition: background 0.2s;
    color: var(--text-primary);
    border-bottom: 1px solid rgba(0,0,0,0.05);
}
.autocomplete-item:last-child {
    border-bottom: none;
}
.autocomplete-item:hover, .autocomplete-item.active {
    background: var(--bg-glass-hover);
    color: var(--accent-primary);
}
</style>

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
                <div class="autocomplete-wrapper">
                    <input type="text" id="boat_name" name="boat_name" class="form-control glass-input" value="<?= $isEdit ? htmlspecialchars($trip->boat_name ?? '') : '' ?>" autocomplete="off">
                    <?php if (isset($previousBoatNames) && !empty($previousBoatNames)): ?>
                    <div class="autocomplete-dropdown" id="boatNameDropdown">
                        <?php foreach ($previousBoatNames as $name): ?>
                            <div class="autocomplete-item" data-value="<?= htmlspecialchars($name) ?>">
                                <?= htmlspecialchars($name) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="form-group">
                <label for="comment"><?= __('captains_log') ?></label>
                <textarea id="comment" name="comment" rows="5" class="form-control glass-input" placeholder="<?= __('comment_placeholder') ?>"><?= $isEdit ? htmlspecialchars($trip->comment ?? '') : '' ?></textarea>
            </div>
        </div>
        
        <div class="form-section glass-card mb-4">
            <h3 style="margin-bottom: 1.5rem; padding-bottom: 0.5rem; border-bottom: 1px solid rgba(0,0,0,0.05);"><?= __('useful_links') ?></h3>
            
            <div id="linksContainer">
                <?php if ($isEdit && !empty($existingLinks)): ?>
                    <?php foreach ($existingLinks as $link): ?>
                        <div class="link-entry d-flex mb-3" style="gap: 1rem; align-items: center;">
                            <div class="glass-input d-flex flex-grow-1" style="padding: 0; overflow: hidden; align-items: stretch; border-radius: var(--radius-md, 8px);">
                                <input type="text" name="links_label[]" class="form-control flex-grow-1 link-field-inner" placeholder="<?= __('link_label') ?>" value="<?= htmlspecialchars($link->label ?? '') ?>" style="border: none; border-radius: 0; background: transparent; box-shadow: none;">
                                <div style="width: 1px; background: rgba(0,0,0,0.1); margin: 0.5rem 0;"></div>
                                <input type="url" name="links_url[]" class="form-control flex-grow-1 link-field-inner" placeholder="<?= __('link_url') ?>" value="<?= htmlspecialchars($link->url) ?>" required style="border: none; border-radius: 0; background: transparent; box-shadow: none;">
                            </div>
                            <button type="button" class="btn-icon remove-link" style="background: transparent; border: none; color: #dc3545; cursor: pointer; padding: 0.5rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: background 0.2s; opacity: 0.7; flex-shrink: 0;" onmouseover="this.style.background='rgba(220,53,69,0.1)'; this.style.opacity='1'" onmouseout="this.style.background='transparent'; this.style.opacity='0.7'" title="<?= __('remove_link') ?>">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="pointer-events: none;"><path d="M3 6h18"></path><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path></svg>
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <button type="button" id="addLinkBtn" class="btn btn-outline mt-2" style="font-size: 0.9rem; padding: 0.5rem 1rem;"><?= __('add_link') ?></button>
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
$linkUrlPlaceholder = addslashes(__('link_url'));
$linkLabelPlaceholder = addslashes(__('link_label'));
$removeLinkPlaceholder = addslashes(__('remove_link'));
$extraJs = <<<JS
<script>
document.addEventListener('DOMContentLoaded', function() {
    const linksContainer = document.getElementById('linksContainer');
    const addLinkBtn = document.getElementById('addLinkBtn');
    
    if (addLinkBtn) {
        addLinkBtn.addEventListener('click', function() {
            const div = document.createElement('div');
            div.className = 'link-entry d-flex mb-3';
            div.style.gap = '1rem';
            div.style.alignItems = 'center';
            
            div.innerHTML = `
                <div class="glass-input d-flex flex-grow-1" style="padding: 0; overflow: hidden; align-items: stretch; border-radius: var(--radius-md, 8px);">
                    <input type="text" name="links_label[]" class="form-control flex-grow-1 link-field-inner" placeholder="{$linkLabelPlaceholder}" style="border: none; border-radius: 0; background: transparent; box-shadow: none;">
                    <div style="width: 1px; background: rgba(0,0,0,0.1); margin: 0.5rem 0;"></div>
                    <input type="url" name="links_url[]" class="form-control flex-grow-1 link-field-inner" placeholder="{$linkUrlPlaceholder}" required style="border: none; border-radius: 0; background: transparent; box-shadow: none;">
                </div>
                <button type="button" class="btn-icon remove-link" style="background: transparent; border: none; color: #dc3545; cursor: pointer; padding: 0.5rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: background 0.2s; opacity: 0.7; flex-shrink: 0;" onmouseover="this.style.background='rgba(220,53,69,0.1)'; this.style.opacity='1'" onmouseout="this.style.background='transparent'; this.style.opacity='0.7'" title="{$removeLinkPlaceholder}">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="pointer-events: none;"><path d="M3 6h18"></path><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path></svg>
                </button>
            `;
            
            linksContainer.appendChild(div);
        });
    }
    
    if (linksContainer) {
        linksContainer.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-link')) {
                e.target.closest('.link-entry').remove();
            }
        });
    }

    const boatNameInput = document.getElementById('boat_name');
    const boatNameDropdown = document.getElementById('boatNameDropdown');
    
    if (boatNameInput && boatNameDropdown) {
        const items = boatNameDropdown.querySelectorAll('.autocomplete-item');
        
        boatNameInput.addEventListener('focus', function() {
            boatNameDropdown.classList.add('show');
            filterDropdown();
        });
        
        boatNameInput.addEventListener('input', function() {
            boatNameDropdown.classList.add('show');
            filterDropdown();
        });
        
        function filterDropdown() {
            const val = boatNameInput.value.toLowerCase();
            let visibleCount = 0;
            items.forEach(item => {
                const text = item.getAttribute('data-value').toLowerCase();
                if (text.includes(val)) {
                    item.style.display = 'block';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });
            if (visibleCount === 0) {
                boatNameDropdown.classList.remove('show');
            }
        }
        
        items.forEach(item => {
            item.addEventListener('click', function() {
                boatNameInput.value = this.getAttribute('data-value');
                boatNameDropdown.classList.remove('show');
                boatNameInput.focus();
            });
        });
        
        document.addEventListener('click', function(e) {
            if (!boatNameInput.contains(e.target) && !boatNameDropdown.contains(e.target)) {
                boatNameDropdown.classList.remove('show');
            }
        });
    }
});
</script>
JS;
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
