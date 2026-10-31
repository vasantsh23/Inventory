-- ============================================================
-- Migration: "Forgot password?" on the login screen.
--
-- One row per reset link e-mailed to a user. Only a SHA-256 hash of
-- the link's secret is stored, so a leaked database row can't be used
-- to reset anyone's password. Links expire after 60 minutes and work
-- once. Rows are deleted automatically with their user.
--
-- Run ONCE in phpMyAdmin's SQL tab against your existing database.
-- Do NOT run on a new database created from the current
-- sql/schema.sql — it already includes this table.
-- ============================================================

CREATE TABLE IF NOT EXISTS password_resets (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    token_hash  CHAR(64)     NOT NULL UNIQUE,
    expires_at  DATETIME     NOT NULL,
    used_at     DATETIME     NULL,
    ipadd       VARCHAR(45)  NOT NULL DEFAULT '',
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_password_resets_user (user_id, created_at),
    CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
) ENGINE=InnoDB;
