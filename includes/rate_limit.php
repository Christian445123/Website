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
 *   - 30 Requests pro 60 Sekunden  (Burst-Schutz)
 *   - 200 Requests pro 3600 Sekunden (Stunden-Cap)
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php'; // lädt auch env.php

// ── Konfiguration ──────────────────────────────────────────────
const RL_DIR           = __DIR__ . '/../tmp/'; // Datei-Fallback-Verzeichnis
const RL_LIMIT_MINUTE  = 30;                   // max Requests pro Minute
const RL_LIMIT_HOUR    = 200;                  // max Requests pro Stunde
const RL_WINDOW_MINUTE = 60;                   // Sekunden
const RL_WINDOW_HOUR   = 3600;                 // Sekunden
const RL_CLEANUP_PROB  = 5;                    // % Wahrscheinlichkeit für GC

// ── Helpers ─────────────────────────────────────────────────────

/**
 * Returns the visitor's real IP, honouring common proxy headers
 * but falling back to REMOTE_ADDR.
 */
function rl_get_ip(): string
{
    $candidates = [
        'HTTP_CF_CONNECTING_IP',   // Cloudflare
        'HTTP_X_REAL_IP',
        'HTTP_X_FORWARDED_FOR',
        'REMOTE_ADDR',
    ];
    foreach ($candidates as $key) {
        if (!empty($_SERVER[$key])) {
            // X-Forwarded-For may be comma-separated; take first
            $ip = trim(explode(',', $_SERVER[$key])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return '0.0.0.0';
}

/**
 * Returns a SHA-256 hash of the IP (rohe IPs werden nie gespeichert).
 */
function rl_hash_ip(string $ip): string
{
    return hash('sha256', $ip);
}

/**
 * DB-Garbage-Collection: löscht Zeilen älter als 2 h.
 * Gibt bei jedem Fehler stillschweigend auf.
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
            ->execute([time() - (RL_WINDOW_HOUR * 2)]);
    } catch (Throwable) {
        rl_gc_files(); // Fallback: Dateien aufräumen
    }
}

/**
 * Datei-basierte Garbage-Collection: entfernt veraltete JSON-Dateien.
 */
function rl_gc_files(): void
{
    $threshold = time() - (RL_WINDOW_HOUR * 2);
    foreach (glob(RL_DIR . 'rl_*.json') ?: [] as $file) {
        if (filemtime($file) < $threshold) {
            @unlink($file);
        }
    }
}

// ── Main check ──────────────────────────────────────────────────

/**
 * Prüft das Rate-Limit gegen die Datenbank.
 * Gibt true zurück wenn erfolgreich, false wenn DB nicht nutzbar
 * (fehlende Verbindung ODER fehlende Tabelle).
 */
function rl_check_db(string $ipHash, int $now): bool
{
    try {
        $pdo = db();
        if ($pdo === null) {
            return false;
        }

        // Minuten-Fenster zählen
        $s = $pdo->prepare(
            'SELECT COUNT(*) FROM `rate_limit` WHERE `ip_hash` = ? AND `requested_at` > ?'
        );
        $s->execute([$ipHash, $now - RL_WINDOW_MINUTE]);
        if ((int) $s->fetchColumn() >= RL_LIMIT_MINUTE) {
            rl_send_429(RL_WINDOW_MINUTE);
        }

        // Stunden-Fenster zählen
        $s->execute([$ipHash, $now - RL_WINDOW_HOUR]);
        if ((int) $s->fetchColumn() >= RL_LIMIT_HOUR) {
            rl_send_429(RL_WINDOW_HOUR);
        }

        // Request protokollieren
        $pdo->prepare('INSERT INTO `rate_limit` (`ip_hash`, `requested_at`) VALUES (?, ?)')
            ->execute([$ipHash, $now]);

        return true;
    } catch (Throwable $e) {
        error_log('[CG] rate_limit DB error: ' . $e->getMessage());
        return false; // Fallback auf Datei-Limiter
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

    $log = array_values(array_filter(
        $log, static fn(int $t) => $t > ($now - RL_WINDOW_HOUR)
    ));

    $minuteCount = count(array_filter($log, static fn(int $t) => $t > ($now - RL_WINDOW_MINUTE)));
    $hourCount   = count($log);

    if ($minuteCount >= RL_LIMIT_MINUTE) { rl_send_429(RL_WINDOW_MINUTE); }
    if ($hourCount   >= RL_LIMIT_HOUR)   { rl_send_429(RL_WINDOW_HOUR);   }

    $log[] = $now;
    $tmp   = $path . '.tmp';
    file_put_contents($tmp, json_encode($log), LOCK_EX);
    rename($tmp, $path);
}

/**
 * Hauptfunktion – wird bei jedem Seitenaufruf ausgeführt.
 * Versucht DB, fällt bei Fehler automatisch auf Dateien zurück.
 */
function rate_limit_check(): void
{
    $ip  = rl_get_ip();
    $now = time();

    if (rand(1, 100) <= RL_CLEANUP_PROB) {
        rl_garbage_collect();
    }

    // DB probieren; bei Fehler Datei-Fallback
    if (!rl_check_db(rl_hash_ip($ip), $now)) {
        rl_check_file($ip, $now);
    }
}

/**
 * Sends a 429 Too Many Requests response and renders a friendly page.
 * Terminates execution afterwards.
 */
function rl_send_429(int $retry_after): never
{
    http_response_code(429);
    header('Retry-After: ' . $retry_after);
    header('Content-Type: text/html; charset=UTF-8');

    $minutes = (int) ceil($retry_after / 60);
    $msg = $retry_after <= 60
        ? 'Bitte warte kurz und lade die Seite erneut.'
        : "Bitte versuche es in ca. {$minutes} Minute" . ($minutes !== 1 ? 'n' : '') . " erneut.";
    ?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>429 – Zu viele Anfragen | Chris_Gaming445</title>
  <link rel="shortcut icon" href="/assets/images/cg-logo-neu.png-496x496.png" type="image/x-icon">
  <link rel="stylesheet" href="/assets/bootstrap/css/bootstrap.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Tilt+Warp&family=Golos+Text:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
    <p><?= htmlspecialchars($msg) ?></p>
    <a href="/"><i class="fas fa-arrow-left"></i>Zurück zur Startseite</a>
  </div>
</body>
</html>
<?php
    exit;
}

// ── Run immediately on include ───────────────────────────────────
rate_limit_check();
