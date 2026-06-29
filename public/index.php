<?php
// Application entry point (Front Controller)

// Start session
session_start();

// Load configuration
require_once __DIR__ . '/../src/config.php';

// Basic autoloader for classes (App Namespace)
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative_class = substr($class, $len);
    $file = SRC_PATH . '/' . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

// Initialize database
\App\Utils\Database::initIfNeeded();

// Initialize Translator
\App\Utils\Translator::init();

// Global translation helper
function __(string $key): string {
    return \App\Utils\Translator::get($key);
}

// Simple routing based on the 'route' parameter provided by .htaccess
$route = isset($_GET['route']) ? rtrim($_GET['route'], '/') : '';
$method = $_SERVER['REQUEST_METHOD'];

// Basic router
if ($route === '' || $route === 'home') {
    require SRC_PATH . '/Views/home.php';
} elseif ($route === 'login') {
    $controller = new \App\Controllers\AuthController();
    if ($method === 'POST') {
        if (isset($_POST['register'])) {
            $controller->handleRegister();
        } else {
            $controller->handleLogin();
        }
    } else {
        $controller->showLogin();
    }
} elseif ($route === 'logout') {
    $controller = new \App\Controllers\AuthController();
    $controller->handleLogout();
} elseif ($route === 'dashboard') {
    $controller = new \App\Controllers\TripController();
    $controller->showDashboard();
} elseif ($route === 'create_trip') {
    $controller = new \App\Controllers\TripController();
    if ($method === 'POST') {
        $controller->handleCreate();
    } else {
        $controller->showCreateForm();
    }
} elseif ($route === 'edit_trip') {
    $controller = new \App\Controllers\TripController();
    if ($method === 'POST') {
        $controller->handleEdit();
    } else {
        $controller->showEditForm();
    }
} elseif ($route === 'delete_track') {
    $controller = new \App\Controllers\TripController();
    if ($method === 'POST') {
        $controller->handleDeleteTrack();
    } else {
        http_response_code(405);
        echo "Method Not Allowed";
    }
} elseif ($route === 'regenerate_token') {
    $controller = new \App\Controllers\TripController();
    if ($method === 'POST') {
        $controller->handleRegenerateToken();
    } else {
        http_response_code(405);
        echo "Method Not Allowed";
    }
} elseif ($route === 'trip') {
    $controller = new \App\Controllers\TripController();
    $controller->showTrip();
} elseif ($route === 'api/track') {
    $controller = new \App\Controllers\TripController();
    $controller->apiTrackData();
} else {
    http_response_code(404);
    echo "Page not found (404): " . htmlspecialchars($route);
}
