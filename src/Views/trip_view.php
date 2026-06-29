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
            <span>👁️ <?= $trip->views_count ?> views</span>
        </div>
        
        <?php if ($trip->comment): ?>
            <div class="trip-comment">
                <p><?= nl2br(htmlspecialchars($trip->comment)) ?></p>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $trip->user_id): ?>
        <div class="trip-owner-actions">
            <div class="d-flex" style="gap: 1rem; align-items: center; margin-bottom: 0.5rem;">
                <span class="badge badge-<?= htmlspecialchars($trip->visibility) ?>"><?= htmlspecialchars($trip->visibility) ?></span>
                <a href="?route=edit_trip&id=<?= $trip->id ?>" class="btn btn-outline btn-sm">Edit Trip</a>
            </div>
            <?php if ($trip->visibility === 'unlisted'): ?>
                <div class="share-link-box">
                    <button class="btn btn-outline btn-sm" id="copyBtn" data-token="<?= htmlspecialchars($trip->unlisted_token) ?>" onclick="copyShareUrl()">Copy Link</button>
                    <button class="btn btn-outline btn-sm" onclick="regenerateToken(<?= $trip->id ?>)" title="Generate new link">Regenerate</button>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>



<?php if (empty($tracksData)): ?>
    <div class="empty-state glass-card mt-4">
        <p>No GPX tracks available for this trip yet.</p>
    </div>
<?php else: ?>
    <!-- Pass tracks data to JS securely -->
    <script>
        window.TRIP_DATA = <?= json_encode($tracksData) ?>;
    </script>
    
    <?php if (count($tracksData) > 1): ?>
        <div class="steps-panel glass-card mt-4 mb-4">
            <h3>Navigation Days</h3>
            <ul class="steps-list d-flex flex-row" id="stepsList" style="display: flex; gap: 1rem; flex-wrap: wrap; list-style: none;">
                <!-- Populated by JS -->
            </ul>
        </div>
    <?php endif; ?>

    <div class="stats-panel glass-card mb-4 mt-4">
        <h3>Trip Statistics</h3>
        <div id="globalStats" class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
            <!-- Populated by JS -->
        </div>
    </div>

    <div class="map-container glass-card mb-4">
        <div id="map" class="leaflet-map"></div>
    </div>
    
    <div class="chart-panel glass-card mb-4">
        <h3>Speed Profile</h3>
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
