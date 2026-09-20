<?php
namespace App\Utils;

use PDO;

class TrackRecalculator {

    /**
     * Recalculates all GPX tracks stored in the database.
     * Re-parses original GPX files, updates SQLite stats and regenerates frontend JSON caches.
     *
     * @param callable|null $progressCallback function(array $info): void
     * @return array Summary of the operation
     */
    public static function recalculateAll(?callable $progressCallback = null): array {
        $pdo = Database::getConnection();

        $sql = "
            SELECT 
                gt.id, 
                gt.trip_step_id, 
                gt.file_path, 
                ts.trip_id, 
                t.user_id, 
                ts.title as step_title, 
                t.title as trip_title
            FROM gpx_tracks gt
            JOIN trip_steps ts ON gt.trip_step_id = ts.id
            JOIN trips t ON ts.trip_id = t.id
            ORDER BY gt.id ASC
        ";

        $stmt = $pdo->query($sql);
        $tracks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total = count($tracks);
        $success = 0;
        $failed = 0;
        $errors = [];

        $updateStmt = $pdo->prepare("
            UPDATE gpx_tracks SET
                file_hash = :file_hash,
                track_uuid = :track_uuid,
                start_time = :start_time,
                end_time = :end_time,
                distance_meters = :distance_meters,
                duration_seconds = :duration_seconds,
                avg_speed_knots = :avg_speed_knots,
                max_speed_knots = :max_speed_knots
            WHERE id = :id
        ");

        foreach ($tracks as $index => $track) {
            $trackId = (int)$track['id'];
            $fullGpxPath = GPX_PATH . '/' . $track['file_path'];

            if (!file_exists($fullGpxPath)) {
                $failed++;
                $msg = "Fichier GPX introuvable : {$track['file_path']}";
                $errors[] = ['track_id' => $trackId, 'error' => $msg];
                if ($progressCallback) {
                    $progressCallback([
                        'index' => $index + 1,
                        'total' => $total,
                        'status' => 'error',
                        'message' => $msg,
                        'track' => $track
                    ]);
                }
                continue;
            }

            $stats = GpxParser::parse($fullGpxPath);
            if (!$stats) {
                $failed++;
                $msg = "Échec de l'analyse du fichier GPX pour la trace #{$trackId}";
                $errors[] = ['track_id' => $trackId, 'error' => $msg];
                if ($progressCallback) {
                    $progressCallback([
                        'index' => $index + 1,
                        'total' => $total,
                        'status' => 'error',
                        'message' => $msg,
                        'track' => $track
                    ]);
                }
                continue;
            }

            try {
                $updateStmt->execute([
                    'file_hash' => $stats['file_hash'] ?? null,
                    'track_uuid' => $stats['track_uuid'] ?? null,
                    'start_time' => $stats['start_time'] ?? null,
                    'end_time' => $stats['end_time'] ?? null,
                    'distance_meters' => $stats['distance_meters'] ?? null,
                    'duration_seconds' => $stats['duration_seconds'] ?? null,
                    'avg_speed_knots' => $stats['avg_speed_knots'] ?? null,
                    'max_speed_knots' => $stats['max_speed_knots'] ?? null,
                    'id' => $trackId
                ]);

                // Regenerate JSON cache file for frontend map/speed charts
                $tripDir = GPX_PATH . '/' . $track['user_id'] . '/' . $track['trip_id'];
                if (!is_dir($tripDir)) {
                    @mkdir($tripDir, 0777, true);
                }

                $jsonFile = $tripDir . '/track_' . $track['trip_step_id'] . '.json';
                file_put_contents($jsonFile, json_encode([
                    'map_points' => $stats['map_points'],
                    'speed_points' => $stats['speed_points']
                ]));

                $success++;

                if ($progressCallback) {
                    $progressCallback([
                        'index' => $index + 1,
                        'total' => $total,
                        'status' => 'ok',
                        'message' => "Trace #{$trackId} recalculée ({$track['step_title']}) - Distance: {$stats['distance_meters']}m, Vmax: {$stats['max_speed_knots']}kts",
                        'track' => $track,
                        'stats' => $stats
                    ]);
                }
            } catch (\Exception $e) {
                $failed++;
                $msg = "Erreur SQL pour la trace #{$trackId} : " . $e->getMessage();
                $errors[] = ['track_id' => $trackId, 'error' => $msg];
                if ($progressCallback) {
                    $progressCallback([
                        'index' => $index + 1,
                        'total' => $total,
                        'status' => 'error',
                        'message' => $msg,
                        'track' => $track
                    ]);
                }
            }
        }

        return [
            'total' => $total,
            'success' => $success,
            'failed' => $failed,
            'errors' => $errors,
            'interval_seconds' => STATS_CALC_INTERVAL
        ];
    }
}
