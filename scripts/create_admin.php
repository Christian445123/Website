<?php
/**
 * create_admin.php – legt einen Administrator an (oder setzt Passwort/Rolle eines bestehenden Benutzers)
 * ====================================================================================================
 *   sudo -u chrisgaming php scripts/create_admin.php <benutzername> <e-mail> [Vorname] [Nachname]
 *
 * Das Passwort wird abgefragt (nicht in der Shell-Historie, nicht als Argument).
 * Existiert der Benutzername schon, werden Passwort gesetzt sowie Rolle=admin, aktiv und verifiziert.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Nur über die Kommandozeile nutzbar.\n");
}

$root = dirname(__DIR__);
require_once $root . '/includes/auth.php'; // db(), CG_BCRYPT_COST, CG_PASSWORD_MIN

[, $username, $email] = $argv + [null, null, null];
$first = $argv[3] ?? 'Admin';
$last  = $argv[4] ?? 'Admin';

if (!$username || !$email || !preg_match('/^[a-z0-9._-]{3,64}$/i', $username) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Aufruf: php scripts/create_admin.php <benutzername> <e-mail> [Vorname] [Nachname]\n");
    exit(1);
}

function ask_hidden(string $prompt): string
{
    echo $prompt;
    $tty = DIRECTORY_SEPARATOR === '/' && function_exists('posix_isatty') && posix_isatty(STDIN);
    if ($tty) {
        shell_exec('stty -echo');
    }
    $line = trim((string) fgets(STDIN));
    if ($tty) {
        shell_exec('stty echo');
        echo "\n";
    }
    return $line;
}

$pw1 = ask_hidden('Passwort (mind. ' . CG_PASSWORD_MIN . ' Zeichen): ');
$pw2 = ask_hidden('Passwort wiederholen: ');
$err = auth_validate_new_password($pw1, $pw2, $username);
if ($err !== null) {
    fwrite(STDERR, "FEHLER: {$err}\n");
    exit(1);
}

$pdo = db();
if ($pdo === null) {
    fwrite(STDERR, "FEHLER: Keine Datenbankverbindung (siehe .env / PHP-Error-Log).\n");
    exit(1);
}

$hash = password_hash($pw1, PASSWORD_BCRYPT, ['cost' => CG_BCRYPT_COST]);

$stmt = $pdo->prepare('SELECT `id` FROM `users` WHERE `username` = ? LIMIT 1');
$stmt->execute([$username]);
$id = $stmt->fetchColumn();

if ($id !== false) {
    $pdo->prepare(
        "UPDATE `users` SET `password_hash` = ?, `role` = 'admin', `is_active` = 1, `email_verified` = 1,
                `email_verify_token` = NULL WHERE `id` = ?"
    )->execute([$hash, $id]);
    echo "Bestehender Benutzer '{$username}' ist jetzt Admin (Passwort neu gesetzt).\n";
} else {
    $pdo->prepare(
        "INSERT INTO `users` (`username`, `first_name`, `last_name`, `email`, `email_verified`,
                              `password_hash`, `role`, `is_active`)
         VALUES (?, ?, ?, ?, 1, ?, 'admin', 1)"
    )->execute([$username, $first, $last, $email, $hash]);
    echo "Admin '{$username}' wurde angelegt.\n";
}
echo "Du kannst dich jetzt unter /login.php anmelden.\n";
