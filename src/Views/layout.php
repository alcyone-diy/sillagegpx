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
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    
    <?php if (isset($extraCss)) echo $extraCss; ?>
</head>
<body>
    <nav class="navbar glass">
        <div class="nav-container">
            <!-- Left: Logo & Primary Nav -->
            <div class="nav-left" style="display: flex; align-items: center; gap: 2rem;">
                <a href="?route=home" class="logo">⛵ Sillage<span>GPX</span></a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="nav-main-links" style="display: flex; gap: 1.5rem;">
                        <a href="?route=dashboard" class="nav-link"><?= __('dashboard') ?></a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right: Actions & User Profile -->
            <div class="nav-right" style="display: flex; align-items: center; gap: 1rem;">
                
                <!-- Language Dropdown -->
                <div class="dropdown" style="margin-left: 0; padding-left: 0; border-left: none;">
                    <button class="nav-link btn-ghost" style="border: none; cursor: pointer; padding: 0.4rem 0.6rem; font-weight: 500; display: flex; align-items: center; gap: 0.3rem; border-radius: var(--radius-sm);">
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
                    <a href="?route=create_trip" class="btn btn-primary-sm" style="margin: 0 0.5rem;"><?= __('new_trip') ?></a>
                    
                    <!-- User Profile Dropdown -->
                    <div class="dropdown" style="margin-left: 0; padding-left: 0; border-left: none;">
                        <button class="nav-link btn-ghost" style="border: none; cursor: pointer; padding: 0.3rem; font-weight: 500; display: flex; align-items: center; gap: 0.6rem; background: rgba(0,0,0,0.04); border-radius: 30px; padding-right: 1rem;">
                            <div style="width: 28px; height: 28px; background: var(--accent-primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700;">
                                <?= strtoupper(substr($_SESSION['username'], 0, 1)) ?>
                            </div>
                            <?= htmlspecialchars($_SESSION['username']) ?>
                        </button>
                        <div class="dropdown-menu glass" style="min-width: 150px;">
                            <a href="?route=logout" class="dropdown-item" style="color: var(--error);"><?= __('logout') ?></a>
                        </div>
                    </div>
                <?php else: ?>
                    <?php if (($_GET['route'] ?? 'home') !== 'login'): ?>
                        <a href="?route=login" class="btn btn-primary-sm" style="margin-left: 0.5rem;"><?= __('login') ?></a>
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
        <p>&copy; <?= date('Y') ?> SillageGPX.</p>
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
