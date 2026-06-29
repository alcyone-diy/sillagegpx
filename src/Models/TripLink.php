<?php
namespace App\Models;

use App\Utils\Database;
use PDO;

class TripLink {
    public int $id;
    public int $trip_id;
    public string $url;
    public ?string $label;

    public static function create(int $trip_id, string $url, ?string $label): ?TripLink {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO trip_links (trip_id, url, label) VALUES (:trip_id, :url, :label)');
        $stmt->execute([
            'trip_id' => $trip_id,
            'url' => $url,
            'label' => $label
        ]);
        
        return self::findById((int)$pdo->lastInsertId());
    }

    public static function findById(int $id): ?TripLink {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM trip_links WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, self::class);
        $link = $stmt->fetch();
        return $link ?: null;
    }

    public static function findByTripId(int $trip_id): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM trip_links WHERE trip_id = :trip_id ORDER BY id ASC');
        $stmt->execute(['trip_id' => $trip_id]);
        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    public static function deleteByTripId(int $trip_id): void {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM trip_links WHERE trip_id = :trip_id');
        $stmt->execute(['trip_id' => $trip_id]);
    }
}
