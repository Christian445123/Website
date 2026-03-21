<?php
/**
 * Authentication Helper (Login Foundation)
 * -----------------------------------------
 * Provides session management, login/logout logic and
 * access guards for a future login system.
 *
 * Usage:
 *   require_once __DIR__ . '/auth.php';
 *
 *   // Require a logged-in user (redirects to login.php otherwise):
 *   auth_require_login();
 *
 *   // Require a specific role:
 *   auth_require_role('admin');
 *
 *   // Check inside a template:
 *   if (auth_check()) { … }
 *   $user = auth_user(); // array from DB or null
 *
 * Database dependency:
 *   Reads from the `users` table – see sql/setup.sql.
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/logger.php'; // Activity Logger

// ── Session bootstrap ─────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,           // until browser close
        'path'     => '/',
        'secure'   => true,        // HTTPS only – set false for local dev via HTTP
        'httponly' => true,        // no JS access
        'samesite' => 'Strict',
    ]);
    session_start();
}

// ── Public helpers ────────────────────────────────────────────────

/**
 * Returns true if a user is currently logged in.
 */
function auth_check(): bool
{
    return isset($_SESSION['user_id']);
}

/**
 * Returns the current user's data (from session cache).
 * Null if not logged in.
 *
 * @return array{id:int,username:string,email:string|null,role:string}|null
 */
function auth_user(): ?array
{
    if (!auth_check()) {
        return null;
    }

    // Lazy-load from DB on first call within the request
    if (!isset($_SESSION['_user_data'])) {
        $pdo = db();
        if ($pdo === null) {
            return null;
        }
        try {
            $stmt = $pdo->prepare(
                'SELECT `id`, `username`, `email`, `role`
                 FROM `users`
                 WHERE `id` = ? AND `is_active` = 1
                 LIMIT 1'
            );
            $stmt->execute([$_SESSION['user_id']]);
            $row = $stmt->fetch();
        } catch (Throwable) {
            return null;
        }

        if ($row === false) {
            // User was deleted / deactivated – force logout
            cg_log(LOG_SESSION_EXPIRED, ['user_id' => $_SESSION['user_id'] ?? null]);
            auth_logout();
            return null;
        }

        $_SESSION['_user_data'] = $row;
    }

    return $_SESSION['_user_data'];
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

    $back = $redirect !== '' ? $redirect : ($_SERVER['REQUEST_URI'] ?? '/');
    header('Location: /login.php?redirect=' . urlencode($back));
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
    $userLevel = $hierarchy[$user['role']] ?? -1;
    $required  = $hierarchy[$requiredRole]  ?? 99;

    if ($userLevel < $required) {
        cg_log(LOG_ACCESS_DENIED, [
            'required_role' => $requiredRole,
            'user_role'     => $user['role'] ?? 'unknown',
            'url'           => $_SERVER['REQUEST_URI'] ?? '',
        ]);
        http_response_code(403);
        exit(
            '<!DOCTYPE html><html lang="de"><head><meta charset="UTF-8"><title>403</title></head>' .
            '<body style="font-family:sans-serif;text-align:center;padding:4rem">' .
            '<h1>403 &ndash; Zugriff verweigert</h1>' .
            '<p>Du hast keine Berechtigung, diese Seite aufzurufen.</p>' .
            '</body></html>'
        );
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
    if (trim($username) === '' || $password === '') {
        return false;
    }

    $pdo = db();
    if ($pdo === null) {
        return false;
    }

    try {
        $stmt = $pdo->prepare(
            'SELECT `id`, `password_hash`, `role`, `is_active`, `email_verified`
             FROM `users`
             WHERE `username` = ?
             LIMIT 1'
        );
        $stmt->execute([trim($username)]);
        $row = $stmt->fetch();
    } catch (Throwable) {
        return false;
    }

    if ($row === false) {
        // Constant-time dummy verify to prevent timing attacks
        password_verify($password, '$2y$12$invaliddummyhashXXXXXXXXXXXXXXXXXXXXXXXX');
        cg_log(LOG_LOGIN_FAILED, ['username' => $username, 'reason' => 'user_not_found']);
        return 'Benutzername oder Passwort falsch.';
    }

    if (!password_verify($password, $row['password_hash'])) {
        cg_log(LOG_LOGIN_FAILED, ['username' => $username, 'reason' => 'wrong_password'], (int) $row['id']);
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

    // Regenerate session ID to prevent fixation
    session_regenerate_id(true);

    $_SESSION['user_id'] = (int) $row['id'];
    unset($_SESSION['_user_data']); // force fresh DB load on auth_user()

    // Update last_login timestamp
    try {
        db()?->prepare('UPDATE `users` SET `last_login` = NOW() WHERE `id` = ?')
            ->execute([$row['id']]);
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
 * Generate a secure random password (12 chars, no ambiguous chars).
 */
function auth_generate_password(): string
{
    $chars    = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789!@#%&';
    $len      = strlen($chars);
    $password = '';
    for ($i = 0; $i < 12; $i++) {
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

    $verifyUrl = 'https://chrisgaming.at/verify.php?token=' . urlencode($token);

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
        $mail->Subject = 'ChrisGaming – E-Mail bestätigen';
        $mail->isHTML(false);
        $mail->Body  = "Hallo {$firstName},\n\n";
        $mail->Body .= "danke für deine Registrierung auf chrisgaming.at!\n\n";
        $mail->Body .= "Hier sind deine Zugangsdaten:\n";
        $mail->Body .= "  Benutzername: {$username}\n";
        $mail->Body .= "  Passwort:     {$password}\n\n";
        $mail->Body .= "Bitte speichere diese Daten sicher ab.\n\n";
        $mail->Body .= "Bestätige jetzt deine E-Mail-Adresse (Link gültig 24 Stunden):\n";
        $mail->Body .= "{$verifyUrl}\n\n";
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
 * Generates username + password automatically.
 * Creates the account in an unverified state and sends a verification e-mail.
 *
 * On success returns an array:
 *   ['username' => string, 'password' => string, 'email_sent' => bool]
 * On failure returns a string error message.
 *
 * @return array{username:string,password:string,email_sent:bool}|string
 */
function auth_register(string $firstName, string $lastName, string $email): array|string
{
    $firstName = trim($firstName);
    $lastName  = trim($lastName);
    $email     = trim($email);

    if ($firstName === '' || $lastName === '' || $email === '') {
        return 'Bitte alle Felder ausfüllen.';
    }
    if (mb_strlen($firstName) < 2 || mb_strlen($firstName) > 64) {
        return 'Vorname muss 2–64 Zeichen lang sein.';
    }
    if (mb_strlen($lastName) < 2 || mb_strlen($lastName) > 64) {
        return 'Nachname muss 2–64 Zeichen lang sein.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'Ungültige E-Mail-Adresse.';
    }

    $pdo = db();
    if ($pdo === null) {
        return 'Datenbankfehler. Bitte später erneut versuchen.';
    }

    try {
        // Check e-mail uniqueness
        $stmt = $pdo->prepare('SELECT `id` FROM `users` WHERE `email` = ? LIMIT 1');
        $stmt->execute([$email]);
        if ($stmt->fetch() !== false) {
            cg_log(LOG_REGISTER_EMAIL_TAKEN, ['email' => $email]);
            return 'Diese E-Mail-Adresse ist bereits registriert.';
        }

        $username = auth_generate_username($firstName, $lastName, $pdo);
        $password = auth_generate_password();
        $hash     = password_hash($password, PASSWORD_BCRYPT);
        $token    = bin2hex(random_bytes(32)); // 64-char hex token

        $insert = $pdo->prepare(
            'INSERT INTO `users`
                 (`username`, `first_name`, `last_name`, `email`,
                  `email_verified`, `email_verify_token`,
                  `password_hash`, `role`, `is_active`)
             VALUES (?, ?, ?, ?, 0, ?, ?, \'user\', 0)'
        );
        $insert->execute([$username, $firstName, $lastName, $email, $token, $hash]);

    } catch (\Throwable $e) {
        $errMsg = $e->getMessage();
        cg_log(LOG_REGISTER_FAILED, ['email' => $email, 'error' => $errMsg]);
        // DEBUG: show real error (remove after fixing)
        if ((string) env('APP_DEBUG', '') === 'true') {
            return 'Registrierung fehlgeschlagen: ' . $errMsg;
        }
        return 'Registrierung fehlgeschlagen. Bitte später erneut versuchen.';
    }

    $emailSent = auth_send_verify_email($email, $firstName, $username, $password, $token);

    cg_log(LOG_REGISTER_SUCCESS, [
        'username'   => $username,
        'email'      => $email,
        'email_sent' => $emailSent ? 'yes' : 'no',
    ]);

    return [
        'username'   => $username,
        'password'   => $password,
        'email_sent' => $emailSent,
    ];
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
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();

    if ($redirectTo !== '') {
        header('Location: ' . $redirectTo);
        exit;
    }
}
