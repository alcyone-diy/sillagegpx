<?php
namespace App\Models;

use App\Utils\Database;
use PDO;

class User {
    public int $id;
    public string $username;
    public string $email;
    public string $password_hash;
    public string $created_at;

    public static function findByUsername(string $username): ?User {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username');
        $stmt->execute(['username' => $username]);
        $data = $stmt->fetch();
        
        if ($data) {
            $user = new User();
            foreach ($data as $key => $value) {
                $user->$key = $value;
            }
            return $user;
        }
        return null;
    }

    public static function findById(int $id): ?User {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch();
        
        if ($data) {
            $user = new User();
            foreach ($data as $key => $value) {
                $user->$key = $value;
            }
            return $user;
        }
        return null;
    }

    public static function create(string $username, string $email, string $password): ?User {
        $pdo = Database::getConnection();
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :password_hash)');
        try {
            $stmt->execute([
                'username' => $username,
                'email' => $email,
                'password_hash' => $hash
            ]);
            return self::findById((int)$pdo->lastInsertId());
        } catch (\PDOException $e) {
            // Probably unique constraint violation
            return null;
        }
    }
}
