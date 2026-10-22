-- ============================================================
--  CG Website – komplettes Datenbankschema (aus includes/db.php erzeugt)
--  Normalerweise nicht nötig: scripts/migrate.php bzw. der erste Seitenaufruf legen alles an.
--  Manuell: mysql -u root -p chrisgaming < sql/setup.sql
-- ============================================================

CREATE TABLE IF NOT EXISTS `rate_limit` (
                `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `ip`           VARCHAR(45)         NULL COMMENT 'Echte IP-Adresse',
                `ip_hash`      VARCHAR(64)     NOT NULL DEFAULT '' COMMENT 'SHA-256 der IP',
                `requested_at` INT UNSIGNED    NOT NULL COMMENT 'UNIX-Timestamp',
                PRIMARY KEY (`id`),
                INDEX `idx_ip_time` (`ip_hash`, `requested_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `auth_attempts` (
                `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `kind`         VARCHAR(16)     NOT NULL COMMENT 'login | register',
                `ip_hash`      CHAR(64)        NOT NULL,
                `subject_hash` CHAR(64)        NOT NULL DEFAULT '' COMMENT 'SHA-256 von Benutzername/E-Mail',
                `attempted_at` INT UNSIGNED    NOT NULL,
                PRIMARY KEY (`id`),
                INDEX `idx_kind_ip`      (`kind`, `ip_hash`, `attempted_at`),
                INDEX `idx_kind_subject` (`kind`, `subject_hash`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `password_resets` (
                `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `user_id`    INT UNSIGNED    NOT NULL,
                `token_hash` CHAR(64)        NOT NULL,
                `expires_at` INT UNSIGNED    NOT NULL,
                `used_at`    INT UNSIGNED        NULL,
                `created_at` INT UNSIGNED    NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_token_hash` (`token_hash`),
                INDEX `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

