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
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        $user = User::findByUsername($username);
        
        if ($user && password_verify($password, $user->password_hash)) {
            $_SESSION['user_id'] = $user->id;
            $_SESSION['username'] = $user->username;
            header('Location: ?route=dashboard');
            exit;
        } else {
            $error = __('invalid_credentials');
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
