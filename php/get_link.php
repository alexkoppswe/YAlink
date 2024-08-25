<?php
require_once("config.php");
require("functions.php");

session_start();
session_regenerate_id(false);

header("Connection: keep-alive");
header("Keep-Alive: timeout=3600, max=99");
header("Content-Type: application/json");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id']) && isset($_GET['token'])) {
  if (empty($_GET['id']) || empty($_GET['token'])) {
    sendError("You are missing something there pal.", 403);
    exit;
  } else {
    $identifier = sanitize_id($_GET['id']);
    $token = sanitize_id($_GET['token']);

    if(empty($token) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
      sendError("Invalid token", 403);
      exit;
    }

    if(!empty($identifier)) {
      $result = getLinkInfo($identifier);

      if($result) {
        echo json_encode($result);
        http_response_code(200);
        exit;
      }
      exit;
    } else {
      sendError("Link not found", 404);
      exit;
    }
  }
}
?>