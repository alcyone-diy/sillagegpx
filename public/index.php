<?php
// Application entry point (Front Controller)

// Load configuration (including composer autoloader)
require_once __DIR__ . '/../src/config.php';

// Start session AFTER autoloader so that objects in session are unserialized correctly
session_start();

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
} elseif ($route === 'api/reveal_email') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit;
    }
    $turnstileResponse = $_POST['cf-turnstile-response'] ?? '';
    if (empty($turnstileResponse) || empty(TURNSTILE_SECRET_KEY)) {
        http_response_code(400);
        exit;
    }

    $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'secret' => TURNSTILE_SECRET_KEY,
        'response' => $turnstileResponse,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    ]));
    $verifyResponse = curl_exec($ch);
    curl_close($ch);

    $responseData = json_decode($verifyResponse);
    if ($responseData && $responseData->success) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'email' => defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'alcyone-diy@gmail.com']);
    } else {
        http_response_code(403);
        echo json_encode(['success' => false]);
    }
    exit;
} elseif (strpos($route, 'api/passkey/') === 0) {
    $controller = new \App\Controllers\PasskeyController();
    if ($route === 'api/passkey/register/challenge') {
        $controller->apiRegisterChallenge();
    } elseif ($route === 'api/passkey/register/verify') {
        $controller->apiRegisterVerify();
    } elseif ($route === 'api/passkey/login/challenge') {
        $controller->apiLoginChallenge();
    } elseif ($route === 'api/passkey/login/verify') {
        $controller->apiLoginVerify();
    } else {
        http_response_code(404);
        exit;
    }
} elseif ($route === 'admin') {
    $controller = new \App\Controllers\AdminController();
    $controller->showAdmin();
} elseif ($route === 'about') {
    require SRC_PATH . '/Views/about.php';
} else {
    http_response_code(404);
    echo "Page not found (404): " . htmlspecialchars($route);
}
