<?php
require_once("config.php");

session_start();
session_regenerate_id(true);

if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'HEAD') {
  http_response_code(405);
  exit();
}

if (!isset($_SERVER['HTTP_REFERER'])) {
  http_response_code(403);
  exit();
}

$referer = $_SERVER['HTTP_REFERER'] ?? '';
$parsedUrl = parse_url($referer);
$refererHost = $parsedUrl['host'] ?? '';

if (!in_array($refererHost, $validHosts)) {
  http_response_code(403);
  exit();
} else {
  header('Access-Control-Allow-Origin: ' . $refererHost);
}

/*
  ===// Uncomment the following block if you want to enforce HTTPS //===
if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
  http_response_code(495);
  exit();
}
*/

if (!isset($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

header_remove("X-Powered-By");
header_remove("Server");
header('X-CSRF-Token: ' . $_SESSION['csrf_token']);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: HEAD');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');
http_response_code(200);
exit();
?>
