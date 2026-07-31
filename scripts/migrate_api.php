<?php
// scripts/migrate_api.php
$dbPath = __DIR__ . '/../db/journal.sqlite';
$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "Démarrage de la migration API...\n";

// 1. Création de la table api_tokens
$sql_tokens = "
CREATE TABLE IF NOT EXISTS api_tokens (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    device_name VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);";
$db->exec($sql_tokens);
echo "Table api_tokens créée ou déjà existante.\n";

// 2. Modification de la table gpx_tracks pour ajouter file_hash et track_uuid
$columns = $db->query("PRAGMA table_info(gpx_tracks)")->fetchAll(PDO::FETCH_ASSOC);
$columnNames = array_column($columns, 'name');

if (!in_array('file_hash', $columnNames)) {
    $db->exec("ALTER TABLE gpx_tracks ADD COLUMN file_hash VARCHAR(64)");
    $db->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_gpx_tracks_file_hash ON gpx_tracks(file_hash)");
    echo "Colonne file_hash ajoutée avec succès.\n";
} else {
    echo "La colonne file_hash existe déjà.\n";
}

if (!in_array('track_uuid', $columnNames)) {
    $db->exec("ALTER TABLE gpx_tracks ADD COLUMN track_uuid VARCHAR(36)");
    $db->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_gpx_tracks_track_uuid ON gpx_tracks(track_uuid)");
    echo "Colonne track_uuid ajoutée avec succès.\n";
} else {
    echo "La colonne track_uuid existe déjà.\n";
}

echo "Migration terminée avec succès !\n";
