<?php
/**
 * ZenPost Blog Upload Endpoint
 * ─────────────────────────────────────────────────────────────────────────────
 * WICHTIG: Lade diese Datei direkt in dein Blog-Hauptverzeichnis hoch!
 *
 *   RICHTIG:  /zenpostapp/zenpost-upload.php       ← hier hochladen
 *   FALSCH:   /zenpostapp/php/zenpost-upload.php   ← NICHT in Unterordner!
 *
 * Die Posts werden automatisch in posts/ neben diesem Skript gespeichert:
 *   /zenpostapp/posts/mein-post.md
 *
 * Voraussetzungen: PHP 7.4+, Schreibrechte auf dem Server
 * ─────────────────────────────────────────────────────────────────────────────
 */

define('API_KEY', 'DEIN_GEHEIMER_KEY');

// POSTS_DIR: Pfad zum posts/-Ordner.
// Standard: posts/ im selben Verzeichnis wie dieses Skript.
// Nur ändern wenn du eine andere Struktur willst.
define('POSTS_DIR', __DIR__ . '/posts/');
define('MANIFEST_PATH', __DIR__ . '/manifest.json');
define('ASSETS_DIR', __DIR__ . '/_assets/');

// ── CORS ────────────────────────────────────────────────────────────────────
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Api-Key');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ── GET: Manifest oder einzelnen Post zurückgeben (kein Auth nötig) ─────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $slug = trim((string)($_GET['slug'] ?? ''));
    if ($slug !== '') {
        // Optional: Slug nur in sicherem Format erlauben
        if (!preg_match('/^[a-z0-9\-_]+$/', $slug)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid slug']);
            exit;
        }

        $postPath = POSTS_DIR . $slug . '.md';
        if (!file_exists($postPath)) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Post not found']);
            exit;
        }

        $markdown = file_get_contents($postPath);
        if ($markdown === false) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Could not read post']);
            exit;
        }

        $title = $slug;
        $subtitle = '';
        if (file_exists(MANIFEST_PATH)) {
            $manifestRaw = file_get_contents(MANIFEST_PATH);
            $manifest = json_decode((string)$manifestRaw, true);
            if (is_array($manifest) && isset($manifest['posts']) && is_array($manifest['posts'])) {
                foreach ($manifest['posts'] as $entry) {
                    if (is_array($entry) && (($entry['slug'] ?? '') === $slug)) {
                        $title = trim((string)($entry['title'] ?? $title));
                        $subtitle = trim((string)($entry['subtitle'] ?? ''));
                        break;
                    }
                }
            }
        }

        echo json_encode([
            'success' => true,
            'slug' => $slug,
            'title' => $title,
            'subtitle' => $subtitle,
            'markdown' => $markdown,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Default GET: manifest zurückgeben
    if (file_exists(MANIFEST_PATH)) {
        echo file_get_contents(MANIFEST_PATH);
    } else {
        echo json_encode(['site' => [], 'posts' => []]);
    }
    exit;
}

// ── Method check ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// ── Auth ────────────────────────────────────────────────────────────────────
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
if ($apiKey !== API_KEY) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// ── Parse body ──────────────────────────────────────────────────────────────
$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON body']);
    exit;
}

// Optional fields for future integrations (currently informational):
// - thought: short thought/intent string from external apps
// - placeholder: { word, status, focus } visual placeholder hints
// They are accepted and can be stored inside manifest entries by the client.

// ── Image Upload ─────────────────────────────────────────────────────────────
if (isset($body['imageData'])) {
    $imageData = trim((string)($body['imageData'] ?? ''));
    $fileName  = trim((string)($body['fileName']  ?? ''));

    if (!preg_match('/^data:image\/(png|jpe?g|webp|gif);base64,(.+)$/i', $imageData, $im)) {
        http_response_code(422);
        echo json_encode(['error' => 'Invalid image data (only png/jpg/webp/gif)']);
        exit;
    }
    $ext    = strtolower($im[1] === 'jpeg' ? 'jpg' : $im[1]);
    $binary = base64_decode(preg_replace('/\s+/', '', $im[2]), true);
    if ($binary === false) {
        http_response_code(422);
        echo json_encode(['error' => 'Base64 decode failed']);
        exit;
    }
    $safeName = preg_replace('/[^a-zA-Z0-9._-]+/', '-', $fileName);
    $safeName = trim($safeName, '-_.');
    if ($safeName === '') { $safeName = 'cover-' . gmdate('Ymd-His'); }
    if (!preg_match('/\.(png|jpe?g|webp|gif)$/i', $safeName)) { $safeName .= '.' . $ext; }

    if (!is_dir(ASSETS_DIR)) { mkdir(ASSETS_DIR, 0755, true); }
    $targetPath = ASSETS_DIR . $safeName;
    if (file_exists($targetPath)) {
        $safeName   = pathinfo($safeName, PATHINFO_FILENAME) . '-' . gmdate('His') . '.' . $ext;
        $targetPath = ASSETS_DIR . $safeName;
    }
    if (@file_put_contents($targetPath, $binary) === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Could not write image file']);
        exit;
    }
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? '';
    $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
    $url = ($host !== '' ? $scheme . '://' . $host : '') . $scriptDir . '/_assets/' . rawurlencode($safeName);
    echo json_encode(['success' => true, 'url' => $url, 'fileName' => $safeName]);
    exit;
}

// ── Manifest-only update (e.g. post deletion) ───────────────────────────────
if (!isset($body['filename']) && !isset($body['content']) && isset($body['manifest'])) {
    file_put_contents(MANIFEST_PATH, json_encode($body['manifest'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo json_encode(['success' => true]);
    exit;
}

$filename = $body['filename'] ?? null;
$content  = $body['content']  ?? null;

if (!$filename || !$content) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing filename or content']);
    exit;
}

// ── Sanitize filename ───────────────────────────────────────────────────────
$filename = basename($filename);
if (!preg_match('/^[a-z0-9\-_]+\.md$/', $filename)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid filename (only lowercase letters, numbers, hyphens allowed)']);
    exit;
}

// ── Save post ───────────────────────────────────────────────────────────────
if (!is_dir(POSTS_DIR)) {
    mkdir(POSTS_DIR, 0755, true);
}

if (file_put_contents(POSTS_DIR . $filename, $content) === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not write file (check server permissions)']);
    exit;
}

// ── Update manifest.json (optional) ─────────────────────────────────────────
if (isset($body['manifest'])) {
    file_put_contents(MANIFEST_PATH, json_encode($body['manifest'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

echo json_encode(['success' => true, 'filename' => $filename]);
