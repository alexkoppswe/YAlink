<?php
/* ===// Link PHP File Structure //===
  1. Required files and session setup
  2. GET requests
  3. POST requests
=================================== */

require_once("config.php");
require("functions.php");

session_start();
session_regenerate_id(true);

header("Content-Type: application/json");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// GET requests
if($_SERVER['REQUEST_METHOD'] === 'GET') {
  if (!isset($_GET['id'])) {
    sendError("You are missing something there pal.", 400);
    exit;
  }

  $identifier = sanitize_id($_GET['id'] ?? false);
  
  if(empty($identifier)) {
    sendError("Link not found", 404);
    exit;
  }

  $links = getLinkInfo($identifier);

  if (!$links || empty($links)) {
    sendError("Link not found", 404);
    exit;
  }

  $links['time_limit'] = calculateTimeLeft($links);

  if (!$links['password'] || (isset($_SESSION['unlocked']) && $_SESSION['unlocked'])) {
    $_SESSION['visited_links'] = $_SESSION['visited_links'] ?? [];

    if(!in_array($identifier, $_SESSION['visited_links'])) {
      $_SESSION['visited_links'][] = $identifier;
      decrementUses($identifier);
      
      if($links['uses'] > 0) {
        $links['uses']--;
      }
    }

    echo json_encode($links);
    http_response_code(200);
    exit;
  } elseif ($links['password'] && !isset($_SESSION['unlocked'])) {
    if (!isset($_SESSION['csrf_token'])) {
      $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    echo json_encode(['password' => $links['password']]);
    http_response_code(200);
    exit;
  } else {
    sendError("Unknown error", 500);
    exit;
  }
}

// POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if(empty($_POST['password']) || empty($_POST['id']) || empty($_POST['csrf_token'])) {
    sendError("You are missing something there pal.", 400);
    exit;
  }

  $password = strip_tags($_POST['password']);
  $id = sanitize_id($_POST['id']);
  $csrf = sanitize_id($_POST['csrf_token']);

  if (strlen($password) < 4) {
    sendError("Password must be at least 4 characters long", 400);
    exit;
  }

  if (!hash_equals($_SESSION['csrf_token'], $csrf)) {
    sendError("Invalid token", 403);
    exit;
  }

  if (isset($_SESSION['lockout_time']) && $_SESSION['lockout_time'] > time()) {
    sendError("Too many failed attempts. Try again later", 429);
    exit;
  }

  $result = getLinkData($id);

  if (!$result) {
    sendError("Link not found", 404);
    exit;
  }

  $passwordHash = $result['password'];

  // Password verification
  if (verifyPassword($password, $passwordHash)) {
    $result['unlocked'] = true;
    $_SESSION['password_attempts'] = 0;
    $_SESSION['lockout_time'] = 0;
    $_SESSION['unlocked'] = true;

    $filteredResult = array_intersect_key($result, array_flip(['url', 'time_limit', 'unlocked', 'uses']));

    if (!isset($_SESSION['visited_links']) || !in_array($id, $_SESSION['visited_links'])) {
      if($result['uses'] > 0) {
        $filteredResult['uses']--;
      }
    }

    echo json_encode($filteredResult);
    http_response_code(200);
    exit;
  } else {
    $_SESSION['password_attempts'] = ($_SESSION['password_attempts'] ?? 0) + 1;
    $_SESSION['lockout_time'] = ($_SESSION['lockout_time'] ?? 0);

    if ($_SESSION['lockout_time'] > time()) {
      sendError("Too many failed attempts. Try again later", 429);
      exit;
    }

    if ($_SESSION['password_attempts'] >= 3) {
      $_SESSION['lockout_time'] = time() + 900; // Lockout for 15 minutes
      sendError("Too many failed attempts. Try again later", 429);
      exit;
    }

    sendError("Incorrect password", 401);
    exit;
  }
}
?>