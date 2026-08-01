<?php
namespace App\Controllers;

use App\Middleware\ApiAuth;
use App\Models\Trip;
use App\Models\TripStep;
use App\Models\GpxTrack;
use App\Utils\GpxParser;
use App\Utils\Database;

class ApiController {
    
    /**
     * Helper to return JSON responses
     */
    private function jsonResponse(array $data, int $statusCode = 200) {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }

    /**
     * GET /api/trips
     * Returns a list of trips for the authenticated user.
     */
    public function listTrips() {
        $userId = ApiAuth::authenticate();
        $trips = Trip::findAllByUser($userId);
        $this->jsonResponse(['success' => true, 'trips' => $trips]);
    }

    /**
     * POST /api/trips
     * Creates a new trip for the authenticated user.
     */
    public function createTrip() {
        $userId = ApiAuth::authenticate();
        
        // Support JSON payload or standard POST
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        
        $title = trim($input['title'] ?? '');
        $boatName = trim($input['boat_name'] ?? '');
        $comment = trim($input['comment'] ?? '');
        $visibility = trim($input['visibility'] ?? 'private');
        
        if (empty($title)) {
            $this->jsonResponse(['success' => false, 'error' => 'Title is required'], 400);
        }
        
        $trip = Trip::create($userId, $title, null, null, $boatName, $comment, $visibility);
        
        if ($trip) {
            $this->jsonResponse(['success' => true, 'trip' => $trip]);
        } else {
            $this->jsonResponse(['success' => false, 'error' => 'Could not create trip'], 500);
        }
    }

    /**
     * POST /api/tracks/upload
     * Uploads a GPX file and attaches it to a trip.
     */
    public function uploadTrack() {
        $userId = ApiAuth::authenticate();
        
        if (!isset($_FILES['gpx_file']) || $_FILES['gpx_file']['error'] !== UPLOAD_ERR_OK) {
            $this->jsonResponse(['success' => false, 'error' => 'No file uploaded or upload error'], 400);
        }
        
        $tripId = (int)($_POST['trip_id'] ?? 0);
        if (!$tripId) {
            $this->jsonResponse(['success' => false, 'error' => 'trip_id is required'], 400);
        }
        
        $trip = Trip::findById($tripId);
        if (!$trip || $trip->user_id !== $userId) {
            $this->jsonResponse(['success' => false, 'error' => 'Trip not found or unauthorized'], 404);
        }
        
        $tmpName = $_FILES['gpx_file']['tmp_name'];
        $name = basename($_FILES['gpx_file']['name']);
        
        $tripGpxPath = GPX_PATH . '/' . $userId . '/' . $tripId;
        if (!is_dir($tripGpxPath)) {
            mkdir($tripGpxPath, 0755, true);
        }
        
        $safeName = $userId . '/' . $tripId . '/' . uniqid() . '_' . preg_replace('/[^a-zA-Z0-9.-]/', '_', $name);
        $destination = GPX_PATH . '/' . $safeName;
        
        if (move_uploaded_file($tmpName, $destination)) {
            $stats = GpxParser::parse($destination);
            if ($stats) {
                // Determine order_index
                $pdo = Database::getConnection();
                $stmt = $pdo->prepare('SELECT MAX(order_index) FROM trip_steps WHERE trip_id = ?');
                $stmt->execute([$tripId]);
                $maxOrder = (int)$stmt->fetchColumn();
                $order = $maxOrder + 1;
                
                $stepTitle = pathinfo($name, PATHINFO_FILENAME);
                $step = TripStep::create($tripId, $stepTitle, $order);
                
                $trackCreated = GpxTrack::create($step->id, $safeName, $stats);
                
                if ($trackCreated) {
                    $jsonFile = $tripGpxPath . '/track_' . $step->id . '.json';
                    file_put_contents($jsonFile, json_encode([
                        'map_points' => $stats['map_points'],
                        'speed_points' => $stats['speed_points']
                    ]));
                    
                    $tripStartDate = $trip->start_date;
                    $tripEndDate = $trip->end_date;
                    
                    if (!$tripStartDate || $stats['start_time'] < $tripStartDate) {
                        $trip->start_date = date('Y-m-d', strtotime($stats['start_time']));
                    }
                    if (!$tripEndDate || $stats['end_time'] > $tripEndDate) {
                        $trip->end_date = date('Y-m-d', strtotime($stats['end_time']));
                    }
                    
                    $trip->update();
                    
                    $this->jsonResponse([
                        'success' => true, 
                        'message' => 'Track uploaded and saved successfully',
                        'file_hash' => $stats['file_hash']
                    ]);
                } else {
                    // Duplicate track (UUID or Hash conflict)
                    $pdo->prepare('DELETE FROM trip_steps WHERE id = ?')->execute([$step->id]);
                    unlink($destination);
                    $this->jsonResponse(['success' => false, 'error' => 'Duplicate track detected'], 409);
                }
            } else {
                unlink($destination);
                $this->jsonResponse(['success' => false, 'error' => 'Invalid or unparseable GPX file'], 400);
            }
        } else {
            $this->jsonResponse(['success' => false, 'error' => 'Failed to move uploaded file'], 500);
        }
    }
}
