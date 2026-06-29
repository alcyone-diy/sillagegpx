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
            $error = "Invalid username or password.";
            require SRC_PATH . '/Views/login.php';
        }
    }
    
    public function handleRegister() {
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($email) || empty($password)) {
            $error = "All fields are required.";
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
            $error = "Username or email already exists.";
            require SRC_PATH . '/Views/login.php';
        }
    }

    public function handleLogout() {
        session_destroy();
        header('Location: ?route=home');
        exit;
    }
}
