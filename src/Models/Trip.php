<?php
namespace App\Models;

use App\Utils\Database;
use PDO;

class Trip {
    public int $id;
    public int $user_id;
    public string $title;
    public ?string $start_date;
    public ?string $end_date;
    public ?string $boat_name;
    public ?string $comment;
    public string $visibility; // 'public', 'unlisted', 'private'
    public ?string $unlisted_token;
    public int $views_count;
    public string $created_at;
    public string $updated_at;

    public static function findAllByUser(int $user_id): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM trips WHERE user_id = :user_id ORDER BY start_date DESC, created_at DESC');
        $stmt->execute(['user_id' => $user_id]);
        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    public static function findById(int $id): ?Trip {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM trips WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);
        $trip = $stmt->fetch();
        return $trip ?: null;
    }
    
    public static function findByToken(string $token): ?Trip {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM trips WHERE unlisted_token = :token');
        $stmt->execute(['token' => $token]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);
        $trip = $stmt->fetch();
        return $trip ?: null;
    }

    public static function create(int $user_id, string $title, ?string $start_date, ?string $end_date, ?string $boat_name, ?string $comment, string $visibility = 'private'): ?Trip {
        $pdo = Database::getConnection();
        $token = ($visibility === 'unlisted') ? bin2hex(random_bytes(16)) : null;
        
        $stmt = $pdo->prepare('INSERT INTO trips (user_id, title, start_date, end_date, boat_name, comment, visibility, unlisted_token) VALUES (:user_id, :title, :start_date, :end_date, :boat_name, :comment, :visibility, :unlisted_token)');
        $stmt->execute([
            'user_id' => $user_id,
            'title' => $title,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'boat_name' => $boat_name,
            'comment' => $comment,
            'visibility' => $visibility,
            'unlisted_token' => $token
        ]);
        
        return self::findById((int)$pdo->lastInsertId());
    }

    public function update(): void {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE trips SET title = :title, start_date = :start_date, end_date = :end_date, boat_name = :boat_name, comment = :comment, visibility = :visibility, unlisted_token = :unlisted_token, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
        $stmt->execute([
            'title' => $this->title,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'boat_name' => $this->boat_name,
            'comment' => $this->comment,
            'visibility' => $this->visibility,
            'unlisted_token' => $this->unlisted_token,
            'id' => $this->id
        ]);
    }

    public function incrementViews(): void {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE trips SET views_count = views_count + 1 WHERE id = :id');
        $stmt->execute(['id' => $this->id]);
        $this->views_count++;
    }

    public static function getAllPublicOrUnlisted() {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT * FROM trips WHERE visibility IN ('public', 'unlisted') ORDER BY start_date DESC");
        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    public static function getPreviousBoatNames(int $user_id): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT DISTINCT boat_name FROM trips WHERE user_id = :user_id AND boat_name IS NOT NULL AND boat_name != "" ORDER BY boat_name ASC');
        $stmt->execute(['user_id' => $user_id]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
