<?php
namespace App\Utils;

use PDO;
use PDOException;

class Database {
    private static ?PDO $instance = null;

    /**
     * Returns the PDO instance (Singleton)
     */
    public static function getConnection(): PDO {
        if (self::$instance === null) {
            try {
                self::$instance = new PDO('sqlite:' . DB_PATH);
                self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                
                // Enable foreign key constraints in SQLite
                self::$instance->exec('PRAGMA foreign_keys = ON;');
            } catch (PDOException $e) {
                die("Database connection error: " . $e->getMessage());
            }
        }
        return self::$instance;
    }

    /**
     * Initializes the database if it doesn't exist and runs pending migrations
     */
    public static function initIfNeeded(): void {
        $isNewDb = !file_exists(DB_PATH);
        $pdo = self::getConnection();
        
        if ($isNewDb && file_exists(SCHEMA_PATH)) {
            $schema = file_get_contents(SCHEMA_PATH);
            $pdo->exec($schema);
            self::markAllMigrationsAsApplied();
        } else {
            self::runMigrations();
        }
    }

    /**
     * Marks all existing migration files as applied (used when initial schema already includes them)
     */
    public static function markAllMigrationsAsApplied(): void {
        $pdo = self::getConnection();
        $pdo->exec('CREATE TABLE IF NOT EXISTS migrations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            migration VARCHAR(255) NOT NULL UNIQUE,
            applied_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )');

        $migrationsDir = defined('MIGRATIONS_PATH') ? MIGRATIONS_PATH : dirname(__DIR__, 2) . '/db/migrations';
        if (!is_dir($migrationsDir)) {
            return;
        }

        $files = glob($migrationsDir . '/*.sql');
        if (empty($files)) {
            return;
        }

        $stmt = $pdo->prepare('INSERT OR IGNORE INTO migrations (migration) VALUES (:migration)');
        foreach ($files as $file) {
            $stmt->execute(['migration' => basename($file)]);
        }
    }

    /**
     * Runs any pending migrations
     * 
     * @param callable|null $logger Optional callback for logging messages
     * @return array List of newly applied migration file names
     */
    public static function runMigrations(?callable $logger = null): array {
        $pdo = self::getConnection();

        // Ensure migrations table exists
        $pdo->exec('CREATE TABLE IF NOT EXISTS migrations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            migration VARCHAR(255) NOT NULL UNIQUE,
            applied_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )');

        $applied = [];
        $stmt = $pdo->query('SELECT migration FROM migrations');
        $executedMigrations = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $migrationsDir = defined('MIGRATIONS_PATH') ? MIGRATIONS_PATH : dirname(__DIR__, 2) . '/db/migrations';
        if (!is_dir($migrationsDir)) {
            return $applied;
        }

        $files = glob($migrationsDir . '/*.sql');
        sort($files);

        foreach ($files as $file) {
            $filename = basename($file);
            if (in_array($filename, $executedMigrations, true)) {
                continue;
            }

            if ($logger) {
                $logger("Applying migration: {$filename}...");
            }

            $sql = file_get_contents($file);
            if (!empty(trim($sql))) {
                $statements = array_filter(array_map('trim', explode(';', $sql)));
                foreach ($statements as $stmt) {
                    if (empty($stmt)) {
                        continue;
                    }
                    try {
                        $pdo->exec($stmt);
                    } catch (PDOException $e) {
                        // Gracefully tolerate if a column or index already exists (e.g. manually added previously)
                        $msg = strtolower($e->getMessage());
                        if (str_contains($msg, 'duplicate column name') || str_contains($msg, 'already exists')) {
                            if ($logger) {
                                $logger("  (Note: Object already exists, skipping statement: {$stmt})");
                            }
                        } else {
                            throw $e;
                        }
                    }
                }
            }

            $insertStmt = $pdo->prepare('INSERT OR IGNORE INTO migrations (migration) VALUES (:migration)');
            $insertStmt->execute(['migration' => $filename]);
            $applied[] = $filename;

            if ($logger) {
                $logger("Applied: {$filename}");
            }
        }

        return $applied;
    }
}
