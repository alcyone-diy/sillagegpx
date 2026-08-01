<?php
namespace App\Middleware;

use App\Utils\Database;

class ApiAuth {
    /**
     * Authenticates the API request using a Bearer token.
     * If valid, updates the last_used_at timestamp and returns the user ID.
     * If invalid, outputs a 401 JSON response and exits.
     * 
     * @return int The authenticated user ID
     */
    public static function authenticate() {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        // Fallback for Apache if HTTP_AUTHORIZATION is stripped by default rules
        if (empty($authHeader) && function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        }

        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = trim($matches[1]);

            try {
                $pdo = Database::getConnection();
                $stmt = $pdo->prepare("SELECT id, user_id FROM api_tokens WHERE token = ?");
                $stmt->execute([$token]);
                $tokenData = $stmt->fetch(\PDO::FETCH_OBJ);

                if ($tokenData) {
                    // Update the last used timestamp
                    $updateStmt = $pdo->prepare("UPDATE api_tokens SET last_used_at = CURRENT_TIMESTAMP WHERE id = ?");
                    $updateStmt->execute([$tokenData->id]);

                    return (int)$tokenData->user_id;
                }
            } catch (\Exception $e) {
                // Database error
                header('Content-Type: application/json');
                http_response_code(500);
                echo json_encode(['error' => 'Internal server error.']);
                exit;
            }
        }

        // Token not found or invalid header format
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized or invalid API token.']);
        exit;
    }
}
