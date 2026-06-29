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
            <a href="?route=home" class="logo">⛵ Sillage<span>GPX</span></a>
            <div class="nav-links">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="?route=dashboard" class="nav-link"><?= __('dashboard') ?></a>
                    <a href="?route=create_trip" class="nav-link btn-primary-sm"><?= __('new_trip') ?></a>
                    <a href="?route=logout" class="nav-link text-muted"><?= __('logout') ?> (<?= htmlspecialchars($_SESSION['username']) ?>)</a>
                <?php else: ?>
                    <?php if (($_GET['route'] ?? 'home') !== 'login'): ?>
                        <a href="?route=login" class="nav-link btn-primary-sm"><?= __('login') ?></a>
                    <?php endif; ?>
                <?php endif; ?>
                <div class="lang-switcher" style="margin-left: 1rem; padding-left: 1rem; border-left: 1px solid var(--border-glass);">
                    <?php $currentParams = $_GET; ?>
                    <a href="?<?= http_build_query(array_merge($currentParams, ['lang' => 'en'])) ?>" class="nav-link <?= \App\Utils\Translator::getCurrentLang() === 'en' ? 'active text-primary' : 'text-muted' ?>" style="padding: 0 4px;">EN</a>
                    <span class="text-muted">|</span>
                    <a href="?<?= http_build_query(array_merge($currentParams, ['lang' => 'fr'])) ?>" class="nav-link <?= \App\Utils\Translator::getCurrentLang() === 'fr' ? 'active text-primary' : 'text-muted' ?>" style="padding: 0 4px;">FR</a>
                </div>
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
