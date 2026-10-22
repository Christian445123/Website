<?php
/**
 * migrate.php – legt die komplette Datenbankstruktur an bzw. ergänzt fehlende Tabellen/Spalten
 * ============================================================================================
 * Nur per Kommandozeile auf dem Server ausführen (liest die Zugangsdaten aus der .env):
 *
 *   cd /home/chrisgaming/htdocs/chrisgaming.at
 *   sudo -u chrisgaming php scripts/migrate.php
 *
 * Voraussetzung: Die (leere) Datenbank und der DB-Benutzer existieren bereits und die .env
 * zeigt darauf – siehe scripts/db_create.sql.example.
 * Das Skript ist idempotent: Mehrfaches Ausführen ist ungefährlich, bestehende Daten bleiben.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Nur über die Kommandozeile nutzbar.\n");
}

$root = dirname(__DIR__);
require_once $root . '/includes/db.php';

echo "Verbinde mit der Datenbank … ";
$pdo = db();
if ($pdo === null) {
    fwrite(STDERR, "\nFEHLER: Keine Verbindung. Prüfe DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS in der .env\n");
    fwrite(STDERR, "und ob der Datenbankserver läuft (Details: PHP-Error-Log, Zeile \"[CG] DB connection failed\").\n");
    exit(1);
}
echo "OK\n";

if (!db_migrate($pdo)) {
    fwrite(STDERR, "FEHLER bei der Migration (siehe PHP-Error-Log, \"[CG] DB migrate error\").\n");
    exit(1);
}

// Schema-Marker neu setzen, damit die Website die Migration nicht bei jedem Aufruf wiederholt
foreach (glob($root . '/tmp/.schema_v*') ?: [] as $old) {
    @unlink($old);
}
@file_put_contents($root . '/tmp/.schema_v' . CG_SCHEMA_VERSION, date('c'));

echo "\nTabellen:\n";
foreach (['users', 'projects', 'announcements', 'activity_log', 'rate_limit', 'auth_attempts', 'password_resets'] as $t) {
    try {
        $n = (int) $pdo->query("SELECT COUNT(*) FROM `{$t}`")->fetchColumn();
        printf("  %-18s OK (%d Zeilen)\n", $t, $n);
    } catch (Throwable $e) {
        printf("  %-18s FEHLT: %s\n", $t, $e->getMessage());
    }
}

$admins = (int) $pdo->query("SELECT COUNT(*) FROM `users` WHERE `role` = 'admin'")->fetchColumn();
echo "\n";
if ($admins === 0) {
    echo "Es gibt noch keinen Admin. Lege einen an mit:\n";
    echo "  sudo -u chrisgaming php scripts/create_admin.php <benutzername> <e-mail>\n";
}
echo "Fertig.\n";
