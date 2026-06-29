<?php
namespace App\Utils;

class Translator {
    private static array $translations = [];
    private static string $lang = 'en';

    public static function init() {
        if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'fr'])) {
            $_SESSION['lang'] = $_GET['lang'];
            // Remove lang from url to avoid preserving it in future requests (optional)
        }

        self::$lang = $_SESSION['lang'] ?? 'en';
        $file = SRC_PATH . '/Lang/' . self::$lang . '.php';
        
        if (file_exists($file)) {
            self::$translations = require $file;
        } else {
            // Fallback to english
            $fallback = SRC_PATH . '/Lang/en.php';
            if (file_exists($fallback)) {
                self::$translations = require $fallback;
            }
        }
    }

    public static function get(string $key): string {
        return self::$translations[$key] ?? $key;
    }
    
    public static function getCurrentLang(): string {
        return self::$lang;
    }
}
