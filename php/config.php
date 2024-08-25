<?php
/* ===// PHP Configuration //=== */

// Error Reporting
ini_set('error_log', __DIR__ . DIRECTORY_SEPARATOR . '../logs/php-error.log');

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

// Security & Session
ini_set('expose_php', false);
ini_set('allow_url_fopen', false);
ini_set('suhosin.protector.enabled', true);
ini_set('session.use_only_cookies', true);
ini_set('session.cookie_httponly', true);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', 3600);
ini_set('session.id_length', 32);

// Set secure headers
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: no-referrer');
header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self'; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self';");

// Configuration
date_default_timezone_set('UTC'); // Set the default timezone to UTC
$link_identifier = bin2hex(random_bytes(8)); // The identifier for the link generation
$databaseName = "../db/database.db"; // Database name
$validHosts = ['localhost']; // Valid hosts

// Database Connection
try {
  $db = new PDO('sqlite:'.$databaseName);
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  $db->exec("
    CREATE TABLE IF NOT EXISTS links (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      url TEXT,
      password TEXT,
      timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
      time_limit TEXT,
      uses INTEGER,
      identifier TEXT,
      deleted INTEGER DEFAULT 0
    )
  ");

  // Optimize and configure the database
  $db->exec("PRAGMA synchronous = NORMAL");
  $db->exec("PRAGMA journal_mode = WAL");
  $db->exec("PRAGMA foreign_keys = ON");
} catch (PDOException $e) {
  die('Error connecting to database: ' . $e->getMessage());
}
?>
