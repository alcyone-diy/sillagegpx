<?php
// migrate.php
// Script to run database migrations

if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.\n");
}

require_once __DIR__ . '/src/config.php';

// Basic autoloader
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

echo "Checking database migrations...\n\n";

// If database doesn't exist yet, initialize it
if (!file_exists(DB_PATH)) {
    echo "Database does not exist. Initializing fresh database from schema...\n";
    \App\Utils\Database::initIfNeeded();
    echo "[OK] Initialized a fresh database.\n";
    exit(0);
}

$applied = \App\Utils\Database::runMigrations(function ($message) {
    echo $message . "\n";
});

if (empty($applied)) {
    echo "No pending migrations. Database is already up to date.\n";
} else {
    echo "\n[OK] Applied " . count($applied) . " migration(s) successfully.\n";
}
