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
    `id`                  INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `username`            VARCHAR(64)      NOT NULL,
    `first_name`          VARCHAR(64)          NULL,
    `last_name`           VARCHAR(64)          NULL,
    `email`               VARCHAR(255)         NULL,
    `email_verified`      TINYINT(1)       NOT NULL DEFAULT 0,
    `email_verify_token`  VARCHAR(64)          NULL   COMMENT 'NULL after verification',
    `password_hash`       VARCHAR(255)     NOT NULL COMMENT 'bcrypt via password_hash()',
    `role`                ENUM('admin','moderator','user') NOT NULL DEFAULT 'user',
    `is_active`           TINYINT(1)       NOT NULL DEFAULT 0 COMMENT '0 until email verified',
    `created_at`          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_login`          DATETIME             NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_username`     (`username`),
    UNIQUE KEY `uk_email`        (`email`),
    UNIQUE KEY `uk_verify_token` (`email_verify_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Run this once if the table already exists and you need to add the new columns:
-- ALTER TABLE `users`
--     ADD COLUMN `first_name`         VARCHAR(64)  NULL AFTER `username`,
--     ADD COLUMN `last_name`          VARCHAR(64)  NULL AFTER `first_name`,
--     ADD COLUMN `email_verified`     TINYINT(1)   NOT NULL DEFAULT 0 AFTER `email`,
--     ADD COLUMN `email_verify_token` VARCHAR(64)  NULL AFTER `email_verified`,
--     MODIFY COLUMN `is_active`       TINYINT(1)   NOT NULL DEFAULT 0,
--     ADD UNIQUE KEY `uk_verify_token` (`email_verify_token`);

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
