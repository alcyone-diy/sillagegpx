<?php
namespace App\Controllers;

use App\Models\User;
use App\Models\ApiToken;
use App\Utils\Database;

class ProfileController {
    public function __construct() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ?route=login');
            exit;
        }
    }

    public function showProfile() {
        $user = User::findById($_SESSION['user_id']);
        if (!$user) {
            session_destroy();
            header('Location: ?route=login');
            exit;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT id, name, credential_id, created_at, last_used_at FROM user_passkeys WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user->id]);
        $passkeys = $stmt->fetchAll(\PDO::FETCH_OBJ);

        $apiTokens = ApiToken::findByUserId($user->id);

        require SRC_PATH . '/Views/profile.php';
    }

    public function updateProfile() {
        $user = User::findById($_SESSION['user_id']);
        if (!$user) {
            header('Location: ?route=login');
            exit;
        }

        $currentPassword = $_POST['current_password'] ?? '';
        $newUsername = trim($_POST['username'] ?? '');
        $newPassword = $_POST['new_password'] ?? '';

        if (!password_verify($currentPassword, $user->password_hash)) {
            $error = __('invalid_credentials');
            $this->showProfileWithError($error);
            return;
        }

        if (empty($newUsername)) {
            $error = __('username_required');
            $this->showProfileWithError($error);
            return;
        }

        $passwordHash = $user->password_hash;
        if (!empty($newPassword)) {
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        }

        if ($user->update($newUsername, $passwordHash)) {
            $_SESSION['username'] = $newUsername;
            $success = __('profile_updated');
            $this->showProfileWithSuccess($success);
        } else {
            $error = __('update_failed');
            $this->showProfileWithError($error);
        }
    }

    public function deletePasskey() {
        $passkeyId = $_POST['passkey_id'] ?? null;
        if ($passkeyId) {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("DELETE FROM user_passkeys WHERE id = ? AND user_id = ?");
            $stmt->execute([$passkeyId, $_SESSION['user_id']]);
        }
        header('Location: ?route=profile');
        exit;
    }

    public function createApiToken() {
        $deviceName = trim($_POST['device_name'] ?? '');
        if (!empty($deviceName)) {
            $token = ApiToken::create($_SESSION['user_id'], $deviceName);
            if ($token) {
                $_SESSION['new_api_token'] = $token;
                $_SESSION['new_api_token_name'] = $deviceName;
            }
        }
        header('Location: ?route=profile');
        exit;
    }

    public function deleteApiToken() {
        $tokenId = $_POST['token_id'] ?? null;
        if ($tokenId) {
            ApiToken::delete($tokenId, $_SESSION['user_id']);
        }
        header('Location: ?route=profile');
        exit;
    }

    public function renamePasskey() {
        $passkeyId = $_POST['passkey_id'] ?? null;
        $newName = trim($_POST['name'] ?? '');

        // Enforce strict name constraints before updating (prevent empty string bypass)
        if ($passkeyId && !empty($newName)) {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("UPDATE user_passkeys SET name = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$newName, $passkeyId, $_SESSION['user_id']]);
        }
        header('Location: ?route=profile');
        exit;
    }

    private function showProfileWithError($error) {
        $user = User::findById($_SESSION['user_id']);
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT id, name, credential_id, created_at, last_used_at FROM user_passkeys WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user->id]);
        $passkeys = $stmt->fetchAll(\PDO::FETCH_OBJ);
        $apiTokens = ApiToken::findByUserId($user->id);
        require SRC_PATH . '/Views/profile.php';
    }

    private function showProfileWithSuccess($success) {
        $user = User::findById($_SESSION['user_id']);
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT id, name, credential_id, created_at, last_used_at FROM user_passkeys WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user->id]);
        $passkeys = $stmt->fetchAll(\PDO::FETCH_OBJ);
        $apiTokens = ApiToken::findByUserId($user->id);
        require SRC_PATH . '/Views/profile.php';
    }
}
