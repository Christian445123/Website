<?php
/**
 * cg_diag.php – System-Diagnoseseite
 * Zugriff nur mit korrektem ?key= Parameter.
 * URL: https://chrisgaming.at/cg_diag.php?key=cg_debug_2026
 */
declare(strict_types=1);

// ── Zugriffsschutz ────────────────────────────────────────────────────────────
if (($_GET['key'] ?? '') !== 'cg_debug_2026') {
    http_response_code(403);
    exit('403 Forbidden');
}

// ── AJAX-Aktion: composer install ────────────────────────────────────────────
if (($_POST['action'] ?? '') === 'composer_install') {
    header('Content-Type: text/plain; charset=UTF-8');
    $composerPaths = [
        '/usr/local/bin/composer',
        '/usr/bin/composer',
        trim((string) shell_exec('which composer 2>/dev/null')),
    ];
    $composer = '';
    foreach ($composerPaths as $p) {
        if ($p !== '' && is_executable($p)) { $composer = $p; break; }
    }
    if ($composer === '') {
        echo "FEHLER: composer nicht gefunden.\n";
        echo "Bitte manuell auf dem Server ausführen:\n";
        echo "  cd " . __DIR__ . "\n";
        echo "  composer install --no-dev --optimize-autoloader\n";
        exit;
    }
    $cmd = escapeshellarg($composer) . ' install --no-dev --optimize-autoloader --no-interaction 2>&1';
    $cwd = __DIR__;
    chdir($cwd);
    $output = [];
    $code   = 0;
    exec($cmd, $output, $code);
    echo implode("\n", $output) . "\n";
    echo ($code === 0) ? "\n✓ composer install erfolgreich." : "\n✗ Fehlercode: {$code}";
    exit;
}

// ── AJAX-Aktion: Test-E-Mail senden ───────────────────────────────────────
if (($_POST['action'] ?? '') === 'send_test_mail') {
    header('Content-Type: text/plain; charset=UTF-8');

    $autoload = __DIR__ . '/vendor/autoload.php';
    if (!is_file($autoload)) {
        echo "FEHLER: PHPMailer nicht gefunden.\nBitte zuerst composer install ausführen.\n";
        exit;
    }
    require_once __DIR__ . '/includes/env.php';
    require_once $autoload;

    $recipient = trim((string) ($_POST['recipient'] ?? ''));
    if ($recipient === '' || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
        echo "FEHLER: Ungültige Empfänger-Adresse.\n";
        exit;
    }

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = (string) env('SMTP_HOST', 'localhost');
        $mail->Port       = (int)    env('SMTP_PORT', 587);
        $mail->SMTPAuth   = true;
        $mail->Username   = (string) env('SMTP_USERNAME', '');
        $mail->Password   = (string) env('SMTP_PASSWORD', '');
        $enc = strtoupper((string) env('SMTP_ENCRYPTION', 'STARTTLS'));
        $mail->SMTPSecure = ($enc === 'SSL')
            ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
            : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;

        $mail->setFrom(
            (string) env('SMTP_FROM', 'noreply@chrisgaming.at'),
            (string) env('SMTP_FROM_NAME', 'ChrisGaming')
        );
        $mail->addAddress($recipient);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'ChrisGaming – SMTP Test ' . date('d.m.Y H:i:s');
        $mail->isHTML(false);
        $mail->Body    = "Das ist eine Test-E-Mail von der Diagnose-Seite.\n\n"
                       . "Server: " . (string) env('SMTP_HOST') . ":" . (string) env('SMTP_PORT') . "\n"
                       . "Absender: " . (string) env('SMTP_FROM') . "\n"
                       . "Zeitpunkt: " . date('d.m.Y H:i:s') . "\n";

        $mail->SMTPDebug = 2;
        $mail->Debugoutput = function(string $str, int $level) {
            echo "[SMTP] " . rtrim($str) . "\n";
        };

        $mail->send();
        echo "\n✓ Test-E-Mail erfolgreich an {$recipient} gesendet.";
    } catch (\PHPMailer\PHPMailer\Exception $e) {
        echo "\n✗ FEHLER: " . $e->getMessage();
    }
    exit;
}

error_reporting(E_ALL);
ini_set('display_errors', '0'); // Fehler über Throwable abfangen, nicht ausgeben

// ── Includes laden ────────────────────────────────────────────────────────────
$includeErrors = [];

foreach (['env', 'db', 'logger', 'rate_limit', 'auth'] as $inc) {
    try {
        require_once __DIR__ . "/includes/{$inc}.php";
    } catch (Throwable $ex) {
        $includeErrors[$inc] = $ex->getMessage() . ' @ ' . basename($ex->getFile()) . ':' . $ex->getLine();
    }
}

// ── Hilfsfunktion: Status-Badge ───────────────────────────────────────────────
function diagBadge(bool $ok, string $okLabel = 'OK', string $failLabel = 'FEHLER'): string
{
    return $ok
        ? "<span class=\"diag-badge diag-ok\"><i class=\"fas fa-check me-1\"></i>{$okLabel}</span>"
        : "<span class=\"diag-badge diag-fail\"><i class=\"fas fa-times me-1\"></i>{$failLabel}</span>";
}
function diagWarn(string $label): string
{
    return "<span class=\"diag-badge diag-warn\"><i class=\"fas fa-triangle-exclamation me-1\"></i>{$label}</span>";
}

// ── Tests durchführen ─────────────────────────────────────────────────────────

// 1. PHP-Includes
$tests = [];
foreach (['env', 'db', 'logger', 'rate_limit', 'auth'] as $inc) {
    $tests['include_' . $inc] = [
        'label' => "includes/{$inc}.php geladen",
        'ok'    => !isset($includeErrors[$inc]),
        'detail'=> $includeErrors[$inc] ?? null,
    ];
}

// 2. PHP-Version
$phpOk = version_compare(PHP_VERSION, '8.1.0', '>=');
$tests['php_version'] = [
    'label'  => 'PHP-Version ≥ 8.1',
    'ok'     => $phpOk,
    'detail' => 'PHP ' . PHP_VERSION,
];

// 3. PHP-Extensions
foreach (['pdo', 'pdo_mysql', 'openssl', 'mbstring', 'json', 'curl'] as $ext) {
    $tests['ext_' . $ext] = [
        'label' => "Extension: {$ext}",
        'ok'    => extension_loaded($ext),
    ];
}

// 4. OPcache
$tests['opcache'] = [
    'label'  => 'OPcache aktiv',
    'ok'     => function_exists('opcache_get_status') && (opcache_get_status(false)['opcache_enabled'] ?? false),
    'detail' => function_exists('opcache_get_status') ? null : 'opcache_get_status() nicht verfügbar',
];

// 5. Datenbankverbindung
$dbOk    = false;
$dbError = null;
$dbInfo  = null;
if (!isset($includeErrors['db'])) {
    try {
        $pdo = db();
        if ($pdo !== null) {
            $dbOk   = true;
            $dbInfo = $pdo->query('SELECT VERSION() AS v')->fetchColumn();
        } else {
            $dbError = 'db() returned NULL';
        }
    } catch (Throwable $ex) {
        $dbError = $ex->getMessage();
    }
}
$tests['db_connect'] = [
    'label'  => 'Datenbankverbindung',
    'ok'     => $dbOk,
    'detail' => $dbOk ? ('MariaDB/MySQL ' . $dbInfo) : $dbError,
];

// 6. Datenbank-Tabellen
$dbTables = [];
if ($dbOk) {
    try {
        $rows = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $dbTables = $rows;
    } catch (Throwable) {}
}
$tests['db_tables'] = [
    'label'  => 'DB-Tabellen vorhanden',
    'ok'     => count($dbTables) > 0,
    'detail' => count($dbTables) > 0 ? implode(', ', $dbTables) : 'Keine Tabellen gefunden',
];

// 7. secret.key (Verschlüsselung)
$keyFile  = '/home/chrisgaming/secret.key';
$keyFound = is_readable($keyFile);
$tests['secret_key'] = [
    'label'  => 'secret.key lesbar',
    'ok'     => $keyFound,
    'detail' => $keyFound ? $keyFile : "Nicht gefunden: {$keyFile}",
];

// 8. DB_PASS verschlüsselt?
$dbPassRaw     = (string) env('DB_PASS', '');
$dbPassEncoded = str_starts_with((string) file_get_contents(__DIR__ . '/.env'), '') &&
                 str_contains((string) file_get_contents(__DIR__ . '/.env'), 'DB_PASS=ENC:');
// Einfacherer Check: env() gibt entschlüsselten Wert zurück – prüfen ob .env noch Klartext hat
$envRaw = (string) file_get_contents(__DIR__ . '/.env');
$tests['db_pass_encrypted'] = [
    'label'  => 'DB_PASS verschlüsselt (ENC:)',
    'ok'     => (bool) preg_match('/^DB_PASS=ENC:/m', $envRaw),
    'detail' => preg_match('/^DB_PASS=ENC:/m', $envRaw) ? null : 'DB_PASS steht noch im Klartext in .env → encrypt_env.php ausführen!',
];
$tests['smtp_pass_encrypted'] = [
    'label'  => 'SMTP_PASSWORD verschlüsselt (ENC:)',
    'ok'     => (bool) preg_match('/^SMTP_PASSWORD=ENC:/m', $envRaw),
    'detail' => preg_match('/^SMTP_PASSWORD=ENC:/m', $envRaw) ? null : 'SMTP_PASSWORD steht noch im Klartext → encrypt_env.php ausführen!',
];

// 9. PHPMailer
$phpmailerFound = is_file(__DIR__ . '/vendor/autoload.php');
$tests['phpmailer'] = [
    'label'  => 'PHPMailer (vendor/autoload.php)',
    'ok'     => $phpmailerFound,
    'detail' => $phpmailerFound ? 'composer install ausgeführt' : 'Nicht gefunden → composer install auf dem Server ausführen',
];

// 10. SMTP-Verbindungstest (Socket-Level)
$smtpHost    = (string) env('SMTP_HOST', '');
$smtpPort    = (int)    env('SMTP_PORT', 587);
$smtpOk      = false;
$smtpDetail  = null;
if ($smtpHost !== '') {
    $errno = $errstr = null;
    $sock = @fsockopen($smtpHost, $smtpPort, $errno, $errstr, 5);
    if ($sock) {
        fclose($sock);
        $smtpOk     = true;
        $smtpDetail = "{$smtpHost}:{$smtpPort} erreichbar";
    } else {
        $smtpDetail = "{$smtpHost}:{$smtpPort} – {$errstr} (#{$errno})";
    }
} else {
    $smtpDetail = 'SMTP_HOST nicht konfiguriert';
}
$tests['smtp_connect'] = [
    'label'  => 'SMTP-Server erreichbar',
    'ok'     => $smtpOk,
    'detail' => $smtpDetail,
];

// 11. Schreibrechte tmp/
$tmpDir = __DIR__ . '/tmp';
$tests['tmp_writable'] = [
    'label'  => 'tmp/ Verzeichnis beschreibbar',
    'ok'     => is_dir($tmpDir) && is_writable($tmpDir),
    'detail' => is_dir($tmpDir) ? null : 'tmp/ existiert nicht',
];

// 12. Letzter PHP-Fehler
$lastErr = error_get_last();
$tests['last_error'] = [
    'label'  => 'Kein letzter PHP-Fehler',
    'ok'     => $lastErr === null,
    'detail' => $lastErr ? "[{$lastErr['type']}] {$lastErr['message']} @ {$lastErr['file']}:{$lastErr['line']}" : null,
];

// ── Zusammenfassung ───────────────────────────────────────────────────────────
$totalOk   = count(array_filter($tests, fn($t) => $t['ok']));
$totalFail = count($tests) - $totalOk;
$overallOk = $totalFail === 0;

// ── Geladene .env Keys anzeigen (Passwörter maskiert) ─────────────────────────
$sensitiveKeys = ['DB_PASS', 'SMTP_PASSWORD', 'SMTP_PASS'];
$envDisplay    = [];
foreach (['APP_DEBUG','DB_DRIVER','DB_HOST','DB_NAME','DB_USER','DB_PASS','DB_PORT','DB_CHARSET',
          'SMTP_HOST','SMTP_PORT','SMTP_ENCRYPTION','SMTP_USERNAME','SMTP_PASSWORD','SMTP_FROM','SMTP_FROM_NAME'] as $k) {
    $val = env($k);
    if ($val === null) continue;
    $envDisplay[$k] = in_array($k, $sensitiveKeys, true) ? '••••••••' : (string) $val;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>System-Diagnose | ChrisGaming</title>
  <link rel="shortcut icon" href="/assets/images/cg-logo-neu.png-496x496.png" type="image/x-icon">
  <link rel="stylesheet" href="/assets/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="/assets/css/custom.css">
  <style>
    body { background: #0e0e13; color: #d0d0e0; font-family: 'Golos Text', sans-serif; }
    .diag-header { background: linear-gradient(135deg,#1a1a2e 0%,#16213e 100%);
                   border-bottom: 2px solid var(--cg-red, #e63946); padding: 2rem 0; }
    .diag-card   { background: #15151f; border: 1px solid #2a2a3d; border-radius: .75rem;
                   padding: 1.5rem; margin-bottom: 1.5rem; }
    .diag-card h5 { color: #fff; font-weight: 700; margin-bottom: 1rem;
                    display: flex; align-items: center; gap: .5rem; }
    .diag-card h5 i { color: var(--cg-red, #e63946); }
    .diag-table  { width: 100%; border-collapse: collapse; font-size: .875rem; }
    .diag-table tr + tr { border-top: 1px solid #1e1e2e; }
    .diag-table td, .diag-table th { padding: .55rem .75rem; vertical-align: middle; }
    .diag-table th { color: #8b8b9a; font-weight: 500; width: 55%; }
    .diag-badge  { display: inline-flex; align-items: center; padding: .2rem .65rem;
                   border-radius: 999px; font-size: .78rem; font-weight: 600; }
    .diag-ok     { background: rgba(40,167,69,.15); color: #51cf66; border: 1px solid rgba(40,167,69,.3); }
    .diag-fail   { background: rgba(220,53,69,.15); color: #ff6b6b; border: 1px solid rgba(220,53,69,.3); }
    .diag-warn   { background: rgba(255,193,7,.12); color: #fcc419; border: 1px solid rgba(255,193,7,.25); }
    .diag-detail { color: #8b8b9a; font-size: .78rem; display: block; margin-top: .2rem; }
    .diag-summary-ok   { background: rgba(40,167,69,.1); border: 1px solid rgba(40,167,69,.3); border-radius: .75rem; }
    .diag-summary-fail { background: rgba(220,53,69,.1); border: 1px solid rgba(220,53,69,.3); border-radius: .75rem; }
    .env-key  { color: #9775fa; font-family: monospace; }
    .env-val  { color: #a9e34b; font-family: monospace; }
    .env-mask { color: #555; font-family: monospace; letter-spacing: .1em; }
    .section-divider { color: #2a2a3d; margin: 0; }
  </style>
</head>
<body>

<!-- Header -->
<div class="diag-header">
  <div class="container">
    <div class="d-flex align-items-center gap-3">
      <img src="/assets/images/cg-logo-neu.png-496x496.png" alt="Logo" style="width:48px;height:48px;border-radius:50%;border:2px solid var(--cg-red,#e63946)">
      <div>
        <h1 class="mb-0" style="font-size:1.4rem;font-weight:800;color:#fff;">System-Diagnose</h1>
        <p class="mb-0" style="color:#8b8b9a;font-size:.85rem;">ChrisGaming · <?= date('d.m.Y H:i:s') ?></p>
      </div>
      <div class="ms-auto">
        <?= $overallOk
            ? '<span class="diag-badge diag-ok" style="font-size:.9rem;padding:.4rem 1rem;"><i class="fas fa-circle-check me-1"></i>Alle Tests bestanden</span>'
            : "<span class=\"diag-badge diag-fail\" style=\"font-size:.9rem;padding:.4rem 1rem;\"><i class=\"fas fa-circle-xmark me-1\"></i>{$totalFail} Test(s) fehlgeschlagen</span>"
        ?>
      </div>
    </div>
  </div>
</div>

<div class="container py-4">

  <!-- Zusammenfassung -->
  <div class="diag-card <?= $overallOk ? 'diag-summary-ok' : 'diag-summary-fail' ?> mb-4 p-3">
    <div class="row text-center g-3">
      <div class="col-4">
        <div style="font-size:2rem;font-weight:800;color:<?= $overallOk ? '#51cf66' : '#ff6b6b' ?>"><?= $totalOk ?></div>
        <div style="color:#8b8b9a;font-size:.8rem;">Bestanden</div>
      </div>
      <div class="col-4">
        <div style="font-size:2rem;font-weight:800;color:<?= $totalFail > 0 ? '#ff6b6b' : '#555' ?>"><?= $totalFail ?></div>
        <div style="color:#8b8b9a;font-size:.8rem;">Fehlgeschlagen</div>
      </div>
      <div class="col-4">
        <div style="font-size:2rem;font-weight:800;color:#aaa"><?= count($tests) ?></div>
        <div style="color:#8b8b9a;font-size:.8rem;">Gesamt</div>
      </div>
    </div>
  </div>

  <div class="row g-4">

    <!-- PHP & Extensions -->
    <div class="col-12 col-lg-6">
      <div class="diag-card">
        <h5><i class="fas fa-code"></i> PHP & Extensions</h5>
        <table class="diag-table">
          <?php foreach (['php_version','ext_pdo','ext_pdo_mysql','ext_openssl','ext_mbstring','ext_json','ext_curl','opcache'] as $k): ?>
          <tr>
            <td class="th"><?= htmlspecialchars($tests[$k]['label']) ?></td>
            <td>
              <?= diagBadge($tests[$k]['ok']) ?>
              <?php if (!empty($tests[$k]['detail'])): ?>
                <span class="diag-detail"><?= htmlspecialchars($tests[$k]['detail']) ?></span>
              <?php endif ?>
            </td>
          </tr>
          <?php endforeach ?>
        </table>
      </div>
    </div>

    <!-- Includes -->
    <div class="col-12 col-lg-6">
      <div class="diag-card">
        <h5><i class="fas fa-file-code"></i> PHP-Includes</h5>
        <table class="diag-table">
          <?php foreach (['include_env','include_db','include_logger','include_rate_limit','include_auth'] as $k): ?>
          <tr>
            <td><?= htmlspecialchars($tests[$k]['label']) ?></td>
            <td>
              <?= diagBadge($tests[$k]['ok']) ?>
              <?php if (!empty($tests[$k]['detail'])): ?>
                <span class="diag-detail"><?= htmlspecialchars($tests[$k]['detail']) ?></span>
              <?php endif ?>
            </td>
          </tr>
          <?php endforeach ?>
        </table>
      </div>
    </div>

    <!-- Datenbank -->
    <div class="col-12 col-lg-6">
      <div class="diag-card">
        <h5><i class="fas fa-database"></i> Datenbank</h5>
        <table class="diag-table">
          <?php foreach (['db_connect','db_tables'] as $k): ?>
          <tr>
            <td><?= htmlspecialchars($tests[$k]['label']) ?></td>
            <td>
              <?= diagBadge($tests[$k]['ok']) ?>
              <?php if (!empty($tests[$k]['detail'])): ?>
                <span class="diag-detail"><?= htmlspecialchars($tests[$k]['detail']) ?></span>
              <?php endif ?>
            </td>
          </tr>
          <?php endforeach ?>
        </table>
      </div>
    </div>

    <!-- Sicherheit & Verschlüsselung -->
    <div class="col-12 col-lg-6">
      <div class="diag-card">
        <h5><i class="fas fa-lock"></i> Sicherheit & Verschlüsselung</h5>
        <table class="diag-table">
          <?php foreach (['secret_key','db_pass_encrypted','smtp_pass_encrypted'] as $k): ?>
          <tr>
            <td><?= htmlspecialchars($tests[$k]['label']) ?></td>
            <td>
              <?= $tests[$k]['ok'] ? diagBadge(true) : diagWarn('Klartext!') ?>
              <?php if (!empty($tests[$k]['detail'])): ?>
                <span class="diag-detail"><?= htmlspecialchars($tests[$k]['detail']) ?></span>
              <?php endif ?>
            </td>
          </tr>
          <?php endforeach ?>
        </table>
      </div>
    </div>

    <!-- E-Mail / SMTP -->
    <div class="col-12 col-lg-6">
      <div class="diag-card">
        <h5><i class="fas fa-envelope"></i> E-Mail / SMTP</h5>
        <table class="diag-table">
          <?php foreach (['phpmailer','smtp_connect'] as $k): ?>
          <tr>
            <td><?= htmlspecialchars($tests[$k]['label']) ?></td>
            <td>
              <?= diagBadge($tests[$k]['ok']) ?>
              <?php if (!empty($tests[$k]['detail'])): ?>
                <span class="diag-detail"><?= htmlspecialchars($tests[$k]['detail']) ?></span>
              <?php endif ?>
            </td>
          </tr>
          <?php endforeach ?>
        </table>

        <?php if (!$phpmailerFound): ?>
        <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid #1e1e2e;">
          <button id="btnComposer" onclick="runComposerInstall()"
            style="background:var(--cg-red,#e63946);color:#fff;border:none;border-radius:.5rem;
                   padding:.5rem 1.25rem;font-size:.85rem;font-weight:600;cursor:pointer;
                   display:inline-flex;align-items:center;gap:.5rem;">
            <i class="fas fa-download"></i> composer install ausführen
          </button>
          <pre id="composerOutput"
            style="display:none;margin-top:.75rem;background:#0a0a10;border:1px solid #2a2a3d;
                   border-radius:.5rem;padding:.75rem;font-size:.75rem;color:#a9e34b;
                   max-height:300px;overflow-y:auto;white-space:pre-wrap;"></pre>
        </div>
        <?php endif ?>

        <?php if ($phpmailerFound): ?>
        <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid #1e1e2e;">
          <div class="d-flex align-items-center gap-2 flex-wrap">
            <input type="email" id="testMailRecipient" placeholder="Empfänger-E-Mail"
              style="background:#0e0e13;border:1px solid #2a2a3d;border-radius:.5rem;
                     color:#d0d0e0;padding:.4rem .75rem;font-size:.85rem;flex:1;min-width:200px;">
            <button id="btnTestMail" onclick="sendTestMail()"
              style="background:#1971c2;color:#fff;border:none;border-radius:.5rem;
                     padding:.5rem 1.25rem;font-size:.85rem;font-weight:600;cursor:pointer;
                     display:inline-flex;align-items:center;gap:.5rem;white-space:nowrap;">
              <i class="fas fa-paper-plane"></i> Test-E-Mail senden
            </button>
          </div>
          <pre id="testMailOutput"
            style="display:none;margin-top:.75rem;background:#0a0a10;border:1px solid #2a2a3d;
                   border-radius:.5rem;padding:.75rem;font-size:.75rem;color:#a9e34b;
                   max-height:300px;overflow-y:auto;white-space:pre-wrap;"></pre>
        </div>
        <?php endif ?>

      </div>
    </div>

    <!-- Sonstiges -->
    <div class="col-12 col-lg-6">
      <div class="diag-card">
        <h5><i class="fas fa-gear"></i> Sonstiges</h5>
        <table class="diag-table">
          <?php foreach (['tmp_writable','last_error'] as $k): ?>
          <tr>
            <td><?= htmlspecialchars($tests[$k]['label']) ?></td>
            <td>
              <?= diagBadge($tests[$k]['ok']) ?>
              <?php if (!empty($tests[$k]['detail'])): ?>
                <span class="diag-detail"><?= htmlspecialchars($tests[$k]['detail']) ?></span>
              <?php endif ?>
            </td>
          </tr>
          <?php endforeach ?>
        </table>
      </div>
    </div>

    <!-- .env Konfiguration -->
    <div class="col-12">
      <div class="diag-card">
        <h5><i class="fas fa-sliders"></i> Geladene .env Konfiguration <small style="color:#555;font-weight:400;font-size:.75rem;">(Passwörter maskiert)</small></h5>
        <table class="diag-table">
          <?php foreach ($envDisplay as $k => $v): ?>
          <tr>
            <td><span class="env-key"><?= htmlspecialchars($k) ?></span></td>
            <td>
              <?php if (in_array($k, $sensitiveKeys, true)): ?>
                <span class="env-mask"><?= $v ?></span>
              <?php else: ?>
                <span class="env-val"><?= htmlspecialchars($v) ?></span>
              <?php endif ?>
            </td>
          </tr>
          <?php endforeach ?>
        </table>
      </div>
    </div>

  </div><!-- /row -->

  <p style="color:#333;font-size:.75rem;text-align:center;margin-top:1rem;">
    Diese Seite ist nur über <code>?key=cg_debug_2026</code> erreichbar. Nicht verlinken.
  </p>

</div><!-- /container -->

<script src="/assets/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
function runComposerInstall() {
    const btn = document.getElementById('btnComposer');
    const out = document.getElementById('composerOutput');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Läuft…';
    out.style.display = 'block';
    out.textContent   = 'composer install wird ausgeführt…\n';

    const params = new URLSearchParams();
    params.append('action', 'composer_install');

    fetch('?key=<?= urlencode($_GET['key'] ?? '') ?>', {
        method : 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body   : params.toString(),
    })
    .then(r => r.text())
    .then(text => {
        out.textContent = text;
        const ok = text.includes('✓');
        btn.innerHTML = ok
            ? '<i class="fas fa-check me-1"></i> Erfolgreich – Seite neu laden'
            : '<i class="fas fa-times me-1"></i> Fehler – siehe Output';
        btn.style.background = ok ? '#2f9e44' : '#c92a2a';
        btn.disabled = false;
        if (ok) btn.onclick = () => location.reload();
    })
    .catch(err => {
        out.textContent = 'Netzwerkfehler: ' + err;
        btn.innerHTML = '<i class="fas fa-times me-1"></i> Fehler';
        btn.disabled  = false;
    });
}

function sendTestMail() {
    const btn       = document.getElementById('btnTestMail');
    const out       = document.getElementById('testMailOutput');
    const recipient = document.getElementById('testMailRecipient').value.trim();

    if (!recipient) {
        alert('Bitte eine Empfänger-E-Mail-Adresse eingeben.');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Wird gesendet…';
    out.style.display = 'block';
    out.textContent   = 'Verbinde mit SMTP-Server…\n';

    const params = new URLSearchParams();
    params.append('action', 'send_test_mail');
    params.append('recipient', recipient);

    fetch('?key=<?= urlencode($_GET['key'] ?? '') ?>', {
        method : 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body   : params.toString(),
    })
    .then(r => r.text())
    .then(text => {
        out.textContent = text;
        const ok = text.includes('✓');
        btn.innerHTML = ok
            ? '<i class="fas fa-check me-1"></i> E-Mail gesendet'
            : '<i class="fas fa-times me-1"></i> Fehler – siehe Output';
        btn.style.background = ok ? '#2f9e44' : '#c92a2a';
        btn.disabled = false;
    })
    .catch(err => {
        out.textContent = 'Netzwerkfehler: ' + err;
        btn.innerHTML = '<i class="fas fa-times me-1"></i> Fehler';
        btn.disabled  = false;
    });
}
</script>
</body>
</html>

