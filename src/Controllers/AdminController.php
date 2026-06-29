<?php
namespace App\Controllers;

use App\Utils\Database;

class AdminController {
    
    public function showAdmin() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ?route=login');
            exit;
        }

        // Only user #1 is admin
        if ((int)$_SESSION['user_id'] !== 1) {
            http_response_code(403);
            echo "Forbidden: Admin access only.";
            exit;
        }

        $pdo = Database::getConnection();
        
        // Count users
        $stmtUsers = $pdo->query('SELECT COUNT(*) as count FROM users');
        $userCount = $stmtUsers->fetch()['count'];

        // Count trips
        $stmtTrips = $pdo->query('SELECT COUNT(*) as count FROM trips');
        $tripCount = $stmtTrips->fetch()['count'];

        // Count tracks
        $stmtTracks = $pdo->query('SELECT COUNT(*) as count FROM gpx_tracks');
        $trackCount = $stmtTracks->fetch()['count'];

        // Get list of users with stats
        $sql = "
            SELECT 
                u.id, 
                u.username, 
                u.email, 
                u.created_at,
                (SELECT COUNT(*) FROM trips t WHERE t.user_id = u.id) as trips_count,
                (SELECT COUNT(gt.id) 
                 FROM trips t 
                 JOIN trip_steps ts ON t.id = ts.trip_id 
                 JOIN gpx_tracks gt ON ts.id = gt.trip_step_id 
                 WHERE t.user_id = u.id) as tracks_count
            FROM users u
            ORDER BY u.created_at DESC
        ";
        $stmtUsersList = $pdo->query($sql);
        $usersList = $stmtUsersList->fetchAll(\PDO::FETCH_ASSOC);

        require SRC_PATH . '/Views/admin.php';
    }
}
