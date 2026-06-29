<?php
$pageTitle = 'Home - SillageGPX';
ob_start();
?>

<div class="hero">
    <div class="hero-content glass-card">
        <h1 class="hero-title">SillageGPX</h1>
        <p class="hero-subtitle">Manage, view, and share your GPX tracks.</p>
        
        <div class="hero-actions">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="?route=dashboard" class="btn btn-primary btn-lg">Go to Dashboard</a>
            <?php else: ?>
                <a href="?route=login" class="btn btn-primary btn-lg">Get Started</a>
            <?php endif; ?>
        </div>
    </div>
</div>



<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
