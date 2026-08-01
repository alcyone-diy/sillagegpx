<?php
namespace App\Controllers;

use App\Models\ApiToken;
use App\Utils\Database;

class ApiAuthController {
    
    /**
     * OAuth-like automated flow for iOS app.
     * The iOS app opens this URL in ASWebAuthenticationSession.
     */
    public function authorize() {
        $deviceName = trim($_GET['device_name'] ?? 'Sillage iOS');
        $redirectUri = trim($_GET['redirect_uri'] ?? 'sillage://auth');
        
        // 1. If not logged in, redirect to login page (which will redirect back here upon success)
        if (!isset($_SESSION['user_id'])) {
            $target = '?route=api/auth/authorize&device_name=' . urlencode($deviceName) . '&redirect_uri=' . urlencode($redirectUri);
            header('Location: ?route=login&redirect=' . urlencode($target));
            exit;
        }

        $userId = $_SESSION['user_id'];
        
        // 2. We are logged in. Check if this device already has a token
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT token FROM api_tokens WHERE user_id = ? AND device_name = ?");
        $stmt->execute([$userId, $deviceName]);
        $existingToken = $stmt->fetchColumn();

        if ($existingToken) {
            $token = $existingToken;
        } else {
            // 3. Otherwise, create a new token automatically
            $token = ApiToken::create($userId, $deviceName);
        }

        if ($token) {
            // 4. Redirect to the provided redirect_uri with the token
            $separator = (strpos($redirectUri, '?') !== false) ? '&' : '?';
            header('Location: ' . $redirectUri . $separator . 'token=' . urlencode($token));
            exit;
        } else {
            http_response_code(500);
            die("Internal server error: Could not generate or retrieve API token.");
        }
    }
}
