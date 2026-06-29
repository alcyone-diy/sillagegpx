<?php
$pageTitle = __('site_title');
ob_start();
?>

<div class="hero">
    <div class="hero-content glass-card">
        <h1 class="hero-title"><?= __('site_title') ?></h1>
        <p class="hero-subtitle"><?= __('home_subtitle') ?></p>
        
        <div class="hero-actions">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="?route=dashboard" class="btn btn-primary btn-lg"><?= __('go_to_dashboard') ?></a>
            <?php else: ?>
                <a href="?route=login" class="btn btn-primary btn-lg"><?= __('get_started') ?></a>
            <?php endif; ?>
        </div>
    </div>
</div>



<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
