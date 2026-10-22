<?php
/**
 * CG Rate Limiter  (DB-backed + automatischer Datei-Fallback)
 * -------------------------------------------------------------
 * Primär: speichert Requests in der `rate_limit` MariaDB-Tabelle.
 * Fallback: ist die DB nicht erreichbar oder die Tabelle fehlt noch,
 *           wird automatisch der dateibasierte Limiter in tmp/ genutzt.
 *           Die Website bleibt in jedem Fall online.
 *
 * Limits:
 *   - 150 Requests pro 30 Minuten (gleitendes Fenster)
 *   - Bei Überschreitung: 30 Minuten Sperre + Activity-Log + Discord Webhook
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';      // lädt auch env.php
require_once __DIR__ . '/logger.php';  // Activity Logger

//  Konfiguration 
const RL_DIR       = __DIR__ . '/../tmp/'; // Datei-Fallback-Verzeichnis
const RL_LIMIT     = 150;                  // max Requests pro Zeitfenster
const RL_WINDOW    = 1800;                 // Zeitfenster: 30 Minuten (Sekunden)
const RL_CLEANUP_PROB = 5;                 // % Wahrscheinlichkeit für GC

//  Helpers 

/**
 * Returns the visitor's real IP (Proxy-Header nur von vertrauenswürdigen Proxies).
 */
function rl_get_ip(): string
{
    return cg_client_ip(); // spoof-sicher, siehe security.php
}

/**
 * Returns a SHA-256 hash of the IP.
 */
function rl_hash_ip(string $ip): string
{
    return hash('sha256', $ip);
}

/**
 * DB-Garbage-Collection: löscht Zeilen älter als 2  RL_WINDOW.
 */
function rl_garbage_collect(): void
{
    try {
        $pdo = db();
        if ($pdo === null) {
            rl_gc_files();
            return;
        }
        $pdo->prepare('DELETE FROM `rate_limit` WHERE `requested_at` < ?')
            ->execute([time() - (RL_WINDOW * 2)]);
    } catch (Throwable) {
        rl_gc_files();
    }
}

/**
 * Datei-basierte Garbage-Collection.
 */
function rl_gc_files(): void
{
    $threshold = time() - (RL_WINDOW * 2);
    foreach (glob(RL_DIR . 'rl_*.json') ?: [] as $file) {
        if (filemtime($file) < $threshold) {
            @unlink($file);
        }
    }
}

//  Main check 

/**
 * Prüft das Rate-Limit gegen die Datenbank (30-Minuten-Fenster).
 * Speichert die echte IP und den IP-Hash.
 * Gibt true zurück wenn erfolgreich, false bei DB-Fehler ( Datei-Fallback).
 */
function rl_check_db(string $ip, int $now): bool
{
    $ipHash = rl_hash_ip($ip);

    try {
        $pdo = db();
        if ($pdo === null) {
            return false;
        }

        // 30-Minuten-Fenster zählen
        $s = $pdo->prepare(
            'SELECT COUNT(*) FROM `rate_limit` WHERE `ip_hash` = ? AND `requested_at` > ?'
        );
        $s->execute([$ipHash, $now - RL_WINDOW]);

        if ((int) $s->fetchColumn() >= RL_LIMIT) {
            rl_send_429($ip);
        }

        // Request protokollieren (IP + Hash)
        $pdo->prepare(
            'INSERT INTO `rate_limit` (`ip`, `ip_hash`, `requested_at`) VALUES (?, ?, ?)'
        )->execute([$ip, $ipHash, $now]);

        return true;
    } catch (Throwable $e) {
        error_log('[CG] rate_limit DB error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Dateibasierter Rate-Limit-Check (Fallback wenn DB nicht erreichbar).
 */
function rl_check_file(string $ip, int $now): void
{
    $path = RL_DIR . 'rl_' . rl_hash_ip($ip) . '.json';

    if (!is_dir(RL_DIR)) {
        @mkdir(RL_DIR, 0750, true);
    }

    $log = [];
    if (file_exists($path)) {
        $raw = json_decode((string) file_get_contents($path), true);
        $log = is_array($raw) ? $raw : [];
    }

    // Altes Fenster herausfiltern
    $log   = array_values(array_filter($log, static fn(int $t) => $t > ($now - RL_WINDOW)));
    $count = count($log);

    if ($count >= RL_LIMIT) {
        rl_send_429($ip);
    }

    $log[] = $now;
    $tmp   = $path . '.tmp';
    file_put_contents($tmp, json_encode($log), LOCK_EX);
    rename($tmp, $path);
}

/**
 * Hauptfunktion  wird bei jedem Seitenaufruf ausgeführt.
 */
function rate_limit_check(): void
{
    $ip  = rl_get_ip();
    $now = time();

    if (rand(1, 100) <= RL_CLEANUP_PROB) {
        rl_garbage_collect();
    }

    if (!rl_check_db($ip, $now)) {
        rl_check_file($ip, $now);
    }
}

/**
 * Sendet HTTP 429, loggt den Event in DB + Discord und beendet die Ausführung.
 */
function rl_send_429(string $ip): void
{
    // Activity-Log: Rate-Limit-Treffer mit realer IP
    cg_log(LOG_RATE_LIMIT_HIT, [
        'window_seconds' => RL_WINDOW,
        'limit'          => RL_LIMIT,
        'retry_after'    => RL_WINDOW,
    ], null, $ip, webhook: true);

    http_response_code(429);
    header('Retry-After: ' . RL_WINDOW);
    header('Content-Type: text/html; charset=UTF-8');
    ?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>429  Zu viele Anfragen | Chris_Gaming445</title>
  <link rel="shortcut icon" href="/assets/images/cg-logo-neu.png-496x496.png" type="image/x-icon">
  <link rel="stylesheet" href="/assets/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="/assets/fonts/fonts.css">
  <link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
  <style>
    :root { --cg-bg:#0d0d0f; --cg-red:#e03131; --cg-surface:#161619; }
    body { background:var(--cg-bg); color:#e8e8ed; font-family:'Golos Text',sans-serif;
           min-height:100vh; display:flex; align-items:center; justify-content:center;
           margin:0; padding:1rem; }
    .box { background:var(--cg-surface); border:1px solid rgba(224,49,49,.3);
           border-radius:1rem; padding:3rem 2.5rem; max-width:480px; text-align:center;
           box-shadow:0 0 40px rgba(224,49,49,.15); }
    h1 { font-family:'Tilt Warp',sans-serif; font-size:5rem; color:var(--cg-red);
         margin:0 0 .25rem; line-height:1; }
    h2 { font-family:'Tilt Warp',sans-serif; font-size:1.5rem; margin-bottom:1rem; }
    p  { color:#8b8b9a; font-size:.95rem; margin-bottom:1.5rem; }
    a  { display:inline-flex; align-items:center; gap:.5rem; padding:.6rem 1.5rem;
         background:var(--cg-red); color:#fff; border-radius:.5rem; text-decoration:none;
         font-weight:600; transition:background .2s; }
    a:hover { background:#c92a2a; }
  </style>
</head>
<body>
  <div class="box">
    <h1>429</h1>
    <h2>Zu viele Anfragen</h2>
    <p>Du hast das Anfrage-Limit erreicht. Bitte versuche es in <strong>30 Minuten</strong> erneut.</p>
    <a href="/"><i class="fas fa-arrow-left"></i>Zur&uuml;ck zur Startseite</a>
  </div>
</body>
</html>
<?php
    exit;
}

//  Run immediately on include 
rate_limit_check();