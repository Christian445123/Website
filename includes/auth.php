<?php
/**
 * Authentication Helper
 * ---------------------
 * Session-Management, Login/Logout, Registrierung, Passwortwechsel und
 * Zugriffs-Guards.
 *
 * Usage:
 *   require_once __DIR__ . '/auth.php';
 *
 *   auth_require_login();          // leitet auf login.php um
 *   auth_require_role('admin');    // 403 bei fehlender Rolle
 *   if (auth_check()) { … }
 *   $user = auth_user();           // array aus der DB oder null
 *
 * Sicherheitsmerkmale:
 *   - __Host- Session-Cookie (Secure, HttpOnly, SameSite=Strict), Strict-Mode
 *   - Idle- (2 h) und absolutes Session-Timeout (12 h), regelmäßige ID-Rotation
 *   - Rolle/Status werden bei jedem Request frisch aus der DB gelesen
 *   - Login-Throttling pro IP und Benutzername, Registrierungs-Limit pro IP
 *   - bcrypt (Cost 12) mit automatischem Rehash, Timing-sicherer Dummy-Vergleich
 *   - Verifizierungs-Token werden nur als SHA-256-Hash gespeichert
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/logger.php'; // Activity Logger
require_once __DIR__ . '/i18n.php';   // cg_url(), cg_lang()

const CG_SESSION_IDLE   = 7200;   // 2 Stunden Inaktivität
const CG_SESSION_MAX    = 43200;  // 12 Stunden absolut
const CG_SESSION_ROTATE = 900;    // Session-ID alle 15 Minuten erneuern

const CG_LOGIN_WINDOW        = 900;   // 15 Minuten
const CG_LOGIN_MAX_PER_IP    = 10;
const CG_LOGIN_MAX_PER_USER  = 25;
const CG_REGISTER_WINDOW     = 3600;  // 1 Stunde
const CG_REGISTER_MAX_PER_IP = 3;

const CG_BCRYPT_COST    = 12;
const CG_PASSWORD_MIN   = 12;
/** Gültiger bcrypt-Hash eines Zufallswerts – für den Timing-sicheren Dummy-Vergleich. */
const CG_DUMMY_HASH     = '$2y$12$xqOe2KPPfrqrVICiD0R3TumuT3b0Y8Tiu0nfSYGGr5AvT0oKKLfMq';

// ── Session bootstrap ─────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE && PHP_SAPI !== 'cli') {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    ini_set('session.gc_maxlifetime', (string) CG_SESSION_IDLE);

    session_name('__Host-cgsid');
    session_set_cookie_params([
        'lifetime' => 0,           // bis Browser geschlossen wird
        'path'     => '/',
        'secure'   => true,        // nur HTTPS (Voraussetzung für __Host-)
        'httponly' => true,        // kein JS-Zugriff
        'samesite' => 'Strict',
    ]);
    session_start();

    $now = time();
    if (isset($_SESSION['user_id'])) {
        $idleFor = $now - (int) ($_SESSION['_last'] ?? $now);
        $age     = $now - (int) ($_SESSION['_born'] ?? $now);
        if ($idleFor > CG_SESSION_IDLE || $age > CG_SESSION_MAX) {
            $_SESSION = [];
            session_regenerate_id(true);
        } elseif ($now - (int) ($_SESSION['_rotated'] ?? 0) > CG_SESSION_ROTATE) {
            session_regenerate_id(true);
            $_SESSION['_rotated'] = $now;
        }
    }
    $_SESSION['_last'] = $now;
    $_SESSION['_born'] ??= $now;
}

// ── Public helpers ────────────────────────────────────────────────

/**
 * Returns true if a (still valid, active) user is currently logged in.
 */
function auth_check(): bool
{
    return auth_user() !== null;
}

/**
 * Returns the current user's data. Wird pro Request einmal frisch aus der DB
 * gelesen, damit Rollenänderungen/Sperren sofort greifen.
 * Null if not logged in (oder User gesperrt/gelöscht).
 *
 * @return array{id:int,username:string,email:string|null,role:string}|null
 */
function auth_user(): ?array
{
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    if (array_key_exists('__cg_user', $GLOBALS)) {
        return $GLOBALS['__cg_user'];
    }

    $pdo = db();
    if ($pdo === null) {
        return null; // fail closed
    }

    try {
        $stmt = $pdo->prepare(
            'SELECT `id`, `username`, `email`, `role`
             FROM `users`
             WHERE `id` = ? AND `is_active` = 1 AND `email_verified` = 1
             LIMIT 1'
        );
        $stmt->execute([(int) $_SESSION['user_id']]);
        $row = $stmt->fetch();
    } catch (Throwable) {
        return null;
    }

    if ($row === false) {
        // User was deleted / deactivated – force logout
        cg_log(LOG_SESSION_EXPIRED, ['user_id' => $_SESSION['user_id'] ?? null]);
        auth_logout();
        $GLOBALS['__cg_user'] = null;
        return null;
    }

    return $GLOBALS['__cg_user'] = $row;
}

/** true, wenn der eingeloggte User Admin ist. */
function auth_is_admin(): bool
{
    $u = auth_user();
    return $u !== null && ($u['role'] ?? '') === 'admin';
}

/**
 * Redirect to login page if visitor is not authenticated.
 *
 * @param string $redirect  Page to go back to after login (default: current URL)
 */
function auth_require_login(string $redirect = ''): void
{
    if (auth_check()) {
        return;
    }

    $back = cg_safe_redirect($redirect !== '' ? $redirect : ($_SERVER['REQUEST_URI'] ?? '/'));
    header('Location: ' . cg_url('/login.php') . '?redirect=' . urlencode($back));
    exit;
}

/**
 * Abort with 403 if the current user does not have the required role.
 * Calls auth_require_login() first (unauthenticated → 302 redirect).
 *
 * Role hierarchy: admin > moderator > user
 */
function auth_require_role(string $requiredRole): void
{
    auth_require_login();

    $hierarchy = ['user' => 0, 'moderator' => 1, 'admin' => 2];
    $user      = auth_user();
    $userLevel = $hierarchy[$user['role'] ?? ''] ?? -1;
    $required  = $hierarchy[$requiredRole]  ?? 99;

    if ($userLevel < $required) {
        cg_log(LOG_ACCESS_DENIED, [
            'required_role' => $requiredRole,
            'user_role'     => $user['role'] ?? 'unknown',
            'url'           => mb_substr((string) ($_SERVER['REQUEST_URI'] ?? ''), 0, 300),
        ]);
        http_response_code(403);
        exit(
            '<!DOCTYPE html><html lang="de"><head><meta charset="UTF-8"><title>403</title></head>' .
            '<body style="font-family:sans-serif;text-align:center;padding:4rem;background:#090a0c;color:#eaeaef">' .
            '<h1>403 &ndash; Zugriff verweigert</h1>' .
            '<p>Du hast keine Berechtigung, diese Seite aufzurufen.</p>' .
            '</body></html>'
        );
    }
}

// ── Throttling (login / register) ────────────────────────────────

function auth_hash(string $value): string
{
    return hash('sha256', strtolower(trim($value)));
}

function auth_attempt_record(string $kind, string $subject = ''): void
{
    try {
        db()?->prepare(
            'INSERT INTO `auth_attempts` (`kind`, `ip_hash`, `subject_hash`, `attempted_at`) VALUES (?, ?, ?, ?)'
        )->execute([$kind, auth_hash(cg_client_ip()), $subject === '' ? '' : auth_hash($subject), time()]);

        // gelegentlich aufräumen
        if (random_int(1, 50) === 1) {
            db()?->prepare('DELETE FROM `auth_attempts` WHERE `attempted_at` < ?')->execute([time() - 86400]);
        }
    } catch (Throwable $e) {
        error_log('[CG] auth_attempt_record: ' . $e->getMessage());
    }
}

function auth_attempt_count(string $kind, string $column, string $hash, int $window): int
{
    if (!in_array($column, ['ip_hash', 'subject_hash'], true)) {
        return 0;
    }
    try {
        $stmt = db()?->prepare(
            "SELECT COUNT(*) FROM `auth_attempts` WHERE `kind` = ? AND `{$column}` = ? AND `attempted_at` > ?"
        );
        if ($stmt === null) {
            return 0;
        }
        $stmt->execute([$kind, $hash, time() - $window]);
        return (int) $stmt->fetchColumn();
    } catch (Throwable) {
        return 0;
    }
}

function auth_attempts_clear(string $kind, string $subject): void
{
    try {
        db()?->prepare('DELETE FROM `auth_attempts` WHERE `kind` = ? AND `subject_hash` = ?')
            ->execute([$kind, auth_hash($subject)]);
    } catch (Throwable) {
        // non-critical
    }
}

/**
 * Attempt to log a user in by username + password.
 *
 * Returns true on success (session is populated),
 * or a string error message on failure.
 *
 * @return true|string
 */
function auth_login(string $username, string $password): bool|string
{
    $username = trim($username);
    if ($username === '' || $password === '' || mb_strlen($username) > 64 || strlen($password) > 1024) {
        return 'Benutzername oder Passwort falsch.';
    }

    $pdo = db();
    if ($pdo === null) {
        return 'Datenbankfehler. Bitte später erneut versuchen.';
    }

    // ── Brute-Force-Schutz ────────────────────────────────────────
    $ipHash   = auth_hash(cg_client_ip());
    $userHash = auth_hash($username);
    if (
        auth_attempt_count('login', 'ip_hash',      $ipHash,   CG_LOGIN_WINDOW) >= CG_LOGIN_MAX_PER_IP ||
        auth_attempt_count('login', 'subject_hash', $userHash, CG_LOGIN_WINDOW) >= CG_LOGIN_MAX_PER_USER
    ) {
        cg_log(LOG_LOGIN_THROTTLED, ['username' => mb_substr($username, 0, 64)]);
        return 'Zu viele Fehlversuche. Bitte warte 15 Minuten und versuche es dann erneut.';
    }

    try {
        $stmt = $pdo->prepare(
            'SELECT `id`, `password_hash`, `role`, `is_active`, `email_verified`
             FROM `users`
             WHERE `username` = ?
             LIMIT 1'
        );
        $stmt->execute([$username]);
        $row = $stmt->fetch();
    } catch (Throwable) {
        return 'Datenbankfehler. Bitte später erneut versuchen.';
    }

    // Immer genau einen bcrypt-Vergleich durchführen (kein Timing-Orakel für Benutzernamen)
    $hash  = $row !== false ? (string) $row['password_hash'] : CG_DUMMY_HASH;
    $valid = password_verify($password, $hash);

    if ($row === false || !$valid) {
        auth_attempt_record('login', $username);
        cg_log(
            LOG_LOGIN_FAILED,
            ['username' => mb_substr($username, 0, 64), 'reason' => $row === false ? 'user_not_found' : 'wrong_password'],
            $row === false ? null : (int) $row['id']
        );
        return 'Benutzername oder Passwort falsch.';
    }

    if (!(bool) $row['email_verified']) {
        cg_log(LOG_LOGIN_UNVERIFIED, ['username' => $username], (int) $row['id']);
        return 'Bitte bestätige zuerst deine E-Mail-Adresse. Schau in dein Postfach!';
    }

    if (!(bool) $row['is_active']) {
        cg_log(LOG_LOGIN_INACTIVE, ['username' => $username], (int) $row['id']);
        return 'Dein Account ist deaktiviert. Bitte kontaktiere uns.';
    }

    // Regenerate session ID to prevent fixation; alten Session-Inhalt verwerfen
    session_regenerate_id(true);
    $_SESSION = [
        'user_id'  => (int) $row['id'],
        '_born'    => time(),
        '_last'    => time(),
        '_rotated' => time(),
    ];
    unset($GLOBALS['__cg_user']);

    auth_attempts_clear('login', $username);

    // Update last_login + ggf. Hash auf aktuellen Cost anheben
    try {
        $pdo->prepare('UPDATE `users` SET `last_login` = NOW() WHERE `id` = ?')->execute([$row['id']]);
        if (password_needs_rehash((string) $row['password_hash'], PASSWORD_BCRYPT, ['cost' => CG_BCRYPT_COST])) {
            $pdo->prepare('UPDATE `users` SET `password_hash` = ? WHERE `id` = ?')
                ->execute([password_hash($password, PASSWORD_BCRYPT, ['cost' => CG_BCRYPT_COST]), $row['id']]);
        }
    } catch (Throwable) {
        // non-critical, ignore
    }

    cg_log(LOG_LOGIN_SUCCESS, ['username' => $username, 'role' => $row['role']], (int) $row['id']);

    return true;
}

// ── Registration helpers ─────────────────────────────────────────

/**
 * Transliterate a name to a clean ASCII slug (umlauts → digraphs).
 */
function auth_slugify_name(string $name): string
{
    $map = [
        'ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue',
        'Ä' => 'ae', 'Ö' => 'oe', 'Ü' => 'ue',
        'ß' => 'ss',
    ];
    $slug = strtr(trim($name), $map);
    $slug = strtolower($slug);
    return preg_replace('/[^a-z0-9]/', '', $slug);
}

/**
 * Generate a unique username from first + last name.
 * Pattern: firstname.lastname, firstname.lastname2, …
 */
function auth_generate_username(string $firstName, string $lastName, \PDO $pdo): string
{
    $base = auth_slugify_name($firstName) . '.' . auth_slugify_name($lastName);
    if ($base === '.') {
        $base = 'user';
    }
    $base = substr($base, 0, 56);

    $candidate = $base;
    $n         = 2;
    while (true) {
        $stmt = $pdo->prepare('SELECT `id` FROM `users` WHERE `username` = ? LIMIT 1');
        $stmt->execute([$candidate]);
        if ($stmt->fetch() === false) {
            return $candidate;
        }
        $candidate = $base . $n;
        $n++;
    }
}

/**
 * Generate a secure random password (16 chars, no ambiguous chars).
 */
function auth_generate_password(): string
{
    $chars    = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789!@#%&';
    $len      = strlen($chars);
    $password = '';
    for ($i = 0; $i < 16; $i++) {
        $password .= $chars[random_int(0, $len - 1)];
    }
    return $password;
}

/**
 * Send the verification e-mail via PHPMailer + SMTP.
 * Returns true on success, false on failure.
 */
function auth_send_verify_email(
    string $to,
    string $firstName,
    string $username,
    string $password,
    string $token
): bool {
    $autoload = dirname(__DIR__) . '/vendor/autoload.php';
    if (!is_file($autoload)) {
        error_log('[CG] PHPMailer not found – run composer install on the server.');
        return false;
    }
    require_once $autoload;

    $verifyUrl = 'https://chrisgaming.at' . cg_url('/verify.php') . '?token=' . urlencode($token);
    $en = cg_lang() === 'en';

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

    try {
        // ── SMTP ──────────────────────────────────────────────────
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

        // ── Absender & Empfänger ──────────────────────────────────
        $mail->setFrom(
            (string) env('SMTP_FROM', 'noreply@chrisgaming.at'),
            (string) env('SMTP_FROM_NAME', 'ChrisGaming')
        );
        $mail->addAddress($to);
        $mail->CharSet = 'UTF-8';

        // ── Inhalt ────────────────────────────────────────────────
        $mail->isHTML(false);
        if ($en) {
            $mail->Subject = 'ChrisGaming – Confirm your e-mail address';
            $mail->Body    = "Hello {$firstName},\n\n"
                . "thanks for registering on chrisgaming.at!\n\n"
                . "Your login details:\n  Username: {$username}\n  Password: {$password}\n\n"
                . "Please change the password in your dashboard after the first login and delete this e-mail.\n\n"
                . "Confirm your e-mail address now (link valid for 24 hours):\n{$verifyUrl}\n\n"
                . "If you did not register, simply ignore this e-mail.\n\nBest regards,\nChris";
            $mail->send();
            return true;
        }
        $mail->Subject = 'ChrisGaming – E-Mail bestätigen';
        $mail->Body  = "Hallo {$firstName},\n\n";
        $mail->Body .= "danke für deine Registrierung auf chrisgaming.at!\n\n";
        $mail->Body .= "Hier sind deine Zugangsdaten:\n";
        $mail->Body .= "  Benutzername: {$username}\n";
        $mail->Body .= "  Passwort:     {$password}\n\n";
        $mail->Body .= "Bitte ändere das Passwort nach dem ersten Login im Dashboard\n";
        $mail->Body .= "und lösche diese E-Mail anschließend.\n\n";
        $mail->Body .= "Bestätige jetzt deine E-Mail-Adresse (Link gültig 24 Stunden):\n";
        $mail->Body .= "{$verifyUrl}\n\n";
        $mail->Body .= "Falls du dich nicht registriert hast, ignoriere diese E-Mail einfach.\n\n";
        $mail->Body .= "Viele Grüße,\nChris";

        $mail->send();
        return true;
    } catch (\PHPMailer\PHPMailer\Exception $e) {
        error_log('[CG] Mail send failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Register a new user account.
 *
 * Username + Passwort werden automatisch erzeugt und AUSSCHLIESSLICH per
 * E-Mail zugestellt (nie im Browser angezeigt) – so kann niemand mit der
 * Adresse eines Dritten einen Account anlegen und das Passwort mitlesen.
 * Ist die E-Mail bereits vergeben, sieht der Besucher dieselbe Erfolgsmeldung
 * (kein Account-Enumeration-Orakel).
 *
 * @return array{email:string}|string  Erfolg: Array, Fehler: Meldung
 */
function auth_register(string $firstName, string $lastName, string $email): array|string
{
    $firstName = trim($firstName);
    $lastName  = trim($lastName);
    $email     = trim($email);

    if ($firstName === '' || $lastName === '' || $email === '') {
        return 'Bitte alle Felder ausfüllen.';
    }
    $nameRe = '/^\p{L}[\p{L} .\'\-]*$/u';
    if (mb_strlen($firstName) < 2 || mb_strlen($firstName) > 64 || !preg_match($nameRe, $firstName)) {
        return 'Vorname muss 2–64 Zeichen lang sein und darf nur Buchstaben enthalten.';
    }
    if (mb_strlen($lastName) < 2 || mb_strlen($lastName) > 64 || !preg_match($nameRe, $lastName)) {
        return 'Nachname muss 2–64 Zeichen lang sein und darf nur Buchstaben enthalten.';
    }
    if (strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'Ungültige E-Mail-Adresse.';
    }

    $pdo = db();
    if ($pdo === null) {
        return 'Datenbankfehler. Bitte später erneut versuchen.';
    }

    // Spam-/Mail-Bombing-Schutz
    if (auth_attempt_count('register', 'ip_hash', auth_hash(cg_client_ip()), CG_REGISTER_WINDOW) >= CG_REGISTER_MAX_PER_IP) {
        cg_log(LOG_REGISTER_FAILED, ['email' => $email, 'error' => 'throttled']);
        return 'Zu viele Registrierungen von deiner Adresse. Bitte versuche es später erneut.';
    }
    auth_attempt_record('register', $email);

    try {
        // Check e-mail uniqueness
        $stmt = $pdo->prepare('SELECT `id` FROM `users` WHERE `email` = ? LIMIT 1');
        $stmt->execute([$email]);
        if ($stmt->fetch() !== false) {
            cg_log(LOG_REGISTER_EMAIL_TAKEN, ['email' => $email]);
            return ['email' => $email]; // gleiche Antwort wie bei Erfolg
        }

        $username = auth_generate_username($firstName, $lastName, $pdo);
        $password = auth_generate_password();
        $hash     = password_hash($password, PASSWORD_BCRYPT, ['cost' => CG_BCRYPT_COST]);
        $token    = bin2hex(random_bytes(32)); // 64-char hex token (nur im Mail-Link)

        $insert = $pdo->prepare(
            'INSERT INTO `users`
                 (`username`, `first_name`, `last_name`, `email`,
                  `email_verified`, `email_verify_token`,
                  `password_hash`, `role`, `is_active`)
             VALUES (?, ?, ?, ?, 0, ?, ?, \'user\', 0)'
        );
        $insert->execute([$username, $firstName, $lastName, $email, hash('sha256', $token), $hash]);
        $newId = (int) $pdo->lastInsertId();

    } catch (\Throwable $e) {
        cg_log(LOG_REGISTER_FAILED, ['email' => $email, 'error' => mb_substr($e->getMessage(), 0, 200)]);
        return 'Registrierung fehlgeschlagen. Bitte später erneut versuchen.';
    }

    $emailSent = auth_send_verify_email($email, $firstName, $username, $password, $token);

    if (!$emailSent) {
        // Ohne zugestellte Mail kann der Account nie aktiviert werden → zurückrollen
        try {
            $pdo->prepare('DELETE FROM `users` WHERE `id` = ? AND `email_verified` = 0')->execute([$newId]);
        } catch (\Throwable) {
            // ignore
        }
        cg_log(LOG_REGISTER_FAILED, ['email' => $email, 'error' => 'mail_not_sent']);
        return 'Die Bestätigungs-E-Mail konnte nicht gesendet werden. Bitte versuche es später erneut.';
    }

    cg_log(LOG_REGISTER_SUCCESS, ['username' => $username, 'email' => $email], $newId);

    return ['email' => $email];
}

/**
 * Prüft ein neues Passwort. Gibt eine Fehlermeldung oder null zurück.
 */
function auth_validate_new_password(string $new, string $confirm, string $username = ''): ?string
{
    if ($new !== $confirm) {
        return 'Die neuen Passwörter stimmen nicht überein.';
    }
    if (mb_strlen($new) < CG_PASSWORD_MIN) {
        return 'Das neue Passwort muss mindestens ' . CG_PASSWORD_MIN . ' Zeichen lang sein.';
    }
    if (strlen($new) > 128) {
        return 'Das neue Passwort ist zu lang (max. 128 Zeichen).';
    }
    if ($username !== '' && strcasecmp($new, $username) === 0) {
        return 'Das Passwort darf nicht dem Benutzernamen entsprechen.';
    }
    return null;
}

/**
 * Passwort ändern (aktuelles Passwort muss stimmen).
 *
 * @return true|string  true bei Erfolg, sonst Fehlermeldung
 */
function auth_change_password(int $userId, string $current, string $new, string $confirm): bool|string
{
    if ($new !== $confirm) {
        return 'Die neuen Passwörter stimmen nicht überein.';
    }
    if (mb_strlen($new) < CG_PASSWORD_MIN) {
        return 'Das neue Passwort muss mindestens ' . CG_PASSWORD_MIN . ' Zeichen lang sein.';
    }
    if (strlen($new) > 128) {
        return 'Das neue Passwort ist zu lang (max. 128 Zeichen).';
    }
    if ($new === $current) {
        return 'Das neue Passwort muss sich vom aktuellen unterscheiden.';
    }

    $pdo = db();
    if ($pdo === null) {
        return 'Datenbankfehler. Bitte später erneut versuchen.';
    }

    // Schutz gegen Raten des aktuellen Passworts über eine gekaperte Session
    $ipHash = auth_hash(cg_client_ip());
    if (auth_attempt_count('login', 'ip_hash', $ipHash, CG_LOGIN_WINDOW) >= CG_LOGIN_MAX_PER_IP) {
        return 'Zu viele Fehlversuche. Bitte warte 15 Minuten.';
    }

    try {
        $stmt = $pdo->prepare('SELECT `username`, `password_hash` FROM `users` WHERE `id` = ? LIMIT 1');
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
    } catch (Throwable) {
        return 'Datenbankfehler. Bitte später erneut versuchen.';
    }

    if ($row === false || !password_verify($current, (string) $row['password_hash'])) {
        auth_attempt_record('login', (string) ($row['username'] ?? ''));
        cg_log(LOG_LOGIN_FAILED, ['reason' => 'password_change_wrong_current'], $userId);
        return 'Das aktuelle Passwort ist falsch.';
    }
    if (strcasecmp($new, (string) $row['username']) === 0) {
        return 'Das Passwort darf nicht dem Benutzernamen entsprechen.';
    }

    try {
        $pdo->prepare('UPDATE `users` SET `password_hash` = ? WHERE `id` = ?')
            ->execute([password_hash($new, PASSWORD_BCRYPT, ['cost' => CG_BCRYPT_COST]), $userId]);
    } catch (Throwable) {
        return 'Datenbankfehler. Bitte später erneut versuchen.';
    }

    session_regenerate_id(true);
    cg_log(LOG_PASSWORD_CHANGED, [], $userId);

    return true;
}

/**
 * Destroy the current session and log the user out.
 * Optionally redirects to a URL.
 */
function auth_logout(string $redirectTo = ''): void
{
    // Log before clearing session
    if (isset($_SESSION['user_id'])) {
        cg_log(LOG_LOGOUT, [], (int) $_SESSION['user_id'], webhook: false);
    }

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 42000,
            'path'     => $params['path'],
            'domain'   => $params['domain'],
            'secure'   => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => 'Strict',
        ]);
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }

    if ($redirectTo !== '') {
        header('Location: ' . cg_safe_redirect($redirectTo));
        exit;
    }
}

// ── Passwort vergessen ────────────────────────────────────────────

const CG_RESET_TTL            = 3600; // Link 1 Stunde gültig
const CG_RESET_MAX_PER_IP     = 5;    // Anfragen pro Stunde und IP
const CG_RESET_MAX_PER_EMAIL  = 3;    // Anfragen pro Stunde und E-Mail-Adresse

/**
 * Führt $fn nach dem Senden der Antwort aus (php-fpm: fastcgi_finish_request).
 * So verrät die Antwortzeit nicht, ob die E-Mail-Adresse existiert (Mailversand dauert).
 */
function cg_run_after_response(callable $fn): void
{
    register_shutdown_function(static function () use ($fn): void {
        while (ob_get_level() > 0) {
            @ob_end_flush();
        }
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } else {
            @flush();
        }
        try {
            $fn();
        } catch (Throwable $e) {
            error_log('[CG] after-response task failed: ' . $e->getMessage());
        }
    });
}

function auth_send_reset_email(string $to, string $firstName, string $token, string $lang): bool
{
    $autoload = dirname(__DIR__) . '/vendor/autoload.php';
    if (!is_file($autoload)) {
        error_log('[CG] PHPMailer not found – run composer install on the server.');
        return false;
    }
    require_once $autoload;

    $url  = 'https://chrisgaming.at' . cg_url('/reset.php') . '?token=' . urlencode($token);
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host     = (string) env('SMTP_HOST', 'localhost');
        $mail->Port     = (int)    env('SMTP_PORT', 587);
        $mail->SMTPAuth = true;
        $mail->Username = (string) env('SMTP_USERNAME', '');
        $mail->Password = (string) env('SMTP_PASSWORD', '');
        $enc = strtoupper((string) env('SMTP_ENCRYPTION', 'STARTTLS'));
        $mail->SMTPSecure = ($enc === 'SSL')
            ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
            : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->setFrom(
            (string) env('SMTP_FROM', 'noreply@chrisgaming.at'),
            (string) env('SMTP_FROM_NAME', 'ChrisGaming')
        );
        $mail->addAddress($to);
        $mail->CharSet = 'UTF-8';
        $mail->isHTML(false);

        if ($lang === 'en') {
            $mail->Subject = 'ChrisGaming – Reset your password';
            $mail->Body    = "Hello {$firstName},\n\n"
                . "someone (hopefully you) asked to reset the password for your account on chrisgaming.at.\n\n"
                . "Set a new password here (link valid for 1 hour, can be used once):\n{$url}\n\n"
                . "If you did not request this, just ignore this e-mail – your password stays unchanged.\n\n"
                . "Best regards,\nChris";
        } else {
            $mail->Subject = 'ChrisGaming – Passwort zurücksetzen';
            $mail->Body    = "Hallo {$firstName},\n\n"
                . "für deinen Account auf chrisgaming.at wurde das Zurücksetzen des Passworts angefordert.\n\n"
                . "Neues Passwort festlegen (Link 1 Stunde gültig, nur einmal verwendbar):\n{$url}\n\n"
                . "Falls du das nicht warst, ignoriere diese E-Mail einfach – dein Passwort bleibt unverändert.\n\n"
                . "Viele Grüße,\nChris";
        }
        $mail->send();
        return true;
    } catch (\PHPMailer\PHPMailer\Exception $e) {
        error_log('[CG] Reset mail failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Fordert einen Reset-Link an. Gibt IMMER dieselbe Antwort (kein Account-Orakel);
 * nur bei Überschreitung des IP-Limits oder ungültiger Adresse wird eine Meldung (string) geliefert.
 *
 * @return true|string
 */
function auth_request_password_reset(string $email): bool|string
{
    $email = trim($email);
    if ($email === '' || strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'Ungültige E-Mail-Adresse.';
    }

    $pdo = db();
    if ($pdo === null) {
        return 'Datenbankfehler. Bitte später erneut versuchen.';
    }

    $ipHash = auth_hash(cg_client_ip());
    if (auth_attempt_count('reset', 'ip_hash', $ipHash, 3600) >= CG_RESET_MAX_PER_IP) {
        return 'Zu viele Anfragen. Bitte versuche es später erneut.';
    }
    $emailCount = auth_attempt_count('reset', 'subject_hash', auth_hash($email), 3600);
    auth_attempt_record('reset', $email);

    if ($emailCount >= CG_RESET_MAX_PER_EMAIL) {
        return true; // stillschweigend ignorieren (kein Mail-Bombing)
    }

    try {
        $stmt = $pdo->prepare(
            'SELECT `id`, `first_name` FROM `users`
             WHERE `email` = ? AND `is_active` = 1 AND `email_verified` = 1 LIMIT 1'
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();
    } catch (Throwable) {
        return true;
    }

    if ($user === false) {
        cg_log(LOG_PASSWORD_RESET_REQ, ['known' => 'no']);
        return true;
    }

    $token = bin2hex(random_bytes(32));
    try {
        $pdo->prepare('DELETE FROM `password_resets` WHERE `user_id` = ?')->execute([(int) $user['id']]);
        $pdo->prepare(
            'INSERT INTO `password_resets` (`user_id`, `token_hash`, `expires_at`, `created_at`) VALUES (?, ?, ?, ?)'
        )->execute([(int) $user['id'], hash('sha256', $token), time() + CG_RESET_TTL, time()]);
    } catch (Throwable $e) {
        error_log('[CG] reset token insert: ' . $e->getMessage());
        return true;
    }

    cg_log(LOG_PASSWORD_RESET_REQ, ['known' => 'yes'], (int) $user['id']);

    $first = (string) ($user['first_name'] ?? '');
    $lang  = function_exists('cg_lang') ? cg_lang() : 'de';
    cg_run_after_response(static function () use ($email, $first, $token, $lang): void {
        auth_send_reset_email($email, $first, $token, $lang);
    });

    return true;
}

/**
 * Sucht einen gültigen (unbenutzten, nicht abgelaufenen) Reset-Token.
 *
 * @return array{id:int,user_id:int,username:string}|null
 */
function auth_find_reset_token(string $token): ?array
{
    if (strlen($token) !== 64 || !ctype_xdigit($token)) {
        return null;
    }
    try {
        $stmt = db()?->prepare(
            'SELECT r.`id`, r.`user_id`, u.`username`
             FROM `password_resets` r
             JOIN `users` u ON u.`id` = r.`user_id`
             WHERE r.`token_hash` = ? AND r.`used_at` IS NULL AND r.`expires_at` > ?
               AND u.`is_active` = 1 AND u.`email_verified` = 1
             LIMIT 1'
        );
        if ($stmt === null) {
            return null;
        }
        $stmt->execute([hash('sha256', $token), time()]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    } catch (Throwable) {
        return null;
    }
}

/**
 * Setzt das Passwort über einen Reset-Token. Der Token wird dabei verbraucht.
 *
 * @return true|string  true bei Erfolg, sonst Fehlermeldung
 */
function auth_complete_password_reset(string $token, string $new, string $confirm): bool|string
{
    $row = auth_find_reset_token($token);
    if ($row === null) {
        cg_log(LOG_PASSWORD_RESET_FAIL, []);
        return 'Der Link ist ungültig oder abgelaufen. Bitte fordere einen neuen an.';
    }
    $err = auth_validate_new_password($new, $confirm, (string) $row['username']);
    if ($err !== null) {
        return $err;
    }

    $pdo = db();
    try {
        $pdo->beginTransaction();
        // Token atomar verbrauchen (verhindert doppelte Verwendung bei parallelen Requests)
        $use = $pdo->prepare('UPDATE `password_resets` SET `used_at` = ? WHERE `id` = ? AND `used_at` IS NULL');
        $use->execute([time(), (int) $row['id']]);
        if ($use->rowCount() !== 1) {
            $pdo->rollBack();
            return 'Der Link ist ungültig oder abgelaufen. Bitte fordere einen neuen an.';
        }
        $pdo->prepare('UPDATE `users` SET `password_hash` = ? WHERE `id` = ?')
            ->execute([password_hash($new, PASSWORD_BCRYPT, ['cost' => CG_BCRYPT_COST]), (int) $row['user_id']]);
        $pdo->prepare('DELETE FROM `password_resets` WHERE `user_id` = ?')->execute([(int) $row['user_id']]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('[CG] password reset: ' . $e->getMessage());
        return 'Datenbankfehler. Bitte später erneut versuchen.';
    }

    auth_attempts_clear('login', (string) $row['username']);
    cg_log(LOG_PASSWORD_RESET_DONE, ['username' => $row['username']], (int) $row['user_id']);
    return true;
}
