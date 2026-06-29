<?php
$pageTitle = htmlspecialchars($trip->title) . ' - SillageGPX';
ob_start();
?>

<div class="trip-header glass-card">
    <div class="trip-header-content">
        <h1 class="trip-title"><?= htmlspecialchars($trip->title) ?></h1>
        <div class="trip-meta text-muted">
            <?php if ($trip->boat_name): ?>
                <span>⛵ <?= htmlspecialchars($trip->boat_name) ?></span> &bull; 
            <?php endif; ?>
            <?php if ($trip->start_date): ?>
                <span>📅 <?= htmlspecialchars($trip->start_date) ?></span> &bull; 
            <?php endif; ?>
            <span>👁️ <?= $trip->views_count ?> <?= __('views') ?></span>
        </div>
        
        <?php if ($trip->comment): ?>
            <div class="trip-comment">
                <p><?= nl2br(htmlspecialchars($trip->comment)) ?></p>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($links)): ?>
            <div class="trip-links mt-3 pt-3" style="border-top: 1px solid rgba(0,0,0,0.05);">
                <h4 style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.5rem; color: var(--text-color); opacity: 0.7; font-weight: 600;"><?= __('useful_links') ?></h4>
                <div class="d-flex" style="flex-wrap: wrap; gap: 0.5rem;">
                    <?php foreach ($links as $link): ?>
                        <a href="<?= htmlspecialchars($link->url) ?>" target="_blank" rel="noopener noreferrer" class="badge badge-link hover-lift" style="text-decoration: none; display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.4rem 0.8rem; background: rgba(255,255,255,0.7); border: 1px solid rgba(0,0,0,0.05); color: var(--text-color); border-radius: 20px; transition: all 0.2s; box-shadow: 0 2px 5px rgba(0,0,0,0.02);">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="opacity: 0.6;"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
                            <?= htmlspecialchars($link->label ?: parse_url($link->url, PHP_URL_HOST) ?: $link->url) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $trip->user_id): ?>
        <div class="trip-owner-actions">
            <div class="d-flex" style="gap: 1rem; align-items: center; margin-bottom: 0.5rem;">
                <span class="badge badge-<?= htmlspecialchars($trip->visibility) ?>"><?= htmlspecialchars($trip->visibility) ?></span>
                <a href="?route=edit_trip&id=<?= $trip->id ?>" class="btn btn-outline btn-sm"><?= __('edit_trip') ?></a>
            </div>
            <?php if ($trip->visibility === 'unlisted'): ?>
                <div class="share-link-box">
                    <button class="btn btn-outline btn-sm" id="copyBtn" data-token="<?= htmlspecialchars($trip->unlisted_token) ?>" onclick="copyShareUrl()"><?= __('copy_link') ?></button>
                    <button class="btn btn-outline btn-sm" onclick="regenerateToken(<?= $trip->id ?>)" title="Generate new link"><?= __('regenerate') ?></button>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>



<?php if (empty($tracksData)): ?>
    <div class="empty-state glass-card mt-4">
        <p><?= __('no_tracks') ?></p>
    </div>
<?php else: ?>
    <!-- Pass tracks data to JS securely -->
    <script>
        window.TRIP_DATA = <?= json_encode($tracksData) ?>;
        window.TRIP_LANG = {
            distance: "<?= __('distance') ?>",
            duration: "<?= __('duration') ?>",
            avgSpeed: "<?= __('avg_speed') ?>",
            maxSpeed: "<?= __('max_speed') ?>",
            overview: "<?= __('trip_overview') ?>",
            days: "<?= __('days') ?>",
            day: "<?= __('day') ?>"
        };
    </script>
    
    <?php if (count($tracksData) > 1): ?>
        <div class="steps-panel glass-card mt-4 mb-4">
            <h3><?= __('nav_days') ?></h3>
            <ul class="steps-list d-flex flex-row" id="stepsList" style="display: flex; gap: 1rem; flex-wrap: wrap; list-style: none;">
                <!-- Populated by JS -->
            </ul>
        </div>
    <?php endif; ?>

    <div class="stats-panel glass-card mb-4 mt-4">
        <h3><?= __('trip_stats') ?></h3>
        <div id="globalStats" class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
            <!-- Populated by JS -->
        </div>
    </div>

    <div class="map-container glass-card mb-4">
        <div id="map" class="leaflet-map"></div>
    </div>
    
    <div class="chart-panel glass-card mb-4">
        <h3><?= __('speed_profile') ?></h3>
        <div class="chart-wrapper">
            <canvas id="speedChart"></canvas>
        </div>
    </div>
<?php endif; ?>

<?php
$extraJs = <<<JS
<script>
function copyShareUrl() {
    var btn = document.getElementById("copyBtn");
    if (!btn) return;
    var token = btn.getAttribute("data-token");
    var url = window.location.origin + window.location.pathname + '?route=trip&token=' + token;
    
    var showSuccess = function() {
        btn.innerText = "Copied!";
        btn.classList.add("glass-success");
        setTimeout(() => {
            btn.innerText = "Copy Link";
            btn.classList.remove("glass-success");
        }, 2000);
    };

    var fallbackCopy = function(text) {
        var textArea = document.createElement("textarea");
        textArea.value = text;
        textArea.style.top = "0";
        textArea.style.left = "0";
        textArea.style.position = "fixed";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        try {
            document.execCommand('copy');
            showSuccess();
        } catch (err) {
            console.error('Fallback: unable to copy', err);
            alert("Copy failed. Please manually copy this link: " + text);
        }
        document.body.removeChild(textArea);
    };

    if (!navigator.clipboard) {
        fallbackCopy(url);
        return;
    }
    
    navigator.clipboard.writeText(url).then(function() {
        showSuccess();
    }).catch(function(err) {
        fallbackCopy(url);
    });
}

function regenerateToken(tripId) {
    if (!confirm('Are you sure you want to generate a new share link? The old link will immediately stop working.')) {
        return;
    }
    fetch('?route=regenerate_token', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ trip_id: tripId })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('copyBtn').setAttribute('data-token', data.new_token);
            alert('New link generated successfully!');
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(err => alert('Network error'));
}
</script>
<script src="js/map.js"></script>
JS;
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
