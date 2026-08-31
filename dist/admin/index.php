<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';

session_name('blueprint_admin');
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict',
    'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);

$contentFile = dirname(__DIR__) . '/private/content.json';
$defaultPassword = 'CHANGE-THIS-TO-A-LONG-UNIQUE-PASSWORD';
$configured = defined('BLUEPRINT_ADMIN_PASSWORD') && BLUEPRINT_ADMIN_PASSWORD !== $defaultPassword;

function csrf(): string {
    if (empty($_SESSION['blueprint_csrf'])) $_SESSION['blueprint_csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['blueprint_csrf'];
}
function respond(array $payload, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'login') {
    $password = (string) ($_POST['password'] ?? '');
    if ($configured && hash_equals(BLUEPRINT_ADMIN_PASSWORD, $password)) {
        session_regenerate_id(true);
        $_SESSION['blueprint_authenticated'] = true;
        header('Location: ./'); exit;
    }
    $_SESSION['blueprint_login_error'] = true;
    header('Location: ./'); exit;
}

if (isset($_GET['logout'])) {
    $_SESSION = []; session_destroy(); header('Location: ./'); exit;
}

$authenticated = !empty($_SESSION['blueprint_authenticated']);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'save') {
    if (!$authenticated) respond(['error' => 'Nicht angemeldet.'], 401);
    $input = json_decode((string) file_get_contents('php://input'), true);
    if (!is_array($input) || !hash_equals(csrf(), (string) ($input['csrf'] ?? ''))) respond(['error' => 'Ungültige Anfrage.'], 422);
    $existing = json_decode((string) file_get_contents($contentFile), true);
    $submitted = $input['content'] ?? null;
    if (!is_array($existing) || !is_array($submitted) || array_diff(array_keys($submitted), array_keys($existing))) respond(['error' => 'Ungültige Felder.'], 422);
    foreach ($existing as $key => $_value) {
        if (!isset($submitted[$key]) || !is_string($submitted[$key]) || mb_strlen($submitted[$key]) > 3000) respond(['error' => 'Ungültiger Inhalt.'], 422);
        $existing[$key] = trim($submitted[$key]);
    }
    $json = json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    if (file_put_contents($contentFile, $json, LOCK_EX) === false) respond(['error' => 'Speichern fehlgeschlagen. Bitte Serverrechte prüfen.'], 500);
    respond(['ok' => true]);
}

if (!$authenticated): $loginError = !empty($_SESSION['blueprint_login_error']); unset($_SESSION['blueprint_login_error']); ?><style>@media(max-width:700px){input,textarea,select,button{font-size:16px!important;max-width:100%;min-width:0}.field{min-width:0}}</style>
<!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Blueprint — Zugang</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=DM+Mono&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet"><style>body{margin:0;min-height:100vh;background:#10100f;color:#e9e4d8;display:grid;place-items:center;font-family:'DM Mono',monospace}.box{width:min(420px,calc(100vw - 48px));border:1px solid rgba(233,228,216,.18);padding:45px}.label{font-size:10px;letter-spacing:.1em;text-transform:uppercase;color:#969388}.title{font:54px/.86 'Instrument Serif',serif;margin:24px 0 37px}.notice{font-size:12px;line-height:1.6;color:#c6a48a;margin-bottom:18px}input,button{box-sizing:border-box;width:100%;font:12px 'DM Mono',monospace}input{margin:10px 0 16px;padding:14px;background:transparent;border:1px solid rgba(233,228,216,.35);color:#fff}button{padding:14px;border:0;background:#e9e4d8;color:#10100f;cursor:pointer}</style></head><body><form class="box" method="post" action="?action=login"><div class="label">Private editing area</div><h1 class="title">Blueprint<br><em>Editor.</em></h1><?php if (!$configured): ?><p class="notice">Bitte zuerst in <code>config.php</code> ein sicheres Passwort setzen.</p><?php elseif ($loginError): ?><p class="notice">Das Passwort stimmt nicht.</p><?php endif; ?><label class="label" for="password">Passwort</label><input id="password" name="password" type="password" autocomplete="current-password" required <?php if (!$configured) echo 'disabled'; ?>><button type="submit" <?php if (!$configured) echo 'disabled'; ?>>Editor öffnen</button></form></body></html>
<?php exit; endif; ?><style>@media(max-width:700px){input,textarea,select,button{font-size:16px!important;max-width:100%;min-width:0}.field{min-width:0}}</style>

$content = json_decode((string) file_get_contents($contentFile), true) ?: [];
$groups = [
  'Identität & Einstieg' => ['brand','meta','year','heroPrefix','heroEmphasis','heroSuffix','heroSubline','heroSetup','quote'],
  'Fundament' => ['foundationLead','principle1Title','principle1Text','principle2Title','principle2Text','principle3Title','principle3Text'],
  'Rollen' => ['strategyName','strategyRole','strategyText','creativeName','creativeRole','creativeText'],
  'Akquise' => ['acquisitionLead','acquisitionLead1','acquisition1Title','acquisition1Text','acquisition2Title','acquisition2Text'],
  'Realitätscheck & Abschluss' => ['realityPrefix','realityEmphasis','risk1Title','risk1Text','risk2Title','risk2Text','risk3Title','risk3Text','closingPrefix','closingEmphasis','closingText','status','footerNote'],
];
$labels = ['brand'=>'Markenname','meta'=>'Kopfzeile','year'=>'Jahr','heroPrefix'=>'Titel, erster Teil','heroEmphasis'=>'Titel, kursiver Teil','heroSuffix'=>'Titel, letzter Teil','heroSubline'=>'Unterzeile','heroSetup'=>'Setup-Zeile','quote'=>'Leitsatz','foundationLead'=>'Einleitung','principle1Title'=>'Prinzip I — Titel','principle1Text'=>'Prinzip I — Text','principle2Title'=>'Prinzip II — Titel','principle2Text'=>'Prinzip II — Text','principle3Title'=>'Prinzip III — Titel','principle3Text'=>'Prinzip III — Text','strategyName'=>'Strategy — Name','strategyRole'=>'Strategy — Rolle','strategyText'=>'Strategy — Beschreibung','creativeName'=>'Creative — Name','creativeRole'=>'Creative — Rolle','creativeText'=>'Creative — Beschreibung','acquisitionLead'=>'Akquise — Einleitung','acquisition1Title'=>'Akquise A — Titel','acquisition1Text'=>'Akquise A — Text','acquisition2Title'=>'Akquise B — Titel','acquisition2Text'=>'Akquise B — Text','realityPrefix'=>'Realität — erster Teil','realityEmphasis'=>'Realität — kursiver Teil','risk1Title'=>'Hürde 01 — Titel','risk1Text'=>'Hürde 01 — Gegenmaßnahme','risk2Title'=>'Hürde 02 — Titel','risk2Text'=>'Hürde 02 — Gegenmaßnahme','risk3Title'=>'Hürde 03 — Titel','risk3Text'=>'Hürde 03 — Gegenmaßnahme','closingPrefix'=>'Abschluss — erster Teil','closingEmphasis'=>'Abschluss — kursiver Teil','closingText'=>'Abschluss — Text','status'=>'Status','footerNote'=>'Footer-Notiz'];
?><!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Blueprint — Editor</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=DM+Mono&family=DM+Sans:wght@400;500&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet"><style>:root{--i:#e9e4d8;--m:#969388;--l:rgba(233,228,216,.18)}*{box-sizing:border-box}body{margin:0;background:#10100f;color:var(--i);font:14px/1.5 'DM Sans',sans-serif}.top{position:sticky;top:0;z-index:2;background:#10100f;border-bottom:1px solid var(--l);padding:18px 5vw;display:flex;justify-content:space-between;align-items:center;font:10px 'DM Mono',monospace;text-transform:uppercase;letter-spacing:.08em}.top a{color:var(--m);text-decoration:none}.top .right{display:flex;align-items:center;gap:22px}.publish{border:0;background:var(--i);color:#10100f;padding:11px 16px;font:10px 'DM Mono',monospace;text-transform:uppercase;letter-spacing:.08em;cursor:pointer}.wrap{width:min(900px,88vw);margin:8vh auto 12vh}.intro{margin-bottom:8vh}.eyebrow,label{font:10px 'DM Mono',monospace;text-transform:uppercase;letter-spacing:.08em;color:var(--m)}h1{font:clamp(54px,8vw,90px)/.86 'Instrument Serif',serif;letter-spacing:-.04em;margin:20px 0}h1 em{font-weight:400}.intro p{max-width:480px;color:var(--m)}section{border-top:1px solid var(--l);padding:30px 0 45px}h2{font:30px 'Instrument Serif',serif;font-weight:400;margin:0 0 30px}.field{display:grid;grid-template-columns:220px 1fr;gap:25px;padding:13px 0;border-top:1px solid rgba(233,228,216,.08)}input,textarea{width:100%;border:1px solid rgba(233,228,216,.25);background:#171715;color:var(--i);padding:11px 12px;font:13px/1.5 'DM Sans',sans-serif;resize:vertical}textarea{min-height:90px}.message{position:fixed;right:5vw;bottom:25px;background:#e9e4d8;color:#10100f;padding:13px 16px;font:11px 'DM Mono',monospace;display:none}@media(max-width:640px){.field{grid-template-columns:1fr;gap:8px}.top .right{gap:12px}.top .view-label{display:none}}</style></head><body><header class="top"><span>Blueprint editor</span><div class="right"><a class="view-label" href="../" target="_blank">Seite ansehen ↗</a><a href="?logout=1">Abmelden</a><button class="publish" id="publish">Änderungen veröffentlichen</button></div></header><main class="wrap"><div class="intro"><p class="eyebrow">Private editing area</p><h1>Worte formen<br><em>Wirkung.</em></h1><p>Änderungen sind erst nach dem Veröffentlichen auf der öffentlichen Blueprint-Seite sichtbar.</p></div><form id="editor"><?php foreach ($groups as $group => $keys): ?><section><h2><?= htmlspecialchars($group) ?></h2><?php foreach ($keys as $key): $value=$content[$key] ?? ''; $long=mb_strlen($value)>90; ?><div class="field"><label for="<?= $key ?>"><?= htmlspecialchars($labels[$key]) ?></label><?php if ($long): ?><textarea id="<?= $key ?>" data-key="<?= $key ?>"><?= htmlspecialchars($value) ?></textarea><?php else: ?><input id="<?= $key ?>" data-key="<?= $key ?>" value="<?= htmlspecialchars($value) ?>"><?php endif; ?></div><?php endforeach; ?></section><?php endforeach; ?></form></main><div class="message" id="message"></div><script>const csrf=<?= json_encode(csrf()) ?>,btn=document.querySelector('#publish'),message=document.querySelector('#message');btn.addEventListener('click',async()=>{const content={};document.querySelectorAll('[data-key]').forEach(e=>content[e.dataset.key]=e.value);btn.disabled=true;btn.textContent='Wird veröffentlicht …';try{const r=await fetch('?action=save',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({csrf,content})});const d=await r.json();if(!r.ok)throw Error(d.error);message.textContent='Veröffentlicht. Die Seite ist aktuell.';message.style.display='block';setTimeout(()=>message.style.display='none',4000)}catch(e){message.textContent=e.message||'Etwas ist schiefgelaufen.';message.style.display='block'}finally{btn.disabled=false;btn.textContent='Änderungen veröffentlichen'}});</script></body></html>
