-- Database schema for SillageGPX (Compatible SQLite / MySQL)

CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(255) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS trips (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    title VARCHAR(255) NOT NULL,
    start_date DATE,
    end_date DATE,
    boat_name VARCHAR(255),
    comment TEXT,
    visibility VARCHAR(20) DEFAULT 'private', -- 'public', 'unlisted', 'private'
    unlisted_token VARCHAR(255) UNIQUE,
    views_count INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS trip_steps (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    trip_id INTEGER NOT NULL,
    title VARCHAR(255) NOT NULL,
    order_index INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS trip_links (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    trip_id INTEGER NOT NULL,
    url VARCHAR(512) NOT NULL,
    label VARCHAR(255),
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS gpx_tracks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    trip_step_id INTEGER NOT NULL,
    file_path VARCHAR(512) NOT NULL,
    start_time DATETIME,
    end_time DATETIME,
    distance_meters REAL,
    duration_seconds INTEGER,
    avg_speed_knots REAL,
    max_speed_knots REAL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trip_step_id) REFERENCES trip_steps(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS login_attempts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ip_address VARCHAR(45) NOT NULL,
    attempt_time DATETIME DEFAULT CURRENT_TIMESTAMP
);
