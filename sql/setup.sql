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
--  Projects  (admin-managed project cards on cgprojekte.php)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `projects` (
    `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `title`       VARCHAR(128)  NOT NULL,
    `description` TEXT          NOT NULL,
    `icon_class`  VARCHAR(64)   NOT NULL DEFAULT 'fas fa-star'          COMMENT 'Font Awesome class(es)',
    `icon_color`  VARCHAR(32)   NOT NULL DEFAULT '#e8351f'              COMMENT 'CSS colour value',
    `icon_bg`     VARCHAR(64)   NOT NULL DEFAULT 'rgba(232,53,31,.15)'  COMMENT 'CSS background value',
    `tags`        VARCHAR(512)       NULL                                COMMENT 'JSON array e.g. ["PHP","Bootstrap"]',
    `link_url`    VARCHAR(512)       NULL,
    `link_label`  VARCHAR(64)        NULL,
    `link_style`  ENUM('primary','outline','discord','disabled') NOT NULL DEFAULT 'outline',
    `sort_order`  SMALLINT      NOT NULL DEFAULT 0,
    `is_active`   TINYINT(1)    NOT NULL DEFAULT 1,
    `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_sort` (`sort_order`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  Announcements  (member-only news / exclusive info)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `announcements` (
    `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `title`      VARCHAR(128)  NOT NULL,
    `body`       TEXT          NOT NULL,
    `author_id`  INT UNSIGNED  NOT NULL,
    `is_active`  TINYINT(1)    NOT NULL DEFAULT 1,
    `created_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_active_created` (`is_active`, `created_at`),
    CONSTRAINT `fk_ann_author` FOREIGN KEY (`author_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
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
