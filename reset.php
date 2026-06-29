<?php
// reset.php
// Script to completely reset the application data

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

echo "Starting complete reset of the SillageGPX website...\n\n";

// 1. Delete the SQLite Database
if (file_exists(DB_PATH)) {
    if (unlink(DB_PATH)) {
        echo "[OK] Deleted database file: " . DB_PATH . "\n";
    } else {
        echo "[ERROR] Failed to delete database file.\n";
    }
} else {
    echo "[INFO] Database file does not exist, skipping.\n";
}

// 2. Clear Data directory
function clearDirectory($dir) {
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                $path = $dir . DIRECTORY_SEPARATOR . $object;
                if (is_dir($path) && !is_link($path)) {
                    clearDirectory($path);
                    rmdir($path);
                } else {
                    unlink($path);
                }
            }
        }
    }
}

if (is_dir(DATA_PATH)) {
    clearDirectory(DATA_PATH);
    echo "[OK] Cleared data directory: " . DATA_PATH . "\n";
} else {
    echo "[INFO] Data directory does not exist, skipping.\n";
}

// 3. Recreate base directories
if (!is_dir(GPX_PATH)) {
    mkdir(GPX_PATH, 0755, true);
    echo "[OK] Recreated GPX uploads directory.\n";
}

// 4. Re-initialize Database
\App\Utils\Database::initIfNeeded();
echo "[OK] Initialized a fresh database.\n";

echo "\nReset completed successfully. The application is now clean.\n";
