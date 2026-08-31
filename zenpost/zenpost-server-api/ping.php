<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
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
echo json_encode(["success"=>true,"message"=>"pong","time"=>gmdate('c')]);
