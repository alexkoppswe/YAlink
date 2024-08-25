<?php
/* ===// PHP Functions File Structure //===
  1. Include and Initialization
  2. Sanitization Functions
  3. Error Handling Functions
  4. Link Data Functions
  5. Other Functions
======================================== */

require_once("config.php");

function sanitize($input) {
  if(!empty($input)) {
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
  }
}

// Sanitize $_GET['id']
function sanitize_id($id) {
  if(isset($id) && !empty($id)) {
    if($id === "1337h4x0r") {
      sendError("Easter Egg found!", 418);
      exit;
    }

    $sanitized_id = preg_replace("/[^0-9a-fA-F]/", "", $id); // Remove any characters that are not 0-9, a-f, or A-F
    return $sanitized_id; 
  }
  return false;
}

function sendError($message, $httpCode = 400) {
  http_response_code($httpCode);
  header("Content-Type: application/json");
  echo json_encode(['error' => $message]);
  exit;
}

// Verify URL
function isUrlCompatible($url) {
  if(empty($url)) {
    return false;
  }
  
  if (filter_var($url, FILTER_VALIDATE_URL) === false) {
    return false;
  }

  $host = parse_url($url, PHP_URL_HOST);

  if (strlen($host) < 3 || strlen($host) > 63) {
    return false;
  }

  if (!preg_match('/^[a-zA-Z0-9-]+(\.[a-zA-Z]{2,})+$/', $host)) { // Check if the host contains only allowed characters and at least one dot followed by at least two letters
    return false;
  }

  return true;
}

// Verify the password
function verifyPassword($postPassword, $linkPassword) {
  if (strlen($postPassword) < 4 || !$postPassword) {
    return false;
  }
  if (strlen($linkPassword) < 40 || !$linkPassword) {
    return false;
  }

  if (password_verify($postPassword, $linkPassword)) {
    $_SESSION['unlocked'] = true;
    return true;
  } else {
    return false;
  }
}

// Get all link data from db
function getLinkData($identifier) {
  global $db;

  if (empty($identifier)) {
    return false;
  }

  try {
    $stmt = $db->prepare("SELECT * FROM links WHERE identifier = :identifier AND deleted = 0");
    $stmt->bindParam(':identifier', $identifier, PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
      header("Cache-Control: no-store, no-cache, must-revalidate");
      header("Pragma: no-cache");
      header("Expires: 0");
      header("Content-Encoding: gzip");
      header("Vary: Accept-Encoding");
      ob_start();
      
      return $result;
    } else {
      return false;
    }
  } catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    return false;
  }
}

// Filter all link data from db
function getLinkInfo($identifier) {
  if($identifier) {
    $result = getLinkData($identifier);

    if ($result) {
      if (!filter_var($result['url'], FILTER_VALIDATE_URL)) {
        sendError("Link not found", 404);
        return false;
      }

      $result['password'] = isset($result['password']) && !empty($result['password']);

      if ($result['time_limit'] !== null && $result['time_limit'] < date('Y-m-d H:i')) {
        markForDelete($identifier);
        sendError("Link has expired", 404);
        return false;
      }

      if($result['uses'] === 0) {
        sendError("Link not found", 404);
        return false;
      }

      $filteredResult = array_intersect_key($result, array_flip(['url', 'time_limit', 'password', 'uses']));

      return $filteredResult;
    } else {
      sendError("Link not found", 404);
      return false;
    }
  }
}

// Count down URL visits
function decrementUses($identifier) {
  global $db;

  if (!isset($identifier) || empty($identifier)) {
    return false;
  }

  try {
    $stmt = $db->prepare("SELECT uses FROM links WHERE identifier = :identifier");
    $stmt->bindParam(':identifier', $identifier, PDO::PARAM_STR);
    $stmt->execute();
    $link = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($link['uses'] > 0) {
      // Decrement the uses field
      $stmt = $db->prepare("UPDATE links SET uses = uses - 1 WHERE identifier = :identifier AND uses > 0");
      $stmt->bindParam(':identifier', $identifier, PDO::PARAM_STR);
      $stmt->execute();
    } elseif ($link['uses'] === 0) {
      // Delete the link
      $stmt = $db->prepare("DELETE FROM links WHERE identifier = :identifier");
      $stmt->bindParam(':identifier', $identifier, PDO::PARAM_STR);
      $stmt->execute();
    }
    // If uses is -1, do nothing (unlimited uses)
  } catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
  }
}

function markForDelete($id) {
  global $db;
  try {
    $currentDateTime = new DateTime();
    $currentDateTimeStr = $currentDateTime->format('Y-m-d H:i');

    $stmt = $db->prepare("UPDATE links SET deleted = 1 WHERE time_limit IS NOT NULL AND time_limit <= :currentDateTime AND identifier = :identifier");
    $stmt->bindParam(':currentDateTime', $currentDateTimeStr, PDO::PARAM_STR);
    $stmt->bindParam(':identifier', $id, PDO::PARAM_STR);
    $stmt->execute();

  } catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
  }
}

function deleteLink($id) {
  global $db;
  try {
    $stmt = $db->prepare("SELECT * FROM links WHERE identifier = :identifier");
    $stmt->bindParam(':identifier', $id, PDO::PARAM_STR);
    $stmt->execute();
    $link = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($link !== false) {
      $stmt = $db->prepare("DELETE FROM links WHERE identifier = :identifier AND WHERE deleted = 1");
      $stmt->bindParam(':identifier', $id, PDO::PARAM_STR);
      $stmt->execute();
    } else {
      return false;
    }
  } catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
  }
}

// Calculate the time left for the link and display it
function calculateTimeLeft($link) {
  if ($link && $link['time_limit'] !== null) {
    $timeLeft = strtotime($link['time_limit']) - time();
    
    $linkDate = new DateTime($link['time_limit']);
    $today = new DateTime('now');
    $dateDiff = $today->diff($linkDate);
    $dateleft = $dateDiff->format('%a days, ');

    if($dateleft == "0 days, ") {
      $dateleft = "Today, ";
    }
    
    $value = $timeLeft > 0 ? ($dateleft . gmdate("H:i", $timeLeft)) : "Expired";
  } else {
    $value = "Forever";
  }

  return $value;
}
?>