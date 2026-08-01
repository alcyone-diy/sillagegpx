<?php
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/Utils/Database.php';

use App\Utils\Database;

try {
    $db = Database::getConnection();
    
    // Check if column already exists
    $stmt = $db->query("PRAGMA table_info(api_tokens)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $hasColumn = false;
    foreach ($columns as $col) {
        if ($col['name'] === 'last_used_at') {
            $hasColumn = true;
            break;
        }
    }
    
    if (!$hasColumn) {
        $db->exec("ALTER TABLE api_tokens ADD COLUMN last_used_at DATETIME DEFAULT NULL");
        echo "[OK] Added 'last_used_at' column to api_tokens table.\n";
    } else {
        echo "[INFO] 'last_used_at' column already exists in api_tokens table.\n";
    }
    
} catch (Exception $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
}
