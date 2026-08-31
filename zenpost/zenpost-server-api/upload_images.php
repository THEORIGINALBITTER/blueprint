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

$imageData = trim((string)($input['imageData'] ?? ''));
$fileName = trim((string)($input['fileName'] ?? ''));
if ($imageData === '' || strpos($imageData, 'data:image/') !== 0) {
  http_response_code(422); echo json_encode(["success"=>false,"message"=>"imageData (data:image) fehlt."]); exit;
}

if (!preg_match('/^data:image\/(png|jpe?g|webp|gif);base64,(.+)$/i', $imageData, $m)) {
  http_response_code(422); echo json_encode(["success"=>false,"message"=>"Nur png/jpg/webp/gif base64 erlaubt."]); exit;
}

$ext = strtolower($m[1] === 'jpeg' ? 'jpg' : $m[1]);
$base64 = preg_replace('/\s+/', '', $m[2]);
$binary = base64_decode($base64, true);
if ($binary === false) {
  http_response_code(422); echo json_encode(["success"=>false,"message"=>"Base64 Dekodierung fehlgeschlagen."]); exit;
}

$safeName = preg_replace('/[^a-zA-Z0-9._-]+/', '-', $fileName);
$safeName = trim($safeName, '-_.');
if ($safeName === '') { $safeName = 'zenpost-image-' . gmdate('Ymd-His'); }
$existingExt = strtolower((string)pathinfo($safeName, PATHINFO_EXTENSION));
$allowedExt = ['png', 'jpg', 'jpeg', 'webp', 'gif'];
if ($existingExt === '' || !in_array($existingExt, $allowedExt, true)) {
  $safeName = preg_replace('/\.[^.]+$/', '', $safeName);
  $safeName .= '.' . $ext;
}

$webRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), DIRECTORY_SEPARATOR);
$targetDir = '';
if ($webRoot !== '') {
  $targetDir = $webRoot . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'zenpoststudio';
}
if ($targetDir === '') {
  $targetDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'zenpoststudio';
}
if (!is_dir($targetDir)) {
  @mkdir($targetDir, 0775, true);
}
if (!is_dir($targetDir) || !is_writable($targetDir)) {
  http_response_code(500); echo json_encode(["success"=>false,"message"=>"Upload-Verzeichnis nicht beschreibbar: ".$targetDir,"documentRoot"=>$webRoot]); exit;
}

$targetPath = $targetDir . DIRECTORY_SEPARATOR . $safeName;
if (file_exists($targetPath)) {
  $safeName = pathinfo($safeName, PATHINFO_FILENAME) . '-' . gmdate('His') . '.' . $ext;
  $targetPath = $targetDir . DIRECTORY_SEPARATOR . $safeName;
}

if (@file_put_contents($targetPath, $binary) === false) {
  http_response_code(500); echo json_encode(["success"=>false,"message"=>"Datei konnte nicht geschrieben werden."]); exit;
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? '';
$publicBase = trim((string)($config['image_public_base'] ?? ''));
if ($publicBase === '') {
  $publicBase = ($host !== '') ? ($scheme . '://' . $host . '/images/zenpoststudio') : '/images/zenpoststudio';
}
$publicBase = rtrim($publicBase, '/');
$url = $publicBase . '/' . rawurlencode($safeName);

echo json_encode([
  "success" => true,
  "fileName" => $safeName,
  "path" => $targetPath,
  "url" => $url,
  "targetDir" => $targetDir,
  "documentRoot" => $webRoot
]);
