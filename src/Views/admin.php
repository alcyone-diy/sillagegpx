<?php 
$pageTitle = __('admin') . ' - SillageGPX';
ob_start(); 
?>

<div class="dashboard-header">
    <h2>🛠️ <?= __('admin') ?></h2>
</div>

<!-- Global Stats -->
<div class="stats-grid mb-4">
    <div class="stat-box">
        <span class="stat-value"><?= htmlspecialchars($userCount) ?></span>
        <span class="stat-label">Utilisateurs</span>
    </div>
    <div class="stat-box">
        <span class="stat-value" style="color: var(--success);"><?= htmlspecialchars($tripCount) ?></span>
        <span class="stat-label">Navigations</span>
    </div>
    <div class="stat-box">
        <span class="stat-value" style="color: var(--warning);"><?= htmlspecialchars($trackCount) ?></span>
        <span class="stat-label">Traces GPX</span>
    </div>
</div>

<!-- Maintenance Section -->
<div class="glass-card mb-4" style="padding: 1.5rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h3 style="margin-bottom: 0.25rem;">⚙️ Maintenance des traces GPX</h3>
            <p class="text-muted" style="margin: 0; font-size: 0.9rem;">
                Intervalle d'échantillonnage actif : <strong><?= round(STATS_CALC_INTERVAL / 60, 1) ?> min</strong> (<?= STATS_CALC_INTERVAL ?> s). Permet de recalculer les distances, vitesses et de rafraîchir le cache des cartes.
            </p>
        </div>
        <div>
            <button id="btn-recalculate" class="btn btn-outline" style="display: inline-flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                <span id="btn-icon">🔄</span>
                <span id="btn-text">Recalculer toutes les traces</span>
            </button>
        </div>
    </div>
    <div id="recalculate-feedback" style="display: none; margin-top: 1rem;"></div>
</div>

<!-- Users List -->
<h3 class="mb-2">Liste des Utilisateurs</h3>
<div class="glass-card" style="padding: 0; overflow-x: auto;">
    <table style="width: 100%; text-align: left; border-collapse: collapse;">
        <thead>
            <tr style="border-bottom: 1px solid var(--border-glass);">
                <th style="padding: 1rem;">ID</th>
                <th style="padding: 1rem;">Nom d'utilisateur</th>
                <th style="padding: 1rem;">Email</th>
                <th style="padding: 1rem;">Inscription</th>
                <th style="padding: 1rem;">Dernière connexion</th>
                <th style="padding: 1rem; text-align: center;">Navigations</th>
                <th style="padding: 1rem; text-align: center;">Traces</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($usersList as $user): ?>
            <tr style="border-bottom: 1px solid var(--border-glass);">
                <td style="padding: 1rem; color: var(--text-muted);">#<?= htmlspecialchars($user['id']) ?></td>
                <td style="padding: 1rem;">
                    <strong><?= htmlspecialchars($user['username']) ?></strong>
                    <?php if ($user['id'] == 1): ?>
                        <span class="badge badge-public" style="margin-left: 0.5rem; font-size: 0.7rem;">Admin</span>
                    <?php endif; ?>
                </td>
                <td style="padding: 1rem; color: var(--text-muted);"><?= htmlspecialchars($user['email']) ?></td>
                <td style="padding: 1rem; color: var(--text-muted);"><?= htmlspecialchars(date('d/m/Y', strtotime($user['created_at']))) ?></td>
                <td style="padding: 1rem; color: var(--text-muted);">
                    <?= !empty($user['last_login_at']) ? htmlspecialchars(date('d/m/Y H:i', strtotime($user['last_login_at']))) : '<span style="opacity: 0.5;">—</span>' ?>
                </td>
                <td style="padding: 1rem; text-align: center;">
                    <span class="badge badge-success"><?= htmlspecialchars($user['trips_count']) ?></span>
                </td>
                <td style="padding: 1rem; text-align: center;">
                    <span class="badge badge-warning"><?= htmlspecialchars($user['tracks_count']) ?></span>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
document.getElementById('btn-recalculate').addEventListener('click', function() {
    if (!confirm("Voulez-vous recalculer toutes les traces GPX avec le nouvel intervalle de <?= round(STATS_CALC_INTERVAL / 60, 1) ?> minutes ?\n\nCela réanalysera les fichiers GPX d'origine, mettra à jour les distances et vitesses en base et régénérera les données des cartes.")) {
        return;
    }

    const btn = this;
    const btnIcon = document.getElementById('btn-icon');
    const btnText = document.getElementById('btn-text');
    const feedback = document.getElementById('recalculate-feedback');

    btn.disabled = true;
    btn.style.opacity = '0.6';
    btn.style.cursor = 'not-allowed';
    btnIcon.textContent = '⏳';
    btnText.textContent = 'Recalcul en cours...';
    feedback.style.display = 'none';

    fetch('?route=api/admin/recalculate_tracks', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        btn.style.opacity = '1';
        btn.style.cursor = 'pointer';
        btnIcon.textContent = '🔄';
        btnText.textContent = 'Recalculer toutes les traces';

        feedback.style.display = 'block';
        if (data.success) {
            feedback.className = 'alert glass-success';
            let detail = data.message;
            if (data.details && data.details.failed > 0) {
                detail += ' (' + data.details.failed + ' échec(s))';
            }
            feedback.textContent = '✅ ' + detail;
        } else {
            feedback.className = 'alert glass-error';
            feedback.textContent = '❌ Erreur : ' + (data.message || 'Échec du recalcul.');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.style.opacity = '1';
        btn.style.cursor = 'pointer';
        btnIcon.textContent = '🔄';
        btnText.textContent = 'Recalculer toutes les traces';

        feedback.style.display = 'block';
        feedback.className = 'alert glass-error';
        feedback.textContent = '❌ Erreur de communication avec le serveur.';
    });
});
</script>

<?php 
$content = ob_get_clean();
require __DIR__ . '/layout.php'; 
?>
