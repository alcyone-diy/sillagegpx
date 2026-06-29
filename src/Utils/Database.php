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
     * Initializes the database if it doesn't exist
     */
    public static function initIfNeeded(): void {
        $isNewDb = !file_exists(DB_PATH);
        $pdo = self::getConnection();
        
        if ($isNewDb && file_exists(SCHEMA_PATH)) {
            $schema = file_get_contents(SCHEMA_PATH);
            $pdo->exec($schema);
        }
    }
}
