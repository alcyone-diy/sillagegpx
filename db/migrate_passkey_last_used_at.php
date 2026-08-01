<?php
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/Utils/Database.php';

use App\Utils\Database;

try {
    $db = Database::getConnection();
    
    // Check if column already exists
    $stmt = $db->query("PRAGMA table_info(user_passkeys)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $hasColumn = false;
    foreach ($columns as $col) {
        if ($col['name'] === 'last_used_at') {
            $hasColumn = true;
            break;
        }
    }
    
    if (!$hasColumn) {
        $db->exec("ALTER TABLE user_passkeys ADD COLUMN last_used_at DATETIME DEFAULT NULL");
        echo "[OK] Added 'last_used_at' column to user_passkeys table.\n";
    } else {
        echo "[INFO] 'last_used_at' column already exists in user_passkeys table.\n";
    }
    
} catch (Exception $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
}
