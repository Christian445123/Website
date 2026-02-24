-- ============================================================
--  CG Website – Database Schema
--  Run once against the `chrisgaming` database.
-- ============================================================

-- ------------------------------------------------------------
--  Rate Limiting
-- ------------------------------------------------------------
-- Each row represents a single HTTP request.
-- Rows older than 2 hours are pruned periodically.
CREATE TABLE IF NOT EXISTS `rate_limit` (
    `id`           BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `ip_hash`      VARCHAR(64)      NOT NULL COMMENT 'SHA-256 of the visitor IP',
    `requested_at` INT UNSIGNED     NOT NULL COMMENT 'UNIX timestamp of the request',
    PRIMARY KEY (`id`),
    INDEX `idx_ip_time` (`ip_hash`, `requested_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  Users  (login foundation)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id`            INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `username`      VARCHAR(64)      NOT NULL,
    `email`         VARCHAR(255)         NULL,
    `password_hash` VARCHAR(255)     NOT NULL COMMENT 'bcrypt / argon2id via password_hash()',
    `role`          ENUM('admin','moderator','user') NOT NULL DEFAULT 'user',
    `is_active`     TINYINT(1)       NOT NULL DEFAULT 1,
    `created_at`    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_login`    DATETIME             NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_username` (`username`),
    UNIQUE KEY `uk_email`    (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  Sessions (optional – only needed for database-backed sessions)
-- ------------------------------------------------------------
-- If you want to store PHP sessions in the DB instead of files,
-- enable this table and set session.save_handler = 'user' in PHP.
-- For now it is left commented out.
--
-- CREATE TABLE IF NOT EXISTS `sessions` (
--     `id`         VARCHAR(128)  NOT NULL,
--     `data`       MEDIUMTEXT        NULL,
--     `expires_at` INT UNSIGNED  NOT NULL,
--     PRIMARY KEY (`id`),
--     INDEX `idx_expires` (`expires_at`)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
