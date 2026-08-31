<?php
declare(strict_types=1);
header('Content-Type: text/markdown; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
$file = basename((string) ($_GET['file'] ?? ''));
if (!preg_match('/^(?:\d{2}-[a-z0-9-]+|memo)\.md$/i', $file)) { http_response_code(400); exit; }
$paths = [__DIR__ . '/docs/' . $file, __DIR__ . '/docs/cases/' . $file];
foreach ($paths as $path) if (is_readable($path)) { readfile($path); exit; }
http_response_code(404);
