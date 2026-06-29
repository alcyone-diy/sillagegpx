<?php
$pageTitle = 'Login / Register - SillageGPX';
ob_start();
?>

<div class="auth-container">
    <div class="auth-box glass-card">
        <h2>Welcome Back</h2>
        <form action="?route=login" method="POST" class="auth-form">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required class="form-control glass-input">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required class="form-control glass-input">
            </div>
            <button type="submit" class="btn btn-primary btn-block">Log In</button>
        </form>
    </div>

    <div class="auth-box glass-card">
        <h2>New to SillageGPX?</h2>
        <form action="?route=login" method="POST" class="auth-form">
            <div class="form-group">
                <label for="reg_username">Username</label>
                <input type="text" id="reg_username" name="username" required class="form-control glass-input">
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required class="form-control glass-input">
            </div>
            <div class="form-group">
                <label for="reg_password">Password</label>
                <input type="password" id="reg_password" name="password" required class="form-control glass-input">
            </div>
            <button type="submit" name="register" class="btn btn-outline btn-block">Create Account</button>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
