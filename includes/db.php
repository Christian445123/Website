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
 * Returns the shared PDO instance, or null if the DB is unavailable.
 * Never throws – connection errors are only logged.
 */
function db(): ?PDO
{
    static ?PDO $pdo = null;
    static bool $tried = false;

    if ($tried) {
        return $pdo; // already connected or already failed
    }

    $tried = true;

    $driver  = strtolower((string) env('DB_DRIVER', 'mysql'));
    $host    = (string) env('DB_HOST',    '127.0.0.1');
    $port    = (int)    env('DB_PORT',    3306);
    $name    = (string) env('DB_NAME',    '');
    $user    = (string) env('DB_USER',    '');
    $pass    = (string) env('DB_PASS',    '');
    $charset = (string) env('DB_CHARSET', 'utf8mb4');

    // MariaDB uses the mysql PDO driver
    $dsnDriver = ($driver === 'mariadb') ? 'mysql' : $driver;
    $dsn       = "{$dsnDriver}:host={$host};port={$port};dbname={$name};charset={$charset}";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset} COLLATE {$charset}_unicode_ci",
    ];

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        error_log('[CG] DB connection failed: ' . $e->getMessage());
        $pdo = null;
    }

    return $pdo;
}
