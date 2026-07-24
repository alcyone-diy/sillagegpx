<?php
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/Utils/Database.php';

use App\Utils\Database;

try {
    $pdo = Database::getConnection();
    echo "Connected to database.\n";

    $pdo->beginTransaction();

    // 1. Create a new table with the strict constraints (NO DEFAULT, NOT NULL, NOT EMPTY)
    $pdo->exec("
        CREATE TABLE user_passkeys_new (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            credential_id TEXT NOT NULL UNIQUE,
            public_key TEXT NOT NULL,
            user_handle TEXT NOT NULL,
            sign_count INTEGER DEFAULT 0,
            name VARCHAR(255) NOT NULL CHECK(name <> ''),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");

    // 2. Copy existing data, providing 'Default Device' ONLY for the migration transition
    $pdo->exec("
        INSERT INTO user_passkeys_new (id, user_id, credential_id, public_key, user_handle, sign_count, created_at, name)
        SELECT id, user_id, credential_id, public_key, user_handle, sign_count, created_at, 'Default Device' 
        FROM user_passkeys
    ");

    // 3. Swap the tables
    $pdo->exec("DROP TABLE user_passkeys");
    $pdo->exec("ALTER TABLE user_passkeys_new RENAME TO user_passkeys");

    $pdo->commit();
    echo "Migration successful: 'name' column added to user_passkeys.\n";

} catch (PDOException $e) {
    // If the column already exists, SQLite throws an error
    if (strpos($e->getMessage(), 'duplicate column name') !== false) {
        echo "Migration skipped: 'name' column already exists.\n";
    } else {
        echo "Migration failed: " . $e->getMessage() . "\n";
    }
}
