<?php
$pageTitle = __('dashboard') . ' - ' . __('site_title');
ob_start();
?>

<div class="dashboard-header">
    <h2><?= __('your_logbook') ?></h2>
    <a href="?route=create_trip" class="btn btn-primary"><?= __('log_new_trip') ?></a>
</div>

<?php if (empty($trips)): ?>
    <div class="empty-state glass-card">
        <div class="empty-icon">🌊</div>
        <h3><?= __('no_trips') ?></h3>
        <p><?= __('no_trips_desc') ?></p>
        <a href="?route=create_trip" class="btn btn-primary mt-4"><?= __('log_first_trip') ?></a>
    </div>
<?php else: ?>
    <div class="trips-grid">
        <?php foreach ($trips as $trip): ?>
            <div class="trip-card glass-card" onclick="window.location.href='?route=trip&id=<?= $trip->id ?>'" style="cursor: pointer;">
                <div class="trip-card-header">
                    <h3 style="margin-bottom: 0; color: var(--accent-primary);"><?= htmlspecialchars($trip->title) ?></h3>
                    <span class="badge badge-<?= htmlspecialchars($trip->visibility) ?>"><?= htmlspecialchars($trip->visibility) ?></span>
                </div>
                
                <div class="trip-card-body" style="margin-top: 1rem;">
                    <?php if ($trip->boat_name): ?>
                        <p class="text-sm"><strong><?= __('boat') ?>:</strong> <?= htmlspecialchars($trip->boat_name) ?></p>
                    <?php endif; ?>
                    
                    <?php if ($trip->start_date): ?>
                        <p class="text-sm"><strong><?= __('date') ?>:</strong> <?= htmlspecialchars($trip->start_date) ?> 
                        <?php if ($trip->end_date && $trip->end_date != $trip->start_date) echo ' ' . __('to') . ' ' . htmlspecialchars($trip->end_date); ?>
                        </p>
                    <?php endif; ?>
                    
                    <div class="trip-stats text-muted text-sm mt-2">
                        <span>👁️ <?= $trip->views_count ?> <?= __('views') ?></span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="glass-card mt-4">
    <h3><?= __('security_settings') ?? 'Paramètres de sécurité' ?></h3>
    <p class="text-sm text-muted">Ajoutez une clé d'accès (Passkey) pour vous connecter sans mot de passe avec TouchID, FaceID, ou Windows Hello.</p>
    <button id="btn-register-passkey" class="btn btn-secondary mt-2">Enregistrer un Passkey</button>
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

document.getElementById('btn-register-passkey').addEventListener('click', async () => {
    try {
        const res = await fetch('?route=api/passkey/register/challenge');
        const options = await res.json();
        
        if (options.error) {
            alert(options.error);
            return;
        }

        // Convert the JSON object back into buffers
        recursiveBase64StrToArrayBuffer(options);

        // Call WebAuthn API
        const credential = await navigator.credentials.create(options);

        // Convert response buffers back to Base64 to send to server
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
