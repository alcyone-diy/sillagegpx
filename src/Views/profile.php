<?php
$pageTitle = __('profile') ?? 'Profil' . ' - ' . __('site_title');
ob_start();
?>

<div class="dashboard-header">
    <h2>Mon Profil</h2>
    <a href="?route=dashboard" class="btn btn-secondary">Retour au Tableau de Bord</a>
</div>

<div class="trips-grid">
    <!-- Profile Info Form -->
    <div class="glass-card">
        <h3>Informations Personnelles</h3>
        <form action="?route=profile" method="POST" class="auth-form mt-4">
            <div class="form-group">
                <label for="username">Nom d'utilisateur</label>
                <input type="text" id="username" name="username" value="<?= htmlspecialchars($user->username) ?>" required class="form-control glass-input">
            </div>
            
            <hr style="border: 0; border-top: 1px solid var(--border-glass); margin: 1.5rem 0;">
            
            <h4>Changer le mot de passe (Optionnel)</h4>
            <div class="form-group">
                <label for="new_password">Nouveau mot de passe</label>
                <input type="password" id="new_password" name="new_password" class="form-control glass-input">
            </div>
            
            <hr style="border: 0; border-top: 1px solid var(--border-glass); margin: 1.5rem 0;">
            
            <div class="form-group">
                <label for="current_password">Mot de passe actuel (requis pour enregistrer)</label>
                <input type="password" id="current_password" name="current_password" required class="form-control glass-input">
            </div>

            <button type="submit" class="btn btn-primary btn-block mt-4">Enregistrer les modifications</button>
        </form>
    </div>

    <!-- Security Settings (Passkeys) -->
    <div class="glass-card">
        <h3>Clés d'accès (Passkeys)</h3>
        <p class="text-sm text-muted">Connectez-vous sans mot de passe à l'aide de TouchID, FaceID ou de votre appareil.</p>
        
        <?php if (!empty($passkeys)): ?>
            <div class="passkeys-list mt-4" style="display: flex; flex-direction: column; gap: 0.5rem;">
                <?php foreach ($passkeys as $pk): ?>
                    <div class="passkey-item" style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 1rem; background: rgba(0,0,0,0.1); border-radius: 8px;">
                        <div>
                            <strong>Passkey</strong>
                            <div class="text-sm text-muted">Ajouté le <?= htmlspecialchars($pk->created_at) ?></div>
                        </div>
                        <form action="?route=api/passkey/delete" method="POST" onsubmit="return confirm('Supprimer ce Passkey ?');" style="margin: 0;">
                            <input type="hidden" name="passkey_id" value="<?= $pk->id ?>">
                            <button type="submit" class="btn btn-error" style="padding: 0.25rem 0.5rem; font-size: 0.8rem; background: rgba(255, 0, 0, 0.2); border: 1px solid rgba(255, 0, 0, 0.3); color: #fff; cursor: pointer; border-radius: 4px;">Supprimer</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <button id="btn-register-passkey" class="btn btn-secondary mt-4">Ajouter un nouveau Passkey</button>
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
            alert("Passkey enregistré avec succès !");
            window.location.reload();
        } else {
            alert("Erreur: " + verifyResult.error);
        }
    } catch (e) {
        alert("Erreur WebAuthn: " + e.message);
    }
});
</script>
<?php $extraJs = ob_get_clean(); ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
