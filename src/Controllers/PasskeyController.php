<?php
namespace App\Controllers;

use App\Models\User;
use App\Utils\Database;

class PasskeyController {
    private $webAuthn;

    public function __construct() {
        if (!class_exists('\lbuchs\WebAuthn\WebAuthn')) {
            $this->jsonError('WebAuthn library not installed');
        }
        $this->webAuthn = new \lbuchs\WebAuthn\WebAuthn(WEBAUTHN_RP_NAME, WEBAUTHN_RP_ID);
    }

    private function jsonError($message, $code = 400) {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode(['error' => $message]);
        exit;
    }

    private function jsonSuccess($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public function apiRegisterChallenge() {
        if (!isset($_SESSION['user_id'])) {
            $this->jsonError('Must be logged in to register a passkey');
        }

        $userId = (string) $_SESSION['user_id'];
        $username = $_SESSION['username'];

        try {
            // Get already registered keys to prevent re-registering
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("SELECT credential_id FROM user_passkeys WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $existingKeys = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            
            // convert existing keys from hex back to binary
            $existingKeysBin = array_map('hex2bin', $existingKeys);

            $crossPlatformAttachment = null; // null = both cross-platform and platform

            $createArgs = $this->webAuthn->getCreateArgs(
                $userId,
                $username,
                $username,
                20,
                true,
                'discouraged',
                $crossPlatformAttachment,
                $existingKeysBin
            );

            // Save challenge to session
            $_SESSION['webauthn_challenge'] = $this->webAuthn->getChallenge();

            $this->jsonSuccess($createArgs); // It returns a stdClass already
        } catch (\Throwable $e) {
            $this->jsonError($e->getMessage());
        }
    }

    public function apiRegisterVerify() {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['webauthn_challenge'])) {
            $this->jsonError('Invalid session state');
        }

        $input = json_decode(file_get_contents('php://input'), true);

        $clientDataJSON = base64_decode($input['clientDataJSON'] ?? '');
        $attestationObject = base64_decode($input['attestationObject'] ?? '');
        $challenge = $_SESSION['webauthn_challenge'];

        try {
            // 4th arg is bool $requireUserVerification
            $data = $this->webAuthn->processCreate($clientDataJSON, $attestationObject, $challenge, false, true, false);
            
            // Save to database
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("INSERT INTO user_passkeys (user_id, credential_id, public_key, user_handle, sign_count) VALUES (?, ?, ?, ?, ?)");
            
            // Credential ID is binary, we can store as base64 or hex
            $credentialIdHex = bin2hex($data->credentialId);
            $stmt->execute([
                $_SESSION['user_id'],
                $credentialIdHex,
                $data->credentialPublicKey,
                (string)$_SESSION['user_id'],
                $data->signatureCounter ?? 0
            ]);

            unset($_SESSION['webauthn_challenge']);
            $this->jsonSuccess(['success' => true]);

        } catch (\Throwable $e) {
            $this->jsonError($e->getMessage());
        }
    }

    public function apiLoginChallenge() {
        try {
            $getArgs = $this->webAuthn->getGetArgs();
            $_SESSION['webauthn_challenge'] = $this->webAuthn->getChallenge();
            $this->jsonSuccess($getArgs);
        } catch (\Throwable $e) {
            $this->jsonError($e->getMessage());
        }
    }

    public function apiLoginVerify() {
        if (!isset($_SESSION['webauthn_challenge'])) {
            $this->jsonError('Invalid session state');
        }

        $input = json_decode(file_get_contents('php://input'), true);

        $clientDataJSON = base64_decode($input['clientDataJSON'] ?? '');
        $authenticatorData = base64_decode($input['authenticatorData'] ?? '');
        $signature = base64_decode($input['signature'] ?? '');
        $userHandle = base64_decode($input['userHandle'] ?? '');
        $id = base64_decode($input['id'] ?? '');

        $challenge = $_SESSION['webauthn_challenge'];
        $credentialIdHex = bin2hex($id);

        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("SELECT user_id, public_key, sign_count FROM user_passkeys WHERE credential_id = ?");
            $stmt->execute([$credentialIdHex]);
            $credential = $stmt->fetch(\PDO::FETCH_OBJ);

            if (!$credential) {
                $this->jsonError('Credential not found');
            }

            // userHandle is optional on login depending on resident key. If provided, check it.
            if ($userHandle !== '' && $userHandle !== (string)$credential->user_id) {
                $this->jsonError('User handle mismatch');
            }

            $this->webAuthn->processGet(
                $clientDataJSON,
                $authenticatorData,
                $signature,
                $credential->public_key,
                $challenge,
                $credential->sign_count,
                false // User verification not strictly required for this demo
            );

            // Login successful
            $user = User::findById($credential->user_id);
            if ($user) {
                $_SESSION['user_id'] = $user->id;
                $_SESSION['username'] = $user->username;
                unset($_SESSION['webauthn_challenge']);
                $this->jsonSuccess(['success' => true]);
            } else {
                $this->jsonError('User not found');
            }

        } catch (\Throwable $e) {
            $this->jsonError($e->getMessage());
        }
    }
}
