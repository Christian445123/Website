<?php
/**
 * CG Rate Limiter (DB-backed)
 * ----------------------------
 * Database-based IP rate limiter using the `rate_limit` table.
 *
 * Limits:
 *   - 30 requests per 60 seconds  (burst protection)
 *   - 200 requests per 3600 seconds (hourly cap)
 *
 * Each page load inserts one row with a SHA-256 hashed IP and a
 * UNIX timestamp.  Old rows (> 2 h) are pruned probabilistically.
 *
 * Requires:
 *   - includes/db.php  (PDO singleton via db())
 *   - `rate_limit` table – see sql/setup.sql
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';

// ── Configuration ────────────────────────────────────────────────
const RL_LIMIT_MINUTE  = 30;    // max requests per minute
const RL_LIMIT_HOUR    = 200;   // max requests per hour
const RL_WINDOW_MINUTE = 60;    // seconds
const RL_WINDOW_HOUR   = 3600;  // seconds
const RL_CLEANUP_PROB  = 5;     // % chance to prune old rows

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
 * Returns a SHA-256 hash of the raw IP address.
 * Raw IPs are never stored; only the hash is persisted.
 */
function rl_hash_ip(string $ip): string
{
    return hash('sha256', $ip);
}

/**
 * Deletes all rate-limit rows older than 2 hours.
 * Called probabilistically to avoid per-request overhead.
 */
function rl_garbage_collect(): void
{
    $threshold = time() - (RL_WINDOW_HOUR * 2);
    db()->prepare('DELETE FROM `rate_limit` WHERE `requested_at` < ?')
        ->execute([$threshold]);
}

// ── Main check ──────────────────────────────────────────────────

/**
 * Runs the rate-limit check against the database.
 * Sends a 429 response and terminates if the visitor is over limit.
 * Otherwise logs the request row and returns.
 */
function rate_limit_check(): void
{
    // Probabilistic garbage collection
    if (rand(1, 100) <= RL_CLEANUP_PROB) {
        rl_garbage_collect();
    }

    $ipHash = rl_hash_ip(rl_get_ip());
    $now    = time();

    $pdo = db();

    // Count requests in minute window
    $stmtMin = $pdo->prepare(
        'SELECT COUNT(*) FROM `rate_limit`
         WHERE `ip_hash` = ? AND `requested_at` > ?'
    );
    $stmtMin->execute([$ipHash, $now - RL_WINDOW_MINUTE]);
    $minuteCount = (int) $stmtMin->fetchColumn();

    if ($minuteCount >= RL_LIMIT_MINUTE) {
        rl_send_429(RL_WINDOW_MINUTE);
    }

    // Count requests in hour window
    $stmtHour = $pdo->prepare(
        'SELECT COUNT(*) FROM `rate_limit`
         WHERE `ip_hash` = ? AND `requested_at` > ?'
    );
    $stmtHour->execute([$ipHash, $now - RL_WINDOW_HOUR]);
    $hourCount = (int) $stmtHour->fetchColumn();

    if ($hourCount >= RL_LIMIT_HOUR) {
        rl_send_429(RL_WINDOW_HOUR);
    }

    // Log this request
    $pdo->prepare('INSERT INTO `rate_limit` (`ip_hash`, `requested_at`) VALUES (?, ?)')
        ->execute([$ipHash, $now]);
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
