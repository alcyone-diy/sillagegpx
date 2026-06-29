<?php
namespace App\Utils;

class Translator {
    private static array $translations = [];
    private static string $lang = 'en';

    public static function init() {
        // 1. User explicitly chooses a language via URL
        if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'fr'])) {
            $lang = $_GET['lang'];
            // Save choice in cookie (1 year)
            setcookie('lang', $lang, time() + 31536000, '/');
            $_SESSION['lang'] = $lang;
        } 
        // 2. User has a saved preference in cookie
        elseif (isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], ['en', 'fr'])) {
            $lang = $_COOKIE['lang'];
            $_SESSION['lang'] = $lang;
        }
        // 3. Auto-detect from browser settings
        elseif (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
            $lang = in_array($lang, ['en', 'fr']) ? $lang : 'en';
            $_SESSION['lang'] = $lang;
        } 
        // 4. Default fallback
        else {
            $lang = 'en';
            $_SESSION['lang'] = $lang;
        }

        self::$lang = $lang;
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
