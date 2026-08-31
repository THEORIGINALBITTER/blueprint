<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }
header("Content-Type: application/json; charset=utf-8");

$configPath = __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
if (!file_exists($configPath)) { http_response_code(500); echo json_encode(["success"=>false,"message"=>"config.php fehlt."]); exit; }
$config = require $configPath;

$conn = new mysqli((string)$config['db_host'], (string)$config['db_user'], (string)$config['db_pass'], (string)$config['db_name']);
if ($conn->connect_error) { http_response_code(500); echo json_encode(["success"=>false,"message"=>"DB Fehler: ".$conn->connect_error]); exit; }
$conn->set_charset("utf8mb4");

$apiKeyEnabled = !empty($config['api_key_enabled']);
$expectedApiKey = trim((string)($config['api_key'] ?? ''));
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = null;
if (preg_match('/Bearer\s+(.+)/i', $authHeader, $m)) { $token = trim($m[1]); }
if ($apiKeyEnabled && ($expectedApiKey === '' || $token !== $expectedApiKey)) {
  http_response_code(401); echo json_encode(["success"=>false,"message"=>"Unauthorized"]); exit;
}

$slug = trim($_GET['slug'] ?? '');
if ($slug === '') { http_response_code(400); echo json_encode(["success"=>false,"message"=>"Kein Slug uebergeben."]); exit; }

$stmt = $conn->prepare("DELETE FROM Articles WHERE Slug = ?");
$stmt->bind_param("s", $slug);
if ($stmt->execute()) {
  $deleted = $stmt->affected_rows;
  echo json_encode(["success"=>true,"deleted"=>$deleted]);
} else {
  http_response_code(500); echo json_encode(["success"=>false,"message"=>"Datenbankfehler: ".$stmt->error]);
}
$stmt->close();
$conn->close();
