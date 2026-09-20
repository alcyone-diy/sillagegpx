<?php
// recalculate_tracks.php
// CLI script to recalculate all GPX tracks statistics with the current STATS_CALC_INTERVAL

if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.\n");
}

require_once __DIR__ . '/src/config.php';

// Basic autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative_class = substr($class, $len);
    $file = SRC_PATH . '/' . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

$intervalMinutes = round(STATS_CALC_INTERVAL / 60, 1);
echo "====================================================\n";
echo "SillageGPX - Recalcul de toutes les traces GPX\n";
echo "Intervalle de calcul configuré : " . STATS_CALC_INTERVAL . "s ({$intervalMinutes} min)\n";
echo "====================================================\n\n";

$startTime = microtime(true);

$result = \App\Utils\TrackRecalculator::recalculateAll(function ($info) {
    $percent = round(($info['index'] / $info['total']) * 100);
    $statusIcon = ($info['status'] === 'ok') ? "✅" : "❌";
    echo sprintf("[%3d%%] %s [%d/%d] %s\n", $percent, $statusIcon, $info['index'], $info['total'], $info['message']);
});

$duration = round(microtime(true) - $startTime, 2);

echo "\n====================================================\n";
echo "Terminé en {$duration} s.\n";
echo "Total de traces : {$result['total']}\n";
echo "Succès          : {$result['success']}\n";
echo "Échecs          : {$result['failed']}\n";
echo "====================================================\n";

if (!empty($result['errors'])) {
    echo "\nDétail des erreurs :\n";
    foreach ($result['errors'] as $err) {
        echo " - [Trace #{$err['track_id']}] {$err['error']}\n";
    }
}
