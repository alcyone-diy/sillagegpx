<?php
namespace App\Models;

use App\Utils\Database;
use PDO;

class User {
    public int $id;
    public string $username;
    public string $email;
    public string $password_hash;
    public ?string $last_login_at = null;
    public string $created_at;

    public static function findByUsername(string $username): ?User {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username');
        $stmt->execute(['username' => $username]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);
        $user = $stmt->fetch();
        return $user ?: null;
    }
    public static function findByEmail(string $email): ?User {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function findById(int $id): ?User {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function create(string $username, string $email, string $password): ?User {
        $pdo = Database::getConnection();
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash, last_login_at) VALUES (:username, :email, :password_hash, CURRENT_TIMESTAMP)');
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

    public static function updateLastLogin(int $userId): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE users SET last_login_at = CURRENT_TIMESTAMP WHERE id = :id');
        try {
            return $stmt->execute(['id' => $userId]);
        } catch (\PDOException $e) {
            return false;
        }
    }

    public function update(string $username, string $passwordHash): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE users SET username = :username, password_hash = :password_hash WHERE id = :id');
        try {
            return $stmt->execute([
                'username' => $username,
                'password_hash' => $passwordHash,
                'id' => $this->id
            ]);
        } catch (\PDOException $e) {
            return false;
        }
    }
}
