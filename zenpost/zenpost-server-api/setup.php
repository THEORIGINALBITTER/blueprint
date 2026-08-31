<?php
header('Content-Type: text/html; charset=utf-8');
$baseDir = __DIR__;
$configPath = $baseDir . DIRECTORY_SEPARATOR . 'config.php';
$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $dbHost = trim($_POST['db_host'] ?? '');
  $dbName = trim($_POST['db_name'] ?? '');
  $dbUser = trim($_POST['db_user'] ?? '');
  $dbPass = trim($_POST['db_pass'] ?? '');
  $apiKeyEnabled = isset($_POST['api_key_enabled']) && $_POST['api_key_enabled'] === '1';
  $apiKey = trim($_POST['api_key'] ?? '');
  if ($dbHost === '' || $dbName === '' || $dbUser === '') { $error = 'Bitte DB Host, DB Name und DB User ausfuellen.'; }
  else {
    $configContent = "<?php\nreturn [\n"
      . "    'db_host' => " . var_export($dbHost, true) . ",\n"
      . "    'db_name' => " . var_export($dbName, true) . ",\n"
      . "    'db_user' => " . var_export($dbUser, true) . ",\n"
      . "    'db_pass' => " . var_export($dbPass, true) . ",\n"
      . "    'api_key_enabled' => " . ($apiKeyEnabled ? 'true' : 'false') . ",\n"
      . "    'api_key' => " . var_export($apiKey, true) . ",\n"
      . "];\n";
    if (@file_put_contents($configPath, $configContent) === false) { $error = 'config.php konnte nicht geschrieben werden.'; }
    else { $message = 'Setup gespeichert.'; }
  }
}
?><!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>ZenPost API Setup</title></head>
<body style="font-family:monospace;background:#111;color:#eee;padding:24px;">
<div style="max-width:720px;margin:0 auto;background:#1b1b1b;border:1px solid #444;border-radius:10px;padding:18px;">
<h1 style="font-size:18px;color:#d8be92;">ZenPost API Setup</h1>
<?php if ($message): ?><div style="padding:8px;background:#14321a;border:1px solid #2f7a40;"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if ($error): ?><div style="padding:8px;background:#3a1616;border:1px solid #8f2c2c;"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<form method="post">
<label>DB Host</label><br><input type="text" name="db_host" style="width:100%;padding:8px;"><br>
<label>DB Name</label><br><input type="text" name="db_name" style="width:100%;padding:8px;"><br>
<label>DB User</label><br><input type="text" name="db_user" style="width:100%;padding:8px;"><br>
<label>DB Passwort</label><br><input type="password" name="db_pass" style="width:100%;padding:8px;"><br>
<label><input type="checkbox" name="api_key_enabled" value="1"> API-Key aktivieren (optional)</label><br>
<label>API Key</label><br><input type="text" name="api_key" style="width:100%;padding:8px;"><br><br>
<button type="submit">Setup speichern</button>
</form></div></body></html>
