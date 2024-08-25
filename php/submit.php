<?php
/* ===// PHP File Structure //===
  1. Required files and session start
  2. Request validation
  3. Data filtering and validation
  4. Database insertion
============================== */

require_once("config.php");
require("functions.php");
session_start();

if($_SERVER['REQUEST_METHOD'] !== 'POST') {
  sendError("Invalid request method", 400);
  return;
}

if(!isset($_SESSION['csrf_token']) || !isset($_SERVER['HTTP_X_CSRF_TOKEN']) || !hash_equals($_SESSION['csrf_token'],$_SERVER['HTTP_X_CSRF_TOKEN'])) {
  sendError("Invalid token", 403);
  return;
}

if (!isset($_POST['url'])) {
  sendError("No URL provided", 400);
  return;
}

if (!isset($_POST['time_limit']) || empty($_POST['time_limit'])) {
  sendError("No time limit provided", 400);
  return;
}

if (!isset($_POST['uses'])) {
  sendError("No uses provided", 400);
  return;
}

$url = filter_var($_POST['url'], FILTER_SANITIZE_URL);
$password = strip_tags($_POST['password']);
$time_limit = strip_tags($_POST['time_limit']);
$uses = filter_var($_POST['uses'], FILTER_SANITIZE_NUMBER_INT);

if (!$url) {
  sendError("Invalid URL", 400);
  return;
}

if (!isUrlCompatible($url)) {
  sendError("Invalid URL format", 400);
  return;
}

if ($uses === false || $uses < 0 || $uses > 21) {
  sendError("Invalid number of uses", 400);
  return;
}

if ($uses == 0) {
  $uses = -1;
}

if (!empty($password) && mb_strlen($password) < 4) {
  sendError("Password must be at least 4 characters long", 400);
  return;
}

if (!empty($_POST['password'])) {
  $hashed_password = password_hash($password, PASSWORD_DEFAULT);
} else {
  $hashed_password = null;
}

if(!$time_limit || empty($time_limit)) {
  sendError("Ahghh.. You forgot to specify a time limit", 400);
  return;
}

$allowed_time_limits = ['none', '10m', '1h', '1d', '7', '1M', '1Y'];

if (in_array($time_limit, $allowed_time_limits)) {
  if ($time_limit !== 'none') {
    $currentDateTime = new DateTime();
    
    switch ($time_limit) {
      case '10m':
        $interval = 'PT10M';
        break;
      case '1h':
        $interval = 'PT1H';
        break;
      case '1d':
        $interval = 'P1D';
        break;
      case '7':
        $interval = 'P7D';
        break;
      case '1M':
        $interval = 'P1M';
        break;
      case '1Y':
        $interval = 'P1Y';
        break;
      default:
        $interval = 'P13Y';
        break;
    }
    
    $intervalObj = new DateInterval($interval);
    $currentDateTime->add($intervalObj);
    
    $expiration_time = $currentDateTime->format('Y-m-d H:i');
  } else {
    $expiration_time = null;
  }
} else {
  sendError("Wrong date format", 400);
  return;
}

// Database insertion
try {
  $currentDateTime = date('Y-m-d H:i');

  $stmt = $db->prepare("INSERT INTO links (url, password, timestamp, time_limit, uses, identifier, deleted) VALUES (:url, :password, :timestamp, :expiration_time, :uses, :identifier, 0)");
  $stmt->bindParam(':url', $url);
  $stmt->bindParam(':password', $hashed_password);
  $stmt->bindParam(':timestamp', $currentDateTime);
  $stmt->bindParam(':expiration_time', $expiration_time, PDO::PARAM_STR);
  $stmt->bindParam(':uses', $uses, PDO::PARAM_INT|PDO::PARAM_STR);
  $stmt->bindParam(':identifier', $link_identifier);
  if ($stmt->execute()) {
    $landingID = json_encode(['identifier' => $link_identifier]);
    echo $landingID;
  } else {
    sendError("Error: " . $stmt->errorInfo()[2], 500);
  }
} catch (PDOException $e) {
  sendError('An error occurred while processing your request. Please try again later.', 500);
  error_log('Database error: ' . $e->getMessage());
  return;
}
?>