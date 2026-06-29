<?php 
$pageTitle = __('about') . ' - SillageGPX';
ob_start(); 
?>

<div class="form-container" style="margin-top: 2rem;">
    <div class="dashboard-header">
        <h2><?= __('about') ?></h2>
    </div>

    <div class="glass-card mb-4 text-center" style="padding: 3rem 2rem;">
        <h3 class="mb-2" style="color: var(--accent-primary);">SillageGPX</h3>
        <p class="text-muted mb-4" style="max-width: 600px; margin-left: auto; margin-right: auto;">
            <?= __('about_desc') ?? 'Your digital logbook, designed with simplicity and elegance to let you relive your sailing trips.' ?>
        </p>

        <div style="margin-top: 2rem; display: flex; flex-direction: column; align-items: center;">
            <p style="font-weight: 600; margin-bottom: 0.5rem;"><?= __('contact_us') ?></p>
            
            <?php if (defined('TURNSTILE_SITE_KEY') && TURNSTILE_SITE_KEY !== ''): ?>
                <div id="turnstile-container" class="cf-turnstile" data-sitekey="<?= htmlspecialchars(TURNSTILE_SITE_KEY) ?>" data-callback="onTurnstileSuccess" data-theme="light"></div>
                <a href="#" id="contact-link" class="btn btn-primary" style="display: none; align-items: center; gap: 0.5rem;"></a>
                
                <script>
                    function onTurnstileSuccess(token) {
                        var formData = new FormData();
                        formData.append('cf-turnstile-response', token);
                        
                        fetch('?route=api/reveal_email', {
                            method: 'POST',
                            body: formData
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                var link = document.getElementById("contact-link");
                                link.href = "mailto:" + data.email;
                                link.innerHTML = "✉️ " + data.email;
                                link.style.display = "inline-flex";
                                document.getElementById("turnstile-container").style.display = "none";
                            }
                        });
                    }
                </script>
            <?php else: ?>
                <!-- Fallback if Turnstile is disabled -->
                <a href="mailto:<?= htmlspecialchars(defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'alcyone-diy@gmail.com') ?>" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 0.5rem;">
                    ✉️ <?= htmlspecialchars(defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'alcyone-diy@gmail.com') ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean();
require __DIR__ . '/layout.php'; 
?>
