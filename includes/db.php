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
        // ── rate_limit ──────────────────────────────────────────────
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `rate_limit` (
                `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `ip`           VARCHAR(45)         NULL COMMENT 'Echte IP-Adresse',
                `ip_hash`      VARCHAR(64)     NOT NULL COMMENT 'SHA-256 der IP',
                `requested_at` INT UNSIGNED    NOT NULL COMMENT 'UNIX-Timestamp',
                PRIMARY KEY (`id`),
                INDEX `idx_ip_time` (`ip_hash`, `requested_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ── users ───────────────────────────────────────────────────────
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `users` (
                `id`                  INT UNSIGNED     NOT NULL AUTO_INCREMENT,
                `username`            VARCHAR(64)      NOT NULL,
                `first_name`          VARCHAR(64)          NULL,
                `last_name`           VARCHAR(64)          NULL,
                `email`               VARCHAR(255)         NULL,
                `email_verified`      TINYINT(1)       NOT NULL DEFAULT 0,
                `email_verify_token`  VARCHAR(64)          NULL,
                `password_hash`       VARCHAR(255)     NOT NULL,
                `role`                ENUM('admin','moderator','user') NOT NULL DEFAULT 'user',
                `is_active`           TINYINT(1)       NOT NULL DEFAULT 0,
                `created_at`          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `last_login`          DATETIME             NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_username`     (`username`),
                UNIQUE KEY `uk_email`        (`email`),
                UNIQUE KEY `uk_verify_token` (`email_verify_token`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ── projects ────────────────────────────────────────────────────
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `projects` (
                `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
                `title`       VARCHAR(128)  NOT NULL,
                `description` TEXT          NOT NULL,
                `icon_class`  VARCHAR(64)   NOT NULL DEFAULT 'fas fa-star',
                `icon_color`  VARCHAR(32)   NOT NULL DEFAULT '#e8351f',
                `icon_bg`     VARCHAR(64)   NOT NULL DEFAULT 'rgba(232,53,31,.15)',
                `tags`        VARCHAR(512)      NULL,
                `link_url`    VARCHAR(512)      NULL,
                `link_label`  VARCHAR(64)       NULL,
                `link_style`  ENUM('primary','outline','discord','disabled') NOT NULL DEFAULT 'outline',
                `sort_order`  SMALLINT      NOT NULL DEFAULT 0,
                `is_active`   TINYINT(1)    NOT NULL DEFAULT 1,
                `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                INDEX `idx_sort` (`sort_order`, `id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ── announcements ───────────────────────────────────────────────
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `announcements` (
                `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
                `title`      VARCHAR(128)  NOT NULL,
                `body`       TEXT          NOT NULL,
                `author_id`  INT UNSIGNED  NOT NULL,
                `is_active`  TINYINT(1)    NOT NULL DEFAULT 1,
                `created_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                INDEX `idx_active_created` (`is_active`, `created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ── activity_log ────────────────────────────────────────────────
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `activity_log` (
                `id`         BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
                `event`      VARCHAR(64)      NOT NULL,
                `user_id`    INT UNSIGNED         NULL,
                `ip`         VARCHAR(45)      NOT NULL DEFAULT '',
                `ip_hash`    VARCHAR(64)      NOT NULL DEFAULT '',
                `user_agent` VARCHAR(512)         NULL,
                `context`    JSON                 NULL,
                `created_at` DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                INDEX `idx_event`      (`event`),
                INDEX `idx_user_id`    (`user_id`),
                INDEX `idx_ip_hash`    (`ip_hash`),
                INDEX `idx_created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        // ── Column migrations (safe ADD IF NOT EXISTS) ─────────────────
        // Adds missing columns to already-existing tables without dropping data.
        $dbName = $pdo->query('SELECT DATABASE()')->fetchColumn();
        $cols   = $pdo->prepare(
            'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
              WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?'
        );

        // rate_limit: add `ip` if missing
        $cols->execute([$dbName, 'rate_limit']);
        $rlCols = $cols->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('ip', $rlCols, true)) {
            $pdo->exec("ALTER TABLE `rate_limit` ADD COLUMN `ip` VARCHAR(45) NULL AFTER `id`");
        }

        // users: add new columns if missing
        $cols->execute([$dbName, 'users']);
        $userCols = $cols->fetchAll(PDO::FETCH_COLUMN);
        $userMigrations = [
            'first_name'         => "ADD COLUMN `first_name` VARCHAR(64) NULL AFTER `username`",
            'last_name'          => "ADD COLUMN `last_name` VARCHAR(64) NULL AFTER `first_name`",
            'email_verified'     => "ADD COLUMN `email_verified` TINYINT(1) NOT NULL DEFAULT 0 AFTER `email`",
            'email_verify_token' => "ADD COLUMN `email_verify_token` VARCHAR(64) NULL AFTER `email_verified`",
        ];
        foreach ($userMigrations as $col => $ddl) {
            if (!in_array($col, $userCols, true)) {
                $pdo->exec("ALTER TABLE `users` {$ddl}");
            }
        }
        // Fix is_active default: old schema had DEFAULT 1, new schema needs DEFAULT 0
        // (accounts inactive until email verified) – only update if no users exist yet
        $userCount = (int) $pdo->query('SELECT COUNT(*) FROM `users`')->fetchColumn();
        if ($userCount === 0) {
            $pdo->exec("ALTER TABLE `users` MODIFY COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 0");
        }

    } catch (Throwable $e) {
        error_log('[CG] DB migrate error: ' . $e->getMessage());
        // Nicht fatal – Fallback läuft weiter
    }
}
