<?php
namespace App\Models;

use App\Utils\Database;
use PDO;

class GpxTrack {
    public int $id;
    public int $trip_step_id;
    public string $file_path;
    public ?string $start_time;
    public ?string $end_time;
    public ?float $distance_meters;
    public ?int $duration_seconds;
    public ?float $avg_speed_knots;
    public ?float $max_speed_knots;
    public string $created_at;

    public static function findByTripStepId(int $trip_step_id): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM gpx_tracks WHERE trip_step_id = :trip_step_id ORDER BY start_time ASC');
        $stmt->execute(['trip_step_id' => $trip_step_id]);
        return $stmt->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    public static function create(int $trip_step_id, string $file_path, array $stats): void {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO gpx_tracks (trip_step_id, file_path, start_time, end_time, distance_meters, duration_seconds, avg_speed_knots, max_speed_knots) VALUES (:trip_step_id, :file_path, :start_time, :end_time, :distance_meters, :duration_seconds, :avg_speed_knots, :max_speed_knots)');
        $stmt->execute([
            'trip_step_id' => $trip_step_id,
            'file_path' => $file_path,
            'start_time' => $stats['start_time'] ?? null,
            'end_time' => $stats['end_time'] ?? null,
            'distance_meters' => $stats['distance_meters'] ?? null,
            'duration_seconds' => $stats['duration_seconds'] ?? null,
            'avg_speed_knots' => $stats['avg_speed_knots'] ?? null,
            'max_speed_knots' => $stats['max_speed_knots'] ?? null
        ]);
    }
}
