<?php
namespace App\Controllers;

use App\Models\User;
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
        $stmt = $pdo->prepare("SELECT id, credential_id, created_at FROM user_passkeys WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user->id]);
        $passkeys = $stmt->fetchAll(\PDO::FETCH_OBJ);

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
            $error = __('invalid_credentials') ?? 'Mot de passe actuel incorrect';
            $this->showProfileWithError($error);
            return;
        }

        if (empty($newUsername)) {
            $error = __('username_required') ?? 'Le nom d\'utilisateur est requis';
            $this->showProfileWithError($error);
            return;
        }

        $passwordHash = $user->password_hash;
        if (!empty($newPassword)) {
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        }

        if ($user->update($newUsername, $passwordHash)) {
            $_SESSION['username'] = $newUsername;
            $success = __('profile_updated') ?? 'Profil mis à jour avec succès';
            $this->showProfileWithSuccess($success);
        } else {
            $error = __('update_failed') ?? 'Erreur lors de la mise à jour (nom d\'utilisateur peut-être déjà pris)';
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

    private function showProfileWithError($error) {
        $user = User::findById($_SESSION['user_id']);
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT id, credential_id, created_at FROM user_passkeys WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user->id]);
        $passkeys = $stmt->fetchAll(\PDO::FETCH_OBJ);
        require SRC_PATH . '/Views/profile.php';
    }

    private function showProfileWithSuccess($success) {
        $user = User::findById($_SESSION['user_id']);
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT id, credential_id, created_at FROM user_passkeys WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user->id]);
        $passkeys = $stmt->fetchAll(\PDO::FETCH_OBJ);
        require SRC_PATH . '/Views/profile.php';
    }
}
