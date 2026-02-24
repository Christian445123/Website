<?php
/**
 * CG Rate Limiter
 * ---------------
 * File-based IP rate limiter. No database required.
 *
 * Limits:
 *   - 30 requests per 60 seconds  (burst protection)
 *   - 200 requests per 3600 seconds (hourly cap)
 *
 * How it works:
 *   Each IP gets a JSON file in tmp/  containing an array
 *   of UNIX timestamps for every request. On each page load
 *   the file is read, old timestamps pruned, limits checked,
 *   and the new timestamp appended.
 */

declare(strict_types=1);

// ── Configuration ────────────────────────────────────────────────
const RL_DIR           = __DIR__ . '/../tmp/';   // storage directory
const RL_LIMIT_MINUTE  = 30;                      // max requests per minute
const RL_LIMIT_HOUR    = 200;                     // max requests per hour
const RL_WINDOW_MINUTE = 60;                      // seconds
const RL_WINDOW_HOUR   = 3600;                    // seconds
const RL_CLEANUP_PROB  = 10;                      // % chance to run garbage collection

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
 * Returns the filesystem path for an IP's rate-limit file.
 * The IP is hashed so that no raw IP addresses are stored on disk.
 */
function rl_file_path(string $ip): string
{
    return RL_DIR . 'rl_' . hash('sha256', $ip) . '.json';
}

/**
 * Reads the request log for an IP (array of timestamps).
 */
function rl_read(string $path): array
{
    if (!file_exists($path)) {
        return [];
    }
    $data = json_decode(file_get_contents($path), true);
    return is_array($data) ? $data : [];
}

/**
 * Writes the request log back to disk (atomic via temp file).
 */
function rl_write(string $path, array $timestamps): void
{
    $tmp = $path . '.tmp';
    file_put_contents($tmp, json_encode($timestamps), LOCK_EX);
    rename($tmp, $path);
}

/**
 * Occasionally removes stale rate-limit files (older than 2 h).
 */
function rl_garbage_collect(): void
{
    $threshold = time() - (RL_WINDOW_HOUR * 2);
    foreach (glob(RL_DIR . 'rl_*.json') as $file) {
        if (filemtime($file) < $threshold) {
            @unlink($file);
        }
    }
}

// ── Main check ──────────────────────────────────────────────────

/**
 * Runs the rate-limit check. If the visitor is over limit,
 * sends a 429 response and terminates execution.
 * Otherwise, records the request and returns.
 */
function rate_limit_check(): void
{
    // Probabilistic garbage collection (avoid running every request)
    if (rand(1, 100) <= RL_CLEANUP_PROB) {
        rl_garbage_collect();
    }

    $ip   = rl_get_ip();
    $path = rl_file_path($ip);
    $now  = time();

    // Load existing timestamps
    $log = rl_read($path);

    // Remove entries older than the longest window
    $log = array_filter($log, static fn(int $t) => $t > ($now - RL_WINDOW_HOUR));
    $log = array_values($log);

    // Count requests in each window
    $minute_count = count(array_filter($log, static fn(int $t) => $t > ($now - RL_WINDOW_MINUTE)));
    $hour_count   = count($log);

    // Determine if over limit
    if ($minute_count >= RL_LIMIT_MINUTE) {
        $retry_after = RL_WINDOW_MINUTE;
        rl_send_429($retry_after);
    }

    if ($hour_count >= RL_LIMIT_HOUR) {
        $retry_after = RL_WINDOW_HOUR;
        rl_send_429($retry_after);
    }

    // Record this request
    $log[] = $now;
    rl_write($path, $log);
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
