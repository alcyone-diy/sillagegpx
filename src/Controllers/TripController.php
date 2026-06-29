<?php
namespace App\Controllers;

use App\Models\Trip;
use App\Models\TripStep;
use App\Models\GpxTrack;
use App\Models\TripLink;
use App\Utils\GpxParser;

class TripController {
    
    private function requireAuth() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ?route=login');
            exit;
        }
        
        // Ensure user actually exists in the database (e.g. after a reset)
        $user = \App\Models\User::findById((int)$_SESSION['user_id']);
        if (!$user) {
            unset($_SESSION['user_id']);
            header('Location: ?route=login');
            exit;
        }
        
        return $_SESSION['user_id'];
    }

    public function showDashboard() {
        $userId = $this->requireAuth();
        $trips = Trip::findAllByUser($userId);
        require SRC_PATH . '/Views/dashboard.php';
    }

    public function showCreateForm() {
        $this->requireAuth();
        require SRC_PATH . '/Views/trip_form.php';
    }

    public function handleCreate() {
        $userId = $this->requireAuth();
        
        $title = $_POST['title'] ?? 'New Trip';
        $boatName = $_POST['boat_name'] ?? null;
        $comment = $_POST['comment'] ?? null;
        $visibility = $_POST['visibility'] ?? 'private';
        
        $trip = Trip::create($userId, $title, null, null, $boatName, $comment, $visibility);
        
        if ($trip) {
            // Handle Links
            if (isset($_POST['links_url']) && is_array($_POST['links_url'])) {
                foreach ($_POST['links_url'] as $index => $url) {
                    $url = trim($url);
                    if (!empty($url)) {
                        $label = isset($_POST['links_label'][$index]) ? trim($_POST['links_label'][$index]) : null;
                        TripLink::create($trip->id, $url, $label ?: null);
                    }
                }
            }
            
            // Handle GPX upload
            if (isset($_FILES['gpx_files']) && !empty($_FILES['gpx_files']['name'][0])) {
                $this->processGpxUploads($trip, $_FILES['gpx_files']);
            }
            header("Location: ?route=trip&id=" . $trip->id);
            exit;
        } else {
            $error = "Failed to create trip.";
            require SRC_PATH . '/Views/trip_form.php';
        }
    }

    private function processGpxUploads(Trip $trip, array $files, int $startingOrder = 0) {
        // Ensure upload directories exist
        if (!is_dir(GPX_PATH)) {
            mkdir(GPX_PATH, 0755, true);
        }
        $tripGpxPath = GPX_PATH . '/' . $trip->user_id . '/' . $trip->id;
        if (!is_dir($tripGpxPath)) {
            mkdir($tripGpxPath, 0755, true);
        }
        
        // Simple step logic: one step per file for now
        $order = $startingOrder;
        $tripStartDate = $trip->start_date ? date('Y-m-d H:i:s', strtotime($trip->start_date)) : null;
        $tripEndDate = $trip->end_date ? date('Y-m-d H:i:s', strtotime($trip->end_date)) : null;

        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $tmpName = $files['tmp_name'][$i];
                $name = basename($files['name'][$i]);
                
                $safeName = $trip->user_id . '/' . $trip->id . '/' . time() . '_' . preg_replace('/[^a-zA-Z0-9.-]/', '_', $name);
                $destination = GPX_PATH . '/' . $safeName;
                
                if (move_uploaded_file($tmpName, $destination)) {
                    $stats = GpxParser::parse($destination);
                    if ($stats) {
                        $stepTitle = pathinfo($name, PATHINFO_FILENAME);
                        $step = TripStep::create($trip->id, $stepTitle, $order++);
                        
                        GpxTrack::create($step->id, $safeName, $stats);
                        
                        // Save downsampled data for frontend in the trip directory
                        $jsonFile = GPX_PATH . '/' . $trip->user_id . '/' . $trip->id . '/track_' . $step->id . '.json';
                        file_put_contents($jsonFile, json_encode([
                            'map_points' => $stats['map_points'],
                            'speed_points' => $stats['speed_points']
                        ]));
                        
                        // Update trip start/end date
                        if (!$tripStartDate || $stats['start_time'] < $tripStartDate) {
                            $tripStartDate = $stats['start_time'];
                        }
                        if (!$tripEndDate || $stats['end_time'] > $tripEndDate) {
                            $tripEndDate = $stats['end_time'];
                        }
                    }
                }
            }
        }
        
        if ($tripStartDate) {
            $trip->start_date = date('Y-m-d', strtotime($tripStartDate));
            $trip->end_date = date('Y-m-d', strtotime($tripEndDate));
            $trip->update();
        }
    }

    public function showEditForm() {
        $userId = $this->requireAuth();
        $id = $_GET['id'] ?? null;
        
        $trip = Trip::findById((int)$id);
        if (!$trip || $trip->user_id !== $userId) {
            http_response_code(403);
            die("Access denied.");
        }
        
        $existingSteps = TripStep::findByTripId($trip->id);
        $existingLinks = TripLink::findByTripId($trip->id);
        
        require SRC_PATH . '/Views/trip_form.php';
    }

    public function handleEdit() {
        $userId = $this->requireAuth();
        $tripId = (int)($_POST['trip_id'] ?? 0);
        
        $trip = Trip::findById($tripId);
        if (!$trip || $trip->user_id !== $userId) {
            http_response_code(403);
            die("Access denied.");
        }
        
        $trip->title = $_POST['title'] ?? $trip->title;
        $trip->boat_name = $_POST['boat_name'] ?? null;
        $trip->comment = $_POST['comment'] ?? null;
        $trip->visibility = $_POST['visibility'] ?? $trip->visibility;
        
        // If it changes to unlisted and didn't have a token, generate one
        if ($trip->visibility === 'unlisted' && empty($trip->unlisted_token)) {
            $trip->unlisted_token = bin2hex(random_bytes(16));
        }
        
        $trip->update();
        
        // Handle Links
        TripLink::deleteByTripId($trip->id);
        if (isset($_POST['links_url']) && is_array($_POST['links_url'])) {
            foreach ($_POST['links_url'] as $index => $url) {
                $url = trim($url);
                if (!empty($url)) {
                    $label = isset($_POST['links_label'][$index]) ? trim($_POST['links_label'][$index]) : null;
                    TripLink::create($trip->id, $url, $label ?: null);
                }
            }
        }
        
        // Handle new GPX uploads if any
        if (isset($_FILES['gpx_files']) && !empty($_FILES['gpx_files']['name'][0])) {
            $steps = TripStep::findByTripId($trip->id);
            $maxOrder = 0;
            foreach ($steps as $step) {
                if ($step->order_index > $maxOrder) {
                    $maxOrder = $step->order_index;
                }
            }
            $startingOrder = count($steps) > 0 ? $maxOrder + 1 : 0;
            
            $this->processGpxUploads($trip, $_FILES['gpx_files'], $startingOrder);
        }
        
        header("Location: ?route=trip&id=" . $trip->id);
        exit;
    }

    public function handleDeleteTrack() {
        $userId = $this->requireAuth();
        
        $data = json_decode(file_get_contents('php://input'), true);
        $stepId = (int)($data['step_id'] ?? 0);
        
        $step = TripStep::findById($stepId);
        if (!$step) {
            http_response_code(404);
            die(json_encode(['error' => 'Step not found']));
        }
        
        $trip = Trip::findById($step->trip_id);
        if (!$trip || $trip->user_id !== $userId) {
            http_response_code(403);
            die(json_encode(['error' => 'Access denied']));
        }
        
        // Delete files
        $tracks = GpxTrack::findByTripStepId($stepId);
        foreach ($tracks as $track) {
            $gpxFile = GPX_PATH . '/' . $track->file_path;
            if (file_exists($gpxFile)) {
                unlink($gpxFile);
                $tripDir = dirname($gpxFile);
                if (is_dir($tripDir) && count(scandir($tripDir)) == 2) { // just . and ..
                    rmdir($tripDir);
                    $userDir = dirname($tripDir);
                    if (is_dir($userDir) && count(scandir($userDir)) == 2) {
                        rmdir($userDir);
                    }
                }
            }
        }
        
        $jsonFile = GPX_PATH . '/' . $trip->user_id . '/' . $trip->id . '/track_' . $stepId . '.json';
        if (file_exists($jsonFile)) {
            unlink($jsonFile);
        }
        
        // Delete from DB
        $pdo = \App\Utils\Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM trip_steps WHERE id = ?');
        $stmt->execute([$stepId]);
        
        echo json_encode(['success' => true]);
        exit;
    }

    public function handleRegenerateToken() {
        $userId = $this->requireAuth();
        
        $data = json_decode(file_get_contents('php://input'), true);
        $tripId = (int)($data['trip_id'] ?? 0);
        
        $trip = Trip::findById($tripId);
        if (!$trip || $trip->user_id !== $userId) {
            http_response_code(403);
            die(json_encode(['error' => 'Access denied']));
        }
        
        if ($trip->visibility === 'unlisted') {
            $trip->unlisted_token = bin2hex(random_bytes(16));
            $trip->update();
            echo json_encode(['success' => true, 'new_token' => $trip->unlisted_token]);
        } else {
            echo json_encode(['error' => 'Trip is not unlisted']);
        }
        exit;
    }

    public function showTrip() {
        $id = $_GET['id'] ?? null;
        $token = $_GET['token'] ?? null;
        
        $trip = null;
        if ($id) {
            $trip = Trip::findById((int)$id);
        } elseif ($token) {
            $trip = Trip::findByToken($token);
        }
        
        if (!$trip) {
            http_response_code(404);
            die("Trip not found.");
        }
        
        // Visibility checks
        $isOwner = isset($_SESSION['user_id']) && $_SESSION['user_id'] == $trip->user_id;
        
        if ($trip->visibility === 'private' && !$isOwner) {
            http_response_code(403);
            die("Access denied.");
        }
        
        if (!$isOwner) {
            $trip->incrementViews();
        }
        
        $steps = TripStep::findByTripId($trip->id);
        $links = TripLink::findByTripId($trip->id);
        $tracksData = [];
        
        foreach ($steps as $step) {
            $tracks = GpxTrack::findByTripStepId($step->id);
            if (!empty($tracks)) {
                // For simplicity, take the first track of the step
                $track = $tracks[0];
                $jsonFile = GPX_PATH . '/' . $trip->user_id . '/' . $trip->id . '/track_' . $step->id . '.json';
                $data = [];
                if (file_exists($jsonFile)) {
                    $data = json_decode(file_get_contents($jsonFile), true);
                }
                
                $tracksData[] = [
                    'step_id' => $step->id,
                    'title' => $step->title,
                    'stats' => $track,
                    'data' => $data
                ];
            }
        }
        
        require SRC_PATH . '/Views/trip_view.php';
    }

    public function apiTrackData() {
        // Optional API endpoint if we want to fetch data via AJAX instead of embedding it
        $stepId = (int)($_GET['step_id'] ?? 0);
        $step = TripStep::findById($stepId);
        
        if ($step) {
            $tripRecord = Trip::findById($step->trip_id);
            if ($tripRecord) {
                $jsonFile = GPX_PATH . '/' . $tripRecord->user_id . '/' . $step->trip_id . '/track_' . $stepId . '.json';
                if (file_exists($jsonFile)) {
                    header('Content-Type: application/json');
                    readfile($jsonFile);
                    exit;
                }
            }
        }
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
        exit;
    }
}
