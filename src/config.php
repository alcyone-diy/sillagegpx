<?php
/**
 * Global application configuration
 */

// Define absolute project paths
define('BASE_PATH', dirname(__DIR__));
define('SRC_PATH', BASE_PATH . '/src');
define('PUBLIC_PATH', BASE_PATH . '/public');

// Application Data (outside public web root for security)
define('DATA_PATH', BASE_PATH . '/data');
define('GPX_PATH', DATA_PATH . '/trips');

// Database
define('DB_PATH', BASE_PATH . '/db/journal.sqlite');
define('SCHEMA_PATH', BASE_PATH . '/db/schema.sql');

// GPX Parsing Settings (Intervals in seconds)
define('STATS_CALC_INTERVAL', 600); // 10 minutes for speed/distance calculations
define('MAP_POINT_INTERVAL', 60);   // 1 minute for map rendering points

// Base URL (if needed to force absolute links in templates, 
// although ideally .htaccess makes relative paths like 'css/style.css' sufficient).
// If the site is at the root of the domain, leave empty or '/'
// If the site is in a subfolder not managed by a VirtualHost, define e.g.: '/journaldebord'
define('BASE_URL', '/gpx'); 
