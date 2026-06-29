<!DOCTYPE html>
<html lang="<?= htmlspecialchars(\App\Utils\Translator::getCurrentLang()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'SillageGPX') ?></title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&family=Outfit:wght@400;700&display=swap" rel="stylesheet">
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
    
    <?php if (defined('TURNSTILE_SITE_KEY') && TURNSTILE_SITE_KEY !== ''): ?>
    <!-- Cloudflare Turnstile -->
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <?php endif; ?>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    
    <?php if (isset($extraCss)) echo $extraCss; ?>
</head>
<body>
    <nav class="navbar glass">
        <div class="nav-container">
            <!-- Left: Logo & Primary Nav -->
            <div class="nav-left d-flex align-items-center">
                <a href="?route=home" class="logo">⛵ <span class="hide-on-mobile">Sillage</span>GPX</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="nav-main-links d-flex">
                        <a href="?route=dashboard" class="nav-link">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="show-on-mobile" style="display:none; margin-right: 0.2rem;"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                            <span class="hide-on-mobile"><?= __('dashboard') ?></span>
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right: Actions & User Profile -->
            <div class="nav-right d-flex align-items-center">
                
                <!-- Language Dropdown -->
                <div class="dropdown nav-dropdown-lang">
                    <button class="nav-link btn-ghost dropdown-btn">
                        <?= strtoupper(\App\Utils\Translator::getCurrentLang()) ?>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                    </button>
                    <div class="dropdown-menu glass" style="min-width: 140px;">
                        <?php $currentParams = $_GET; ?>
                        <a href="?<?= http_build_query(array_merge($currentParams, ['lang' => 'en'])) ?>" class="dropdown-item <?= \App\Utils\Translator::getCurrentLang() === 'en' ? 'active' : '' ?>">English (EN)</a>
                        <a href="?<?= http_build_query(array_merge($currentParams, ['lang' => 'fr'])) ?>" class="dropdown-item <?= \App\Utils\Translator::getCurrentLang() === 'fr' ? 'active' : '' ?>">Français (FR)</a>
                    </div>
                </div>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- CTA -->
                    <a href="?route=create_trip" class="btn btn-primary-sm cta-new-trip">
                        <span class="hide-on-mobile"><?= __('new_trip') ?></span>
                        <span class="show-on-mobile" style="display:none;">+</span>
                    </a>
                    
                    <!-- User Profile Dropdown -->
                    <div class="dropdown nav-dropdown-user">
                        <button class="nav-link btn-ghost user-profile-btn dropdown-btn">
                            <div class="avatar-circle">
                                <?= strtoupper(substr($_SESSION['username'], 0, 1)) ?>
                            </div>
                            <span class="hide-on-mobile"><?= htmlspecialchars($_SESSION['username']) ?></span>
                        </button>
                        <div class="dropdown-menu glass" style="min-width: max-content; right: 0; white-space: nowrap;">
                            <a href="?route=profile" class="dropdown-item">👤 Mon Profil</a>
                            <?php if ((int)$_SESSION['user_id'] === 1): ?>
                                <a href="?route=admin" class="dropdown-item" style="color: var(--warning);">🛠️ <?= __('admin') ?></a>
                            <?php endif; ?>
                            <div style="height: 1px; background: rgba(255,255,255,0.1); margin: 0.5rem 0;"></div>
                            <a href="?route=logout" class="dropdown-item" style="color: var(--error);"><?= __('logout') ?></a>
                        </div>
                    </div>
                <?php else: ?>
                    <?php if (($_GET['route'] ?? 'home') !== 'login'): ?>
                        <a href="?route=login" class="btn btn-primary-sm cta-login"><?= __('login') ?></a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="toast-container">
        <?php if (isset($error)): ?>
            <div class="alert alert-error glass-error toast">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success glass-success toast">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>
    </div>
    
    <main class="main-content">

        <?= $content ?>
    </main>

    <footer class="footer">
        <p>
            &copy; <?= date('Y') ?> SillageGPX. 
            <a href="?route=about" style="color: inherit; text-decoration: underline; margin-left: 0.5rem;"><?= __('about') ?></a>
        </p>
    </footer>

    <!-- Scripts -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="js/app.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toasts = document.querySelectorAll('.toast');
            toasts.forEach(toast => {
                setTimeout(() => {
                    toast.classList.add('fade-out');
                    setTimeout(() => toast.remove(), 500);
                }, 4000);
            });
        });
    </script>
    <?php if (isset($extraJs)) echo $extraJs; ?>
</body>
</html>
