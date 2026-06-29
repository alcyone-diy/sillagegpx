<?php
$pageTitle = __('login') . ' - ' . __('site_title');
ob_start();
?>

<div class="auth-container">
    <div class="auth-box glass-card">
        <h2><?= __('welcome_back') ?></h2>
        <form action="?route=login" method="POST" class="auth-form">
            <div class="form-group">
                <label for="username"><?= __('username') ?></label>
                <input type="text" id="username" name="username" required class="form-control glass-input">
            </div>
            <div class="form-group">
                <label for="password"><?= __('password') ?></label>
                <input type="password" id="password" name="password" required class="form-control glass-input">
            </div>
            <button type="submit" class="btn btn-primary btn-block"><?= __('log_in') ?></button>
        </form>
    </div>

    <div class="auth-box glass-card">
        <h2><?= __('new_to_site') ?></h2>
        <form action="?route=login" method="POST" class="auth-form">
            <div class="form-group">
                <label for="reg_username"><?= __('username') ?></label>
                <input type="text" id="reg_username" name="username" required class="form-control glass-input">
            </div>
            <div class="form-group">
                <label for="email"><?= __('email') ?></label>
                <input type="email" id="email" name="email" required class="form-control glass-input">
            </div>
            <div class="form-group">
                <label for="reg_password"><?= __('password') ?></label>
                <input type="password" id="reg_password" name="password" required class="form-control glass-input">
            </div>
            <button type="submit" name="register" class="btn btn-primary btn-block"><?= __('create_account') ?></button>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
