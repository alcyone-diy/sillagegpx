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
    public bool $is_skipper = true;
    public string $created_at;
    public string $updated_at;
    public float $total_distance_meters = 0.0;

    public function isSkipper(): bool {
        return (bool)$this->is_skipper;
    }

    public function getDurationDays(): int {
        if (!empty($this->start_date) && !empty($this->end_date)) {
            try {
                $start = new \DateTime($this->start_date);
                $end = new \DateTime($this->end_date);
                return max(1, $start->diff($end)->days + 1);
            } catch (\Throwable) {
                return 1;
            }
        }
        return 1;
    }

    public function getTotalDistanceNm(): float {
        return round($this->total_distance_meters / 1852.0, 1);
    }

    public static function findAllByUser(int $user_id): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('
            SELECT 
                t.*,
                COALESCE(SUM(g.distance_meters), 0) AS total_distance_meters
            FROM trips t
            LEFT JOIN trip_steps s ON s.trip_id = t.id
            LEFT JOIN gpx_tracks g ON g.trip_step_id = s.id
            WHERE t.user_id = :user_id
            GROUP BY t.id
            ORDER BY t.start_date DESC, t.created_at DESC
        ');
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

    public static function create(int $user_id, string $title, ?string $start_date, ?string $end_date, ?string $boat_name, ?string $comment, string $visibility = 'private', bool $is_skipper = true): ?Trip {
        $pdo = Database::getConnection();
        $token = ($visibility === 'unlisted') ? bin2hex(random_bytes(16)) : null;
        
        $stmt = $pdo->prepare('INSERT INTO trips (user_id, title, start_date, end_date, boat_name, comment, visibility, unlisted_token, is_skipper) VALUES (:user_id, :title, :start_date, :end_date, :boat_name, :comment, :visibility, :unlisted_token, :is_skipper)');
        $stmt->execute([
            'user_id' => $user_id,
            'title' => $title,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'boat_name' => $boat_name,
            'comment' => $comment,
            'visibility' => $visibility,
            'unlisted_token' => $token,
            'is_skipper' => (int) $is_skipper
        ]);
        
        return self::findById((int)$pdo->lastInsertId());
    }

    public function update(): void {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE trips SET title = :title, start_date = :start_date, end_date = :end_date, boat_name = :boat_name, comment = :comment, visibility = :visibility, unlisted_token = :unlisted_token, is_skipper = :is_skipper, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
        $stmt->execute([
            'title' => $this->title,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'boat_name' => $this->boat_name,
            'comment' => $this->comment,
            'visibility' => $this->visibility,
            'unlisted_token' => $this->unlisted_token,
            'is_skipper' => (int) $this->is_skipper,
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
        $stmt = $pdo->query("
            SELECT 
                t.*,
                COALESCE(SUM(g.distance_meters), 0) AS total_distance_meters
            FROM trips t
            LEFT JOIN trip_steps s ON s.trip_id = t.id
            LEFT JOIN gpx_tracks g ON g.trip_step_id = s.id
            WHERE t.visibility IN ('public', 'unlisted')
            GROUP BY t.id
            ORDER BY t.start_date DESC, t.created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    public static function getPreviousBoatNames(int $user_id): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT DISTINCT boat_name FROM trips WHERE user_id = :user_id AND boat_name IS NOT NULL AND boat_name != "" ORDER BY boat_name ASC');
        $stmt->execute(['user_id' => $user_id]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get aggregated sailing statistics for a user
     *
     * @param int $userId
     * @return array{
     *     total_nm: float,
     *     skipper_nm: float,
     *     crew_nm: float,
     *     total_trips: int,
     *     skipper_trips: int,
     *     crew_trips: int,
     *     total_days: int,
     *     skipper_days: int,
     *     crew_days: int
     * }
     */
    public static function getUserStats(int $userId): array {
        $pdo = Database::getConnection();

        // 1. Distance aggregates from tracks
        $stmtDist = $pdo->prepare('
            SELECT 
                COALESCE(SUM(g.distance_meters), 0) AS total_meters,
                COALESCE(SUM(CASE WHEN t.is_skipper = 1 THEN g.distance_meters ELSE 0 END), 0) AS skipper_meters,
                COALESCE(SUM(CASE WHEN t.is_skipper = 0 THEN g.distance_meters ELSE 0 END), 0) AS crew_meters
            FROM trips t
            JOIN trip_steps s ON s.trip_id = t.id
            JOIN gpx_tracks g ON g.trip_step_id = s.id
            WHERE t.user_id = :user_id
        ');
        $stmtDist->execute(['user_id' => $userId]);
        $distRow = $stmtDist->fetch();

        $nmConversion = 1852.0;

        // 2. Trips and navigation days counts
        $stmtTrips = $pdo->prepare('
            SELECT 
                is_skipper,
                CAST(MAX(1, COALESCE(julianday(end_date) - julianday(start_date) + 1, 1)) AS INTEGER) AS duration_days
            FROM trips 
            WHERE user_id = :user_id
        ');
        $stmtTrips->execute(['user_id' => $userId]);
        $trips = $stmtTrips->fetchAll();

        $totalTrips = 0;
        $skipperTrips = 0;
        $crewTrips = 0;
        $totalDays = 0;
        $skipperDays = 0;
        $crewDays = 0;

        foreach ($trips as $t) {
            $days = (int)($t['duration_days'] ?? 1);
            $totalTrips++;
            $totalDays += $days;

            if (!empty($t['is_skipper'])) {
                $skipperTrips++;
                $skipperDays += $days;
            } else {
                $crewTrips++;
                $crewDays += $days;
            }
        }

        return [
            'total_nm' => round(($distRow['total_meters'] ?? 0) / $nmConversion, 1),
            'skipper_nm' => round(($distRow['skipper_meters'] ?? 0) / $nmConversion, 1),
            'crew_nm' => round(($distRow['crew_meters'] ?? 0) / $nmConversion, 1),
            'total_trips' => $totalTrips,
            'skipper_trips' => $skipperTrips,
            'crew_trips' => $crewTrips,
            'total_days' => $totalDays,
            'skipper_days' => $skipperDays,
            'crew_days' => $crewDays,
        ];
    }
}
