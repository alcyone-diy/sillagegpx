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
            <hr style="border: 0; border-top: 1px solid var(--border-glass); margin: 1.5rem 0;">
            <button type="button" id="btn-login-passkey" class="btn btn-secondary btn-block">Se connecter avec Passkey</button>
        </form>
    </div>

<?php ob_start(); ?>
<script>
// WebAuthn Helpers
function recursiveBase64StrToArrayBuffer(obj) {
    let prefix = '=?BINARY?B?';
    let suffix = '?=';
    if (typeof obj === 'object' && obj !== null) {
        for (let key in obj) {
            if (typeof obj[key] === 'string') {
                let str = obj[key];
                if (str.substring(0, prefix.length) === prefix && str.substring(str.length - suffix.length) === suffix) {
                    let b64url = str.substring(prefix.length, str.length - suffix.length);
                    let padding = '='.repeat((4 - b64url.length % 4) % 4);
                    let b64 = (b64url + padding).replace(/-/g, '+').replace(/_/g, '/');
                    let byteString = atob(b64);
                    let arrayBuffer = new ArrayBuffer(byteString.length);
                    let intArray = new Uint8Array(arrayBuffer);
                    for (let i = 0; i < byteString.length; i++) {
                        intArray[i] = byteString.charCodeAt(i);
                    }
                    obj[key] = arrayBuffer;
                }
            } else {
                recursiveBase64StrToArrayBuffer(obj[key]);
            }
        }
    }
}

function arrayBufferToBase64(buffer) {
    let binary = '';
    let bytes = new Uint8Array(buffer);
    let len = bytes.byteLength;
    for (let i = 0; i < len; i++) {
        binary += String.fromCharCode(bytes[i]);
    }
    return window.btoa(binary);
}

document.getElementById('btn-login-passkey').addEventListener('click', async () => {
    try {
        const res = await fetch('?route=api/passkey/login/challenge');
        const options = await res.json();
        
        if (options.error) {
            alert(options.error);
            return;
        }

        // Convert the JSON object back into buffers
        recursiveBase64StrToArrayBuffer(options);

        // Call WebAuthn API
        const credential = await navigator.credentials.get(options);

        // Convert response buffers back to Base64 to send to server
        const clientDataJSON = arrayBufferToBase64(credential.response.clientDataJSON);
        const authenticatorData = arrayBufferToBase64(credential.response.authenticatorData);
        const signature = arrayBufferToBase64(credential.response.signature);
        const userHandle = credential.response.userHandle ? arrayBufferToBase64(credential.response.userHandle) : '';
        const id = arrayBufferToBase64(credential.rawId);

        const verifyRes = await fetch('?route=api/passkey/login/verify', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                clientDataJSON: clientDataJSON,
                authenticatorData: authenticatorData,
                signature: signature,
                userHandle: userHandle,
                id: id
            })
        });

        const verifyResult = await verifyRes.json();
        if (verifyResult.success) {
            window.location.href = '?route=dashboard';
        } else {
            alert(<?= json_encode(__('error')) ?> + " " + verifyResult.error);
        }
    } catch (e) {
        alert(<?= json_encode(__('webauthn_error')) ?> + " " + e.message);
    }
});
</script>
<?php $extraJs = ob_get_clean(); ?>

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
            <?php if (defined('TURNSTILE_SITE_KEY') && TURNSTILE_SITE_KEY !== ''): ?>
            <div class="form-group">
                <div class="cf-turnstile" data-sitekey="<?= htmlspecialchars(TURNSTILE_SITE_KEY) ?>" data-theme="light"></div>
            </div>
            <?php endif; ?>
            <button type="submit" name="register" class="btn btn-primary btn-block"><?= __('create_account') ?></button>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
