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
    public static function findByEmail(string $email): ?User {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
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
