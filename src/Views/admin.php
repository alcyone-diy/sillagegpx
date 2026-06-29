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

<?php 
$content = ob_get_clean();
require __DIR__ . '/layout.php'; 
?>
