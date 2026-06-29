<?php
namespace App\Controllers;

use App\Models\User;

class AuthController {
    
    public function showLogin() {
        if (isset($_SESSION['user_id'])) {
            header('Location: ?route=dashboard');
            exit;
        }
        require SRC_PATH . '/Views/login.php';
    }

    public function handleLogin() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $pdo = \App\Utils\Database::getConnection();

        // 1. Check if IP is locked out (>= 5 attempts in last 15 minutes)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempt_time > datetime('now', '-15 minutes')");
        $stmt->execute([$ip]);
        $attempts = $stmt->fetchColumn();

        if ($attempts >= 5) {
            $error = "Trop de tentatives échouées. Par sécurité, veuillez patienter 15 minutes.";
            require SRC_PATH . '/Views/login.php';
            return;
        }

        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        $user = User::findByUsername($username);
        
        if ($user && password_verify($password, $user->password_hash)) {
            // Success: clear past failed attempts for this IP
            $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
            $stmt->execute([$ip]);

            $_SESSION['user_id'] = $user->id;
            $_SESSION['username'] = $user->username;
            header('Location: ?route=dashboard');
            exit;
        } else {
            // Failure: log this attempt
            $stmt = $pdo->prepare("INSERT INTO login_attempts (ip_address) VALUES (?)");
            $stmt->execute([$ip]);

            $error = __('invalid_credentials') ?? 'Identifiants incorrects';
            require SRC_PATH . '/Views/login.php';
        }
    }
    
    public function handleRegister() {
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($email) || empty($password)) {
            $error = __('fields_required');
            require SRC_PATH . '/Views/login.php';
            return;
        }

        if (defined('TURNSTILE_SECRET_KEY') && TURNSTILE_SECRET_KEY !== '') {
            $turnstileResponse = $_POST['cf-turnstile-response'] ?? '';
            if (empty($turnstileResponse)) {
                $error = "Veuillez valider que vous n'êtes pas un robot.";
                require SRC_PATH . '/Views/login.php';
                return;
            }

            $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                'secret' => TURNSTILE_SECRET_KEY,
                'response' => $turnstileResponse,
                'remoteip' => $_SERVER['REMOTE_ADDR']
            ]));
            $verifyResponse = curl_exec($ch);
            curl_close($ch);

            $responseData = json_decode($verifyResponse);
            if (!$responseData || !$responseData->success) {
                $error = "Validation anti-robot échouée. Veuillez réessayer.";
                require SRC_PATH . '/Views/login.php';
                return;
            }
        }
        
        $user = User::create($username, $email, $password);
        
        if ($user) {
            $_SESSION['user_id'] = $user->id;
            $_SESSION['username'] = $user->username;
            header('Location: ?route=dashboard');
            exit;
        } else {
            $error = __('user_exists');
            require SRC_PATH . '/Views/login.php';
        }
    }

    public function handleLogout() {
        session_destroy();
        header('Location: ?route=home');
        exit;
    }
}
