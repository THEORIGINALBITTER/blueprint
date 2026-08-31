<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');

$file = __DIR__ . '/private/content.json';
if (!is_readable($file)) {
    http_response_code(500);
    echo '{"error":"Blueprint content is unavailable."}';
    exit;
}

readfile($file);
