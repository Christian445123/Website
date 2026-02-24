<?php
/**
 * CG Activity Logger
 * ------------------------------------------
 * Loggt alle sicherheits- und benutzerrelevanten Events:
 *   1. In die Datenbank  → Tabelle `activity_log`
 *   2. Als Discord Embed → DISCORD_LOG_WEBHOOK in .env
 *
 * Konfiguration in .env:
 *   DISCORD_LOG_WEBHOOK=https://discord.com/api/webhooks/XXX/YYY
 *   DISCORD_LOG_LEVEL=info       (debug | info | warning | critical)
 *
 * Nutzung:
 *   cg_log(LOG_LOGIN_SUCCESS);
 *   cg_log(LOG_LOGIN_FAILED, ['username' => $username]);
 *   cg_log(LOG_ADMIN_USER_DELETED, ['target_id' => $uid, 'target_user' => $name], webhook: true);
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';   // lädt auch env.php

// ═══════════════════════════════════════════════════════════════
//  EVENT-KONSTANTEN
// ═══════════════════════════════════════════════════════════════

// ── Auth ────────────────────────────────────────────────────────
const LOG_LOGIN_SUCCESS        = 'login.success';
const LOG_LOGIN_FAILED         = 'login.failed';
const LOG_LOGIN_UNVERIFIED     = 'login.unverified';
const LOG_LOGIN_INACTIVE       = 'login.inactive';
const LOG_LOGOUT               = 'logout';

// ── Registrierung ───────────────────────────────────────────────
const LOG_REGISTER_SUCCESS     = 'register.success';
const LOG_REGISTER_EMAIL_TAKEN = 'register.email_taken';
const LOG_REGISTER_FAILED      = 'register.failed';

// ── E-Mail-Verifizierung ────────────────────────────────────────
const LOG_VERIFY_SUCCESS       = 'verify.success';
const LOG_VERIFY_FAILED        = 'verify.failed';
const LOG_VERIFY_EXPIRED       = 'verify.expired';
const LOG_VERIFY_ALREADY       = 'verify.already';

// ── Sicherheit ──────────────────────────────────────────────────
const LOG_RATE_LIMIT_HIT       = 'rate_limit.hit';
const LOG_ACCESS_DENIED        = 'access.denied';
const LOG_SESSION_EXPIRED      = 'session.expired';

// ── Admin – Benutzerverwaltung ──────────────────────────────────
const LOG_ADMIN_USER_ACTIVATED   = 'admin.user.activated';
const LOG_ADMIN_USER_DEACTIVATED = 'admin.user.deactivated';
const LOG_ADMIN_USER_ROLE        = 'admin.user.role_changed';
const LOG_ADMIN_USER_DELETED     = 'admin.user.deleted';
const LOG_ADMIN_USER_VERIFIED    = 'admin.user.verified';

// ── Admin – Projekte ────────────────────────────────────────────
const LOG_ADMIN_PROJECT_CREATED  = 'admin.project.created';
const LOG_ADMIN_PROJECT_UPDATED  = 'admin.project.updated';
const LOG_ADMIN_PROJECT_DELETED  = 'admin.project.deleted';
const LOG_ADMIN_PROJECT_TOGGLED  = 'admin.project.toggled';

// ── Admin – Ankündigungen ───────────────────────────────────────
const LOG_ADMIN_ANN_CREATED      = 'admin.announcement.created';
const LOG_ADMIN_ANN_UPDATED      = 'admin.announcement.updated';
const LOG_ADMIN_ANN_DELETED      = 'admin.announcement.deleted';
const LOG_ADMIN_ANN_TOGGLED      = 'admin.announcement.toggled';

// ── PHP-Laufzeitfehler ─────────────────────────────────────────
const LOG_PHP_FATAL              = 'php.fatal';       // E_ERROR, parse, core, compile
const LOG_PHP_EXCEPTION          = 'php.exception';   // Uncaught Throwable
const LOG_PHP_ERROR              = 'php.error';       // E_USER_ERROR
const LOG_PHP_WARNING            = 'php.warning';     // E_WARNING, E_CORE_WARNING, E_USER_WARNING
const LOG_PHP_NOTICE             = 'php.notice';      // E_NOTICE, E_USER_NOTICE
const LOG_PHP_DEPRECATED         = 'php.deprecated';  // E_DEPRECATED, E_USER_DEPRECATED
const LOG_PHP_STRICT             = 'php.strict';      // E_STRICT

// ═══════════════════════════════════════════════════════════════
//  SEVERITY & DISCORD-METADATEN
// ═══════════════════════════════════════════════════════════════

/**
 * Gibt Level, Embed-Farbe, Emoji und Anzeigetext pro Event zurück.
 *
 * @return array{level:string, color:int, emoji:string, label:string}
 */
function cg_log_meta(string $event): array
{
    return match (true) {
        // ── Kritisch (rot) ─────────────────────────────────────
        $event === LOG_RATE_LIMIT_HIT
            => ['level' => 'critical', 'color' => 0xE03131, 'emoji' => '🚫',  'label' => 'Rate Limit überschritten'],
        $event === LOG_ACCESS_DENIED
            => ['level' => 'critical', 'color' => 0xE03131, 'emoji' => '⛔',  'label' => 'Zugriff verweigert (403)'],
        $event === LOG_ADMIN_USER_DELETED
            => ['level' => 'critical', 'color' => 0xE03131, 'emoji' => '🗑️', 'label' => 'Benutzer gelöscht'],
        $event === LOG_LOGIN_INACTIVE
            => ['level' => 'critical', 'color' => 0xE03131, 'emoji' => '🔴',  'label' => 'Login: Account gesperrt'],

        // ── Warnung (gelb) ─────────────────────────────────────
        $event === LOG_LOGIN_FAILED
            => ['level' => 'warning',  'color' => 0xFAB005, 'emoji' => '⚠️', 'label' => 'Login fehlgeschlagen'],
        $event === LOG_LOGIN_UNVERIFIED
            => ['level' => 'warning',  'color' => 0xFAB005, 'emoji' => '📧', 'label' => 'Login: E-Mail nicht verifiziert'],
        $event === LOG_SESSION_EXPIRED
            => ['level' => 'warning',  'color' => 0xFAB005, 'emoji' => '⏱️', 'label' => 'Session abgelaufen / Account gesperrt'],
        $event === LOG_VERIFY_EXPIRED
            => ['level' => 'warning',  'color' => 0xFAB005, 'emoji' => '⌛', 'label' => 'Verifizierungs-Token abgelaufen'],
        $event === LOG_VERIFY_FAILED
            => ['level' => 'warning',  'color' => 0xFAB005, 'emoji' => '🔑', 'label' => 'Ungültiger Verifizierungs-Token'],
        $event === LOG_REGISTER_EMAIL_TAKEN
            => ['level' => 'warning',  'color' => 0xFAB005, 'emoji' => '📋', 'label' => 'Registrierung: E-Mail bereits vergeben'],
        $event === LOG_REGISTER_FAILED
            => ['level' => 'warning',  'color' => 0xFAB005, 'emoji' => '❌', 'label' => 'Registrierung fehlgeschlagen'],

        // ── Positiv (grün) ─────────────────────────────────────
        $event === LOG_LOGIN_SUCCESS
            => ['level' => 'info',     'color' => 0x2F9E44, 'emoji' => '✅', 'label' => 'Login erfolgreich'],
        $event === LOG_LOGOUT
            => ['level' => 'info',     'color' => 0x2F9E44, 'emoji' => '👋', 'label' => 'Logout'],
        $event === LOG_VERIFY_SUCCESS
            => ['level' => 'info',     'color' => 0x2F9E44, 'emoji' => '✉️', 'label' => 'E-Mail erfolgreich verifiziert'],
        $event === LOG_VERIFY_ALREADY
            => ['level' => 'info',     'color' => 0x5865F2, 'emoji' => 'ℹ️', 'label' => 'E-Mail bereits verifiziert'],

        // ── Registrierung (pink) ───────────────────────────────
        $event === LOG_REGISTER_SUCCESS
            => ['level' => 'info',     'color' => 0xCC5DE8, 'emoji' => '🆕', 'label' => 'Neuer Benutzer registriert'],

        // ── Admin Aktionen (blau) ──────────────────────────────
        $event === LOG_ADMIN_USER_ACTIVATED
            => ['level' => 'info',     'color' => 0x5865F2, 'emoji' => '🔓', 'label' => 'Admin: Benutzer aktiviert'],
        $event === LOG_ADMIN_USER_DEACTIVATED
            => ['level' => 'warning',  'color' => 0xFAB005, 'emoji' => '🔒', 'label' => 'Admin: Benutzer deaktiviert'],
        $event === LOG_ADMIN_USER_ROLE
            => ['level' => 'info',     'color' => 0x5865F2, 'emoji' => '🎭', 'label' => 'Admin: Rolle geändert'],
        $event === LOG_ADMIN_USER_VERIFIED
            => ['level' => 'info',     'color' => 0x5865F2, 'emoji' => '✔️', 'label' => 'Admin: E-Mail manuell verifiziert'],
        $event === LOG_ADMIN_PROJECT_CREATED
            => ['level' => 'info',     'color' => 0x5865F2, 'emoji' => '📁', 'label' => 'Admin: Projekt erstellt'],
        $event === LOG_ADMIN_PROJECT_UPDATED
            => ['level' => 'info',     'color' => 0x5865F2, 'emoji' => '✏️', 'label' => 'Admin: Projekt bearbeitet'],
        $event === LOG_ADMIN_PROJECT_DELETED
            => ['level' => 'critical', 'color' => 0xE03131, 'emoji' => '🗑️', 'label' => 'Admin: Projekt gelöscht'],
        $event === LOG_ADMIN_PROJECT_TOGGLED
            => ['level' => 'info',     'color' => 0x5865F2, 'emoji' => '👁️', 'label' => 'Admin: Projekt-Sichtbarkeit geändert'],
        $event === LOG_ADMIN_ANN_CREATED
            => ['level' => 'info',     'color' => 0x5865F2, 'emoji' => '📰', 'label' => 'Admin: Ankündigung erstellt'],
        $event === LOG_ADMIN_ANN_UPDATED
            => ['level' => 'info',     'color' => 0x5865F2, 'emoji' => '✏️', 'label' => 'Admin: Ankündigung bearbeitet'],
        $event === LOG_ADMIN_ANN_DELETED
            => ['level' => 'critical', 'color' => 0xE03131, 'emoji' => '🗑️', 'label' => 'Admin: Ankündigung gelöscht'],
        $event === LOG_ADMIN_ANN_TOGGLED
            => ['level' => 'info',     'color' => 0x5865F2, 'emoji' => '👁️', 'label' => 'Admin: Ankündigung-Sichtbarkeit geändert'],

        // ── PHP-Laufzeitfehler ─────────────────────────────────
        $event === LOG_PHP_FATAL
            => ['level' => 'critical', 'color' => 0xFF0000, 'emoji' => '💀', 'label' => 'PHP Fatal Error'],
        $event === LOG_PHP_EXCEPTION
            => ['level' => 'critical', 'color' => 0xE03131, 'emoji' => '💥', 'label' => 'Unbehandelte Exception'],
        $event === LOG_PHP_ERROR
            => ['level' => 'critical', 'color' => 0xE03131, 'emoji' => '🔥', 'label' => 'PHP Error'],
        $event === LOG_PHP_WARNING
            => ['level' => 'warning',  'color' => 0xFAB005, 'emoji' => '⚠️', 'label' => 'PHP Warning'],
        $event === LOG_PHP_NOTICE
            => ['level' => 'debug',    'color' => 0x868E96, 'emoji' => '📌', 'label' => 'PHP Notice'],
        $event === LOG_PHP_DEPRECATED
            => ['level' => 'debug',    'color' => 0x868E96, 'emoji' => '🕰️', 'label' => 'PHP Deprecated'],
        $event === LOG_PHP_STRICT
            => ['level' => 'debug',    'color' => 0x868E96, 'emoji' => '📐', 'label' => 'PHP Strict'],

        // ── Fallback ───────────────────────────────────────────
        default
            => ['level' => 'info',     'color' => 0x868E96, 'emoji' => '📝', 'label' => $event],
    };
}
// ═══════════════════════════════════════════════════════════════
//  HAUPT-LOG-FUNKTION
// ═══════════════════════════════════════════════════════════════

/**
 * Erstellt einen Activity-Log-Eintrag.
 *
 * @param string   $event    Einer der LOG_* Konstanten
 * @param array    $context  Beliebige Zusatzdaten (werden als JSON gespeichert)
 * @param int|null $userId   User-ID (null = aus Session lesen)
 * @param string|null $ip    IP-Adresse (null = auto-detect)
 * @param bool     $webhook  Discord Webhook senden?
 */
function cg_log(
    string  $event,
    array   $context  = [],
    ?int    $userId   = null,
    ?string $ip       = null,
    bool    $webhook  = true
): void {
    // Grundwerte ermitteln
    $ip        ??= cg_log_get_ip();
    $userAgent   = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 512);
    $ipHash      = hash('sha256', $ip);
    $contextJson = count($context) > 0
        ? json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        : null;

    // User-ID aus Session ableiten wenn nicht explizit übergeben
    if ($userId === null && isset($_SESSION['user_id'])) {
        $userId = (int) $_SESSION['user_id'];
    }

    // 1) Datenbank-Log ───────────────────────────────────────────
    try {
        $pdo = db();
        if ($pdo !== null) {
            $pdo->prepare(
                'INSERT INTO `activity_log`
                    (`event`, `user_id`, `ip`, `ip_hash`, `user_agent`, `context`)
                 VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([
                $event,
                $userId,
                $ip,
                $ipHash,
                $userAgent ?: null,
                $contextJson,
            ]);
        }
    } catch (Throwable $e) {
        error_log('[CG-LOG] DB error: ' . $e->getMessage());
    }

    // 2) Discord Webhook ─────────────────────────────────────────
    if ($webhook) {
        cg_log_discord($event, $context, $userId, $ip);
    }
}

// ═══════════════════════════════════════════════════════════════
//  DISCORD WEBHOOK
// ═══════════════════════════════════════════════════════════════

function cg_log_discord(string $event, array $context, ?int $userId, string $ip): void
{
    $webhookUrl = (string) env('DISCORD_LOG_WEBHOOK', '');
    if ($webhookUrl === '') {
        return; // Webhook nicht konfiguriert
    }

    $meta    = cg_log_meta($event);
    $minLvl  = (string) env('DISCORD_LOG_LEVEL', 'info');
    $lvlMap  = ['debug' => 0, 'info' => 1, 'warning' => 2, 'critical' => 3];
    $eventLvl = $lvlMap[$meta['level']] ?? 1;
    $minLvlVal = $lvlMap[$minLvl] ?? 1;

    if ($eventLvl < $minLvlVal) {
        return; // Unter dem konfigurierten Mindest-Level
    }

    // Embed-Fields aufbauen
    $fields = [
        ['name' => '🔍 Event',      'value' => '`' . $event . '`',  'inline' => true],
        ['name' => '🌐 IP-Adresse', 'value' => '`' . $ip . '`',     'inline' => true],
        ['name' => '📅 Zeit',       'value' => '`' . date('d.m.Y H:i:s') . '`', 'inline' => true],
    ];

    if ($userId !== null) {
        $fields[] = ['name' => '👤 User-ID', 'value' => '`' . $userId . '`', 'inline' => true];
    }

    // Kontextfelder einzeln als Discord-Fields
    foreach ($context as $key => $value) {
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        $fields[] = [
            'name'   => ucfirst(str_replace('_', ' ', (string) $key)),
            'value'  => '`' . mb_substr((string) $value, 0, 1000) . '`',
            'inline' => true,
        ];
    }

    $payload = [
        'username'   => 'CG Security Bot',
        'avatar_url' => 'https://chrisgaming.at/assets/images/cg-logo-neu.png-496x496.png',
        'embeds'     => [[
            'title'     => $meta['emoji'] . '  ' . $meta['label'],
            'color'     => $meta['color'],
            'fields'    => $fields,
            'footer'    => [
                'text'     => 'chrisgaming.at  •  Activity Log',
                'icon_url' => 'https://chrisgaming.at/assets/images/cg-logo-neu.png-496x496.png',
            ],
            'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
        ]],
    ];

    // Nicht-blockierendes HTTP POST über stream context
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $ctx  = stream_context_create([
        'http' => [
            'method'        => 'POST',
            'header'        => "Content-Type: application/json\r\n"
                             . 'Content-Length: ' . strlen($json) . "\r\n",
            'content'       => $json,
            'timeout'       => 3,
            'ignore_errors' => true,
        ],
    ]);

    try {
        @file_get_contents($webhookUrl, false, $ctx);
    } catch (Throwable) {
        // Nicht-kritisch – stilles Ignorieren
    }
}

// ═══════════════════════════════════════════════════════════════
//  HILFSFUNKTIONEN
// ═══════════════════════════════════════════════════════════════

/**
 * Ermittelt die echte IP des Besuchers (Cloudflare, Proxies, etc.).
 */
function cg_log_get_ip(): string
{
    $candidates = [
        'HTTP_CF_CONNECTING_IP',   // Cloudflare
        'HTTP_X_REAL_IP',
        'HTTP_X_FORWARDED_FOR',
        'REMOTE_ADDR',
    ];
    foreach ($candidates as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = trim(explode(',', $_SERVER[$key])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return '0.0.0.0';
}

// ═══════════════════════════════════════════════════════════════
//  PHP ERROR / EXCEPTION / SHUTDOWN HANDLER
// ═══════════════════════════════════════════════════════════════

/**
 * Wandelt eine PHP-Fehlernummer (E_*) in die passende LOG_PHP_* Konstante um.
 */
function cg_php_errno_to_event(int $errno): string
{
    return match (true) {
        (bool) ($errno & (E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_PARSE))
            => LOG_PHP_FATAL,
        (bool) ($errno & E_USER_ERROR)
            => LOG_PHP_ERROR,
        (bool) ($errno & (E_WARNING | E_CORE_WARNING | E_COMPILE_WARNING | E_USER_WARNING))
            => LOG_PHP_WARNING,
        (bool) ($errno & (E_DEPRECATED | E_USER_DEPRECATED))
            => LOG_PHP_DEPRECATED,
        (bool) ($errno & E_STRICT)
            => LOG_PHP_STRICT,
        default
            => LOG_PHP_NOTICE,
    };
}

/**
 * Kürzt einen Dateipfad auf den Teil ab DOCUMENT_ROOT.
 */
function cg_log_short_path(string $path): string
{
    $root = rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
    if ($root !== '' && str_starts_with($path, $root)) {
        return substr($path, strlen($root));
    }
    return $path;
}

/**
 * set_error_handler – fängt alle PHP Warnings, Notices, Deprecations, etc.
 * Gibt false zurück damit PHPs interner Handler ebenfalls läuft (error_log).
 */
function cg_php_error_handler(int $errno, string $errstr, string $errfile = '', int $errline = 0): bool
{
    static $guard = false;
    if ($guard) {
        return false;
    }
    $guard = true;

    // Fehler die durch @ (Silence-Operator) unterdrückt wurden, ignorieren
    if (!(error_reporting() & $errno)) {
        $guard = false;
        return false;
    }

    $event = cg_php_errno_to_event($errno);

    cg_log($event, [
        'message' => mb_substr($errstr, 0, 500),
        'file'    => cg_log_short_path($errfile),
        'line'    => $errline,
        'errno'   => $errno,
        'url'     => $_SERVER['REQUEST_URI'] ?? '',
    ], null, null, webhook: true);

    $guard = false;
    return false; // PHPs Standard-Error-Log ebenfalls ausführen
}

/**
 * set_exception_handler – fängt alle unbehandelten Exceptions/Throwables.
 */
function cg_php_exception_handler(Throwable $e): void
{
    static $guard = false;
    if ($guard) {
        return;
    }
    $guard = true;

    cg_log(LOG_PHP_EXCEPTION, [
        'class'   => get_class($e),
        'message' => mb_substr($e->getMessage(), 0, 500),
        'file'    => cg_log_short_path($e->getFile()),
        'line'    => $e->getLine(),
        'code'    => $e->getCode(),
        'trace'   => mb_substr($e->getTraceAsString(), 0, 1500),
        'url'     => $_SERVER['REQUEST_URI'] ?? '',
    ], null, null, webhook: true);

    $guard = false;

    // Standard-Ausgabe (weiße Seite statt leerem Screen)
    if (!headers_sent()) {
        http_response_code(500);
    }
    if ((string) env('APP_DEBUG', '') === 'true') {
        echo '<pre style="background:#1a1a1a;color:#ff6b6b;padding:2rem;font-size:.85rem">';
        echo htmlspecialchars((string) $e);
        echo '</pre>';
    }
}

/**
 * register_shutdown_function – fängt fatale Fehler, die set_error_handler nicht erreicht.
 */
function cg_php_shutdown_handler(): void
{
    $err = error_get_last();
    if ($err === null) {
        return;
    }

    // Nur echte fatale Fehler
    $fatalMask = E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR;
    if (!($err['type'] & $fatalMask)) {
        return;
    }

    static $guard = false;
    if ($guard) {
        return;
    }
    $guard = true;

    cg_log(LOG_PHP_FATAL, [
        'message' => mb_substr($err['message'], 0, 500),
        'file'    => cg_log_short_path($err['file']),
        'line'    => $err['line'],
        'errno'   => $err['type'],
        'url'     => $_SERVER['REQUEST_URI'] ?? '',
    ], null, null, webhook: true);
}

/**
 * Registriert alle drei PHP-Error-Handler.
 * Wird einmalig beim Include von logger.php automatisch aufgerufen.
 */
function cg_register_error_handlers(): void
{
    // Alle Fehler PHP-intern sammeln, aber nicht ausgeben (Ausgabe via Logger)
    error_reporting(E_ALL);
    ini_set('display_errors', '0');   // nie an den Browser schicken
    ini_set('log_errors',     '1');   // immer in PHP error_log schreiben

    set_error_handler('cg_php_error_handler');
    set_exception_handler('cg_php_exception_handler');
    register_shutdown_function('cg_php_shutdown_handler');
}

// ── Auto-Register on include ─────────────────────────────────────
cg_register_error_handlers();
