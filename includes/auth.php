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
        http_response_code(403);
        exit(
            '<!DOCTYPE html><html lang="de"><head><meta charset="UTF-8"><title>403</title></head>' .
            '<body style="font-family:sans-serif;text-align:center;padding:4rem">' .
            '<h1>403 – Zugriff verweigert</h1>' .
            '<p>Du hast keine Berechtigung, diese Seite aufzurufen.</p>' .
            '</body></html>'
        );
    }
}

/**
 * Attempt to log a user in by username + password.
 *
 * Returns true on success (session is populated), false on failure.
 *
 * Usage:
 *   if (auth_login($_POST['username'], $_POST['password'])) {
 *       header('Location: /');
 *   } else {
 *       $error = 'Benutzername oder Passwort falsch.';
 *   }
 */
function auth_login(string $username, string $password): bool
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
            'SELECT `id`, `password_hash`, `role`, `is_active`
             FROM `users`
             WHERE `username` = ?
             LIMIT 1'
        );
        $stmt->execute([trim($username)]);
        $row = $stmt->fetch();
    } catch (Throwable) {
        return false;
    }

    if ($row === false || !(bool) $row['is_active']) {
        // Constant-time dummy verify to prevent timing attacks
        password_verify($password, '$2y$12$invaliddummyhashXXXXXXXXXXXXXXXXXXXXXXXX');
        return false;
    }

    if (!password_verify($password, $row['password_hash'])) {
        return false;
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

    return true;
}

/**
 * Destroy the current session and log the user out.
 * Optionally redirects to a URL.
 */
function auth_logout(string $redirectTo = ''): void
{
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
