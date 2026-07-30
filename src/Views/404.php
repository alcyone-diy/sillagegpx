<?php 
$pageTitle = __('error_404') . ' - SillageGPX';
ob_start(); 
?>

<div class="form-container" style="margin-top: 2rem;">
    <div class="dashboard-header">
        <h2><?= __('error_404') ?></h2>
    </div>

    <div class="glass-card mb-4 text-center" style="padding: 3rem 2rem;">
        <h3 class="mb-2" style="color: var(--accent-primary); font-size: 4rem; margin-bottom: 1rem;">404</h3>
        <h4 class="mb-2" style="font-weight: 600;"><?= __('page_not_found') ?></h4>
        <p class="text-muted mb-4" style="max-width: 600px; margin-left: auto; margin-right: auto;">
            <?= __('page_not_found_desc') ?>
        </p>

        <div style="margin-top: 2rem;">
            <a href="?route=home" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 0.5rem;">
                <?= __('back_to_home') ?>
            </a>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean();
require __DIR__ . '/layout.php'; 
?>
