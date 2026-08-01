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
                            <!-- Safely display the user-provided passkey name (fallback to 'Passkey' just in case) -->
                            <strong><?= htmlspecialchars($pk->name ?? 'Passkey') ?></strong>
                            <div class="text-sm text-muted">
                                <?= __('added_on') ?> <?= htmlspecialchars(substr($pk->created_at, 0, 16)) ?>
                                <?php if (!empty($pk->last_used_at)): ?>
                                    <br><?= __('last_used') ?> <?= htmlspecialchars(substr($pk->last_used_at, 0, 16)) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div style="display: flex; gap: 0.5rem; margin: 0;">
                            <!-- Rename Form -->
                            <form action="?route=api/passkey/rename" method="POST" class="form-rename-passkey" style="margin: 0;">
                                <input type="hidden" name="passkey_id" value="<?= htmlspecialchars($pk->id) ?>">
                                <input type="hidden" name="name" value="">
                                <button type="submit" class="btn btn-xs btn-glass-neutral">
                                    <?= __('rename') ?>
                                </button>
                            </form>
                            <!-- Delete Form -->
                            <form action="?route=api/passkey/delete" method="POST" class="form-delete-passkey" style="margin: 0;">
                                <input type="hidden" name="passkey_id" value="<?= $pk->id ?>">
                                <button type="submit" class="btn btn-xs btn-glass-error">
                                    <?= __('delete') ?>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <button id="btn-register-passkey" class="btn btn-secondary mt-4"><?= __('add_passkey') ?></button>
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

// Rename Form interceptor
document.querySelectorAll('.form-rename-passkey').forEach(form => {
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        let newName = await customPrompt(<?= json_encode(__('passkey_rename_prompt')) ?>);
        if (newName === null) return;
        if (newName.trim() === '') {
            await customAlert(<?= json_encode(__('passkey_name_required')) ?>);
            return;
        }
        form.elements['name'].value = newName.trim();
        form.submit();
    });
});

// Delete Form interceptor
document.querySelectorAll('.form-delete-passkey').forEach(form => {
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        let confirmed = await customConfirm(<?= json_encode(__('confirm_delete_passkey')) ?>);
        if (confirmed) {
            form.submit();
        }
    });
});

document.getElementById('btn-register-passkey')?.addEventListener('click', async () => {
    try {
        let passkeyName = await customPrompt(<?= json_encode(__('passkey_name_prompt')) ?>);
        if (passkeyName === null) return;
        if (passkeyName.trim() === '') {
            await customAlert(<?= json_encode(__('passkey_name_required')) ?>);
            return;
        }

        const res = await fetch('?route=api/passkey/register/challenge');
        const options = await res.json();
        
        if (options.error) {
            await customAlert(options.error);
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
                attestationObject: attestationObject,
                name: passkeyName.trim()
            })
        });

        const verifyResult = await verifyRes.json();
        if (verifyResult.success) {
            await customAlert(<?= json_encode(__('passkey_registered')) ?>);
            window.location.reload();
        } else {
            await customAlert(<?= json_encode(__('error')) ?> + " " + verifyResult.error);
        }
    } catch (e) {
        // Si l'utilisateur annule simplement la fenêtre du système (navigateur/OS), on ne fait rien
        if (e.name === 'NotAllowedError') {
            console.log('Enregistrement annulé par l\'utilisateur.');
        } else {
            await customAlert(<?= json_encode(__('webauthn_error')) ?> + " " + e.message);
        }
    }
});
</script>
<?php $extraJs = ob_get_clean(); ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
