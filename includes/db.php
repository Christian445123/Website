<?php
/**
 * Database Connection (PDO Singleton)
 * ------------------------------------
 * Verbindet sich mit der MariaDB aus .env-Credentials.
 * Gibt NULL zurück wenn die DB nicht erreichbar ist (kein Absturz).
 * Erstellt automatisch alle benötigten Tabellen beim ersten Verbinden
 * (kein manuelles Ausführen von setup.sql nötig).
 */

declare(strict_types=1);

require_once __DIR__ . '/env.php';

/**
 * Gibt die gemeinsame PDO-Instanz zurück, oder NULL bei Fehler.
 * Wirft niemals eine Exception.
 */
function db(): ?PDO
{
    // Keine typisierten static-Variablen verwenden! (erst ab PHP 8.3 gültig)
    static $pdo   = null;   // PDO|null
    static $tried = false;  // bool

    if ($tried) {
        return $pdo;
    }
    $tried = true;

    $driver  = strtolower((string) env('DB_DRIVER', 'mysql'));
    $host    = (string) env('DB_HOST',    '127.0.0.1');
    $port    = (int)    env('DB_PORT',    3306);
    $name    = (string) env('DB_NAME',    '');
    $user    = (string) env('DB_USER',    '');
    $pass    = (string) env('DB_PASS',    '');
    $charset = (string) env('DB_CHARSET', 'utf8mb4');

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
        db_migrate($pdo); // Tabellen automatisch anlegen
    } catch (PDOException $e) {
        error_log('[CG] DB connection failed: ' . $e->getMessage());
        $pdo = null;
    }

    return $pdo;
}

/**
 * Legt alle benötigten Tabellen an, falls sie noch nicht existieren.
 * Wird automatisch nach dem ersten erfolgreichen Verbindungsaufbau aufgerufen.
 * Entspricht sql/setup.sql – kein manuelles Ausführen mehr nötig.
 */
function db_migrate(PDO $pdo): void
{
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `rate_limit` (
                `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `ip_hash`      VARCHAR(64)     NOT NULL COMMENT 'SHA-256 of visitor IP',
                `requested_at` INT UNSIGNED    NOT NULL COMMENT 'UNIX timestamp',
                PRIMARY KEY (`id`),
                INDEX `idx_ip_time` (`ip_hash`, `requested_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `users` (
                `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
                `username`      VARCHAR(64)     NOT NULL,
                `email`         VARCHAR(255)        NULL,
                `password_hash` VARCHAR(255)    NOT NULL,
                `role`          ENUM('admin','moderator','user') NOT NULL DEFAULT 'user',
                `is_active`     TINYINT(1)      NOT NULL DEFAULT 1,
                `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `last_login`    DATETIME            NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_username` (`username`),
                UNIQUE KEY `uk_email`    (`email`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    } catch (Throwable $e) {
        error_log('[CG] DB migrate error: ' . $e->getMessage());
        // Nicht fatal – Fallback läuft weiter
    }
}
