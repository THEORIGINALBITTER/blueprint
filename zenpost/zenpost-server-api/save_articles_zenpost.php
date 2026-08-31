<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }
header("Content-Type: application/json; charset=utf-8");

$configPath = __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
if (!file_exists($configPath)) { http_response_code(500); echo json_encode(["success"=>false,"message"=>"config.php fehlt. setup.php ausfuehren."]); exit; }
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

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) { http_response_code(400); echo json_encode(["success"=>false,"message"=>"Ungueltiges JSON"]); exit; }
$slug = trim($input['slug'] ?? '');
$title = trim($input['title'] ?? '');
$subtitle = trim($input['subtitle'] ?? '');
$publishDate = trim($input['date'] ?? ($input['publishDate'] ?? date('Y-m-d')));
$imageUrl = trim($input['image'] ?? ($input['imageUrl'] ?? ''));
if ($slug === '' || $title === '') { http_response_code(422); echo json_encode(["success"=>false,"message"=>"Slug und Title sind erforderlich."]); exit; }

if (isset($input['blocks']) && is_array($input['blocks'])) {
  $contentJson = json_encode(["blocks"=>$input['blocks']], JSON_UNESCAPED_UNICODE);
} else {
  $contentJson = json_encode(["format"=>"markdown","content"=>trim($input['content'] ?? '')], JSON_UNESCAPED_UNICODE);
}

$stmtCheck = $conn->prepare("SELECT Id FROM Articles WHERE Slug = ?");
$stmtCheck->bind_param("s", $slug);
$stmtCheck->execute();
$resultCheck = $stmtCheck->get_result();

if ($resultCheck && $resultCheck->num_rows > 0) {
  $stmt = $conn->prepare("UPDATE Articles SET Title=?, Subtitle=?, PublishDate=?, ImageUrl=?, Content=?, UpdatedAt=CURRENT_TIMESTAMP WHERE Slug=?");
  $stmt->bind_param("ssssss", $title, $subtitle, $publishDate, $imageUrl, $contentJson, $slug);
  $ok = $stmt->execute();
  if ($ok) { echo json_encode(["success"=>true,"mode"=>"update","message"=>"Artikel aktualisiert."]); }
  else { http_response_code(500); echo json_encode(["success"=>false,"message"=>"Update fehlgeschlagen: ".$stmt->error]); }
} else {
  $stmt = $conn->prepare("INSERT INTO Articles (Slug, Title, Subtitle, PublishDate, ImageUrl, Content) VALUES (?, ?, ?, ?, ?, ?)");
  $stmt->bind_param("ssssss", $slug, $title, $subtitle, $publishDate, $imageUrl, $contentJson);
  $ok = $stmt->execute();
  if ($ok) { echo json_encode(["success"=>true,"mode"=>"insert","message"=>"Artikel gespeichert."]); }
  else { http_response_code(500); echo json_encode(["success"=>false,"message"=>"Insert fehlgeschlagen: ".$stmt->error]); }
}
$stmt->close();
$stmtCheck->close();
$conn->close();
