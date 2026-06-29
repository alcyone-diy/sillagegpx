<?php
$pageTitle = __('profile') . ' - ' . __('site_title');
ob_start();
?>

<div class="dashboard-header">
    <h2><?= __('profile') ?></h2>
</div>

<div class="trips-grid">
    <!-- Profile Info Form -->
    <div class="glass-card">
        <h3><?= __('personal_info') ?></h3>
        <form action="?route=profile" method="POST" class="auth-form mt-4">
            <div class="form-group">
                <label for="username"><?= __('username') ?></label>
                <input type="text" id="username" name="username" value="<?= htmlspecialchars($user->username) ?>" required class="form-control glass-input">
            </div>
            
            <hr style="border: 0; border-top: 1px solid var(--border-glass); margin: 1.5rem 0;">
            
            <h4><?= __('change_password_optional') ?></h4>
            <div class="form-group">
                <label for="new_password"><?= __('new_password') ?></label>
                <input type="password" id="new_password" name="new_password" class="form-control glass-input">
            </div>
            
            <hr style="border: 0; border-top: 1px solid var(--border-glass); margin: 1.5rem 0;">
            
            <div class="form-group">
                <label for="current_password"><?= __('current_password_required') ?></label>
                <input type="password" id="current_password" name="current_password" required class="form-control glass-input">
            </div>

            <button type="submit" class="btn btn-primary btn-block mt-4"><?= __('save_changes') ?></button>
        </form>
    </div>

    <!-- Security Settings (Passkeys) -->
    <div class="glass-card">
        <h3><?= __('passkeys_title') ?></h3>
        <p class="text-sm text-muted"><?= __('passkeys_desc') ?></p>
        
        <?php if (!empty($passkeys)): ?>
            <div class="passkeys-list mt-4" style="display: flex; flex-direction: column; gap: 0.5rem;">
                <?php foreach ($passkeys as $pk): ?>
                    <div class="passkey-item" style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 1rem; background: rgba(0,0,0,0.1); border-radius: 8px;">
                        <div>
                            <strong>Passkey</strong>
                            <div class="text-sm text-muted"><?= __('added_on') ?> <?= htmlspecialchars($pk->created_at) ?></div>
                        </div>
                        <form action="?route=api/passkey/delete" method="POST" onsubmit="return confirm('<?= __('confirm_delete_passkey') ?>');" style="margin: 0;">
                            <input type="hidden" name="passkey_id" value="<?= $pk->id ?>">
                            <button type="submit" class="btn btn-error" style="padding: 0.25rem 0.5rem; font-size: 0.8rem; background: rgba(255, 0, 0, 0.2); border: 1px solid rgba(255, 0, 0, 0.3); color: #fff; cursor: pointer; border-radius: 4px;"><?= __('delete') ?></button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <button id="btn-register-passkey" class="btn btn-secondary mt-4"><?= __('add_passkey') ?></button>
    </div>
</div>

<?php ob_start(); ?>
<script>
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

document.getElementById('btn-register-passkey')?.addEventListener('click', async () => {
    try {
        const res = await fetch('?route=api/passkey/register/challenge');
        const options = await res.json();
        
        if (options.error) {
            alert(options.error);
            return;
        }

        recursiveBase64StrToArrayBuffer(options);
        const credential = await navigator.credentials.create(options);

        const clientDataJSON = arrayBufferToBase64(credential.response.clientDataJSON);
        const attestationObject = arrayBufferToBase64(credential.response.attestationObject);

        const verifyRes = await fetch('?route=api/passkey/register/verify', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                clientDataJSON: clientDataJSON,
                attestationObject: attestationObject
            })
        });

        const verifyResult = await verifyRes.json();
        if (verifyResult.success) {
            alert(<?= json_encode(__('passkey_registered')) ?>);
            window.location.reload();
        } else {
            alert(<?= json_encode(__('error')) ?> + " " + verifyResult.error);
        }
    } catch (e) {
        alert(<?= json_encode(__('webauthn_error')) ?> + " " + e.message);
    }
});
</script>
<?php $extraJs = ob_get_clean(); ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
