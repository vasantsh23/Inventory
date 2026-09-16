-- ============================================================
-- Migration: adds `self_short_urls`, used by the new "Copy" button
-- on the Results page — each time a user copies selected diamonds'
-- details, one row is logged per diamond (id + a shared timestamp
-- for that batch), matching the audit-log pattern from the supplied
-- reference program (main_self_copy.php).
--
-- Safe to run once in phpMyAdmin's SQL tab against your existing
-- database.
-- ============================================================

CREATE TABLE IF NOT EXISTS self_short_urls (
    urlid  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ids    VARCHAR(50) NOT NULL,
    timeon VARCHAR(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
