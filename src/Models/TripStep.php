<?php
namespace App\Models;

use App\Utils\Database;
use PDO;

class TripStep {
    public int $id;
    public int $trip_id;
    public string $title;
    public int $order_index;
    public string $created_at;

    public static function findByTripId(int $trip_id): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM trip_steps WHERE trip_id = :trip_id ORDER BY order_index ASC, id ASC');
        $stmt->execute(['trip_id' => $trip_id]);
        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    public static function findById(int $id): ?TripStep {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM trip_steps WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);
        $step = $stmt->fetch();
        return $step ?: null;
    }

    public static function create(int $trip_id, string $title, int $order_index = 0): ?TripStep {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO trip_steps (trip_id, title, order_index) VALUES (:trip_id, :title, :order_index)');
        $stmt->execute([
            'trip_id' => $trip_id,
            'title' => $title,
            'order_index' => $order_index
        ]);
        return self::findById((int)$pdo->lastInsertId());
    }
}
