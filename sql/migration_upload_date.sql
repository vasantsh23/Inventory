-- ============================================================
-- Migration: creates `upload_date` — one row is written every time
-- Diamond Data Upload successfully imports a CSV (Admin > Diamond
-- Data Upload). See record_diamond_upload_log() in
-- includes/functions.php.
--
-- upldfile_date / upldfile_time : the uploaded CSV file's OWN
--   last-modified date/time, as reported by the browser (a plain
--   file upload doesn't otherwise carry this info at all).
-- data_uplddate / data_upldtime : when the upload actually RAN on
--   this server.
-- userid : the username of whoever ran the upload.
--
-- Shown together in Diamond Search's footer (top-right) so users can
-- see how fresh the current inventory data is.
--
-- Manageable at Admin > Manage Tables > Upload History.
--
-- Safe to run once in phpMyAdmin's SQL tab against your existing
-- database.
-- ============================================================

CREATE TABLE IF NOT EXISTS `upload_date` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `upldfile_date` DATE NULL,
    `upldfile_time` TIME NULL,
    `data_uplddate` DATE NULL,
    `data_upldtime` TIME NULL,
    `userid`        VARCHAR(150) NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
