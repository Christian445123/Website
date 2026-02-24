<?php
/**
 * Database Connection (PDO Singleton)
 * ------------------------------------
 * Returns a shared PDO instance. All credentials come from the
 * .env file via env().
 *
 * Usage anywhere after this file is included:
 *   $pdo = db();
 *   $stmt = db()->prepare('SELECT …');
 *
 * Supported drivers (DB_DRIVER in .env): mysql | mariadb
 */

declare(strict_types=1);

// Ensure .env values are available
require_once __DIR__ . '/env.php';

/**
 * Returns the shared PDO instance (lazy-initialized singleton).
 */
function db(): PDO
{
    static PDO|null $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $driver  = strtolower((string) env('DB_DRIVER', 'mysql'));
    $host    = (string) env('DB_HOST', '127.0.0.1');
    $port    = (int)    env('DB_PORT', 3306);
    $name    = (string) env('DB_NAME', '');
    $user    = (string) env('DB_USER', '');
    $pass    = (string) env('DB_PASS', '');
    $charset = (string) env('DB_CHARSET', 'utf8mb4');

    // MariaDB uses the mysql PDO driver
    $dsnDriver = ($driver === 'mariadb') ? 'mysql' : $driver;

    $dsn = "{$dsnDriver}:host={$host};port={$port};dbname={$name};charset={$charset}";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset} COLLATE {$charset}_unicode_ci",
    ];

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        // Do NOT expose connection details in the error message in production
        error_log('DB connection failed: ' . $e->getMessage());
        http_response_code(503);
        die(
            '<!DOCTYPE html><html lang="de"><head><meta charset="UTF-8"><title>503</title></head>' .
            '<body style="font-family:sans-serif;text-align:center;padding:4rem">' .
            '<h1>503 – Dienst nicht verfügbar</h1>' .
            '<p>Der Datenbankserver ist momentan nicht erreichbar. Bitte versuche es später erneut.</p>' .
            '</body></html>'
        );
    }

    return $pdo;
}
