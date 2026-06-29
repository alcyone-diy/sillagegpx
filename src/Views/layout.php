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
                <div class="dropdown">
                    <button class="nav-link btn-ghost" style="border: none; cursor: pointer; padding: 0.4rem; font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
                        🌐 <?= strtoupper(\App\Utils\Translator::getCurrentLang()) ?> ▼
                    </button>
                    <div class="dropdown-menu glass">
                        <?php $currentParams = $_GET; ?>
                        <a href="?<?= http_build_query(array_merge($currentParams, ['lang' => 'en'])) ?>" class="dropdown-item <?= \App\Utils\Translator::getCurrentLang() === 'en' ? 'active' : '' ?>">English (EN)</a>
                        <a href="?<?= http_build_query(array_merge($currentParams, ['lang' => 'fr'])) ?>" class="dropdown-item <?= \App\Utils\Translator::getCurrentLang() === 'fr' ? 'active' : '' ?>">Français (FR)</a>
                    </div>
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
