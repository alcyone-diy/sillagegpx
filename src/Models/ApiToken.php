<?php
namespace App\Models;

use App\Utils\Database;
use PDO;

class ApiToken {
    public $id;
    public $user_id;
    public $token;
    public $device_name;
    public $last_used_at;
    public $created_at;

    public static function create($userId, $deviceName) {
        $pdo = Database::getConnection();
        // Generate a simple 64-character token
        $token = bin2hex(random_bytes(32));
        
        $stmt = $pdo->prepare("INSERT INTO api_tokens (user_id, token, device_name) VALUES (?, ?, ?)");
        if ($stmt->execute([$userId, $token, $deviceName])) {
            return $token;
        }
        return false;
    }

    public static function findByUserId($userId) {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM api_tokens WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    public static function delete($tokenId, $userId) {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM api_tokens WHERE id = ? AND user_id = ?");
        return $stmt->execute([$tokenId, $userId]);
    }

    public static function nameExists($userId, $deviceName) {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM api_tokens WHERE user_id = ? AND device_name = ?");
        $stmt->execute([$userId, $deviceName]);
        return $stmt->fetchColumn() > 0;
    }
}
