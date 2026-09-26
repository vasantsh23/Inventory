-- ============================================================
-- Migration: adds the fancycolor and fancyint reference tables
-- (for the Nat Fancy Color / Intensity pill selectors), a new
-- setup.Fancyfilter field controlling whether they're shown, and
-- deactivates NatFancyColor/NatFancyColorIntensity's old plain-text
-- search behavior in favor of the new pill rendering.
--
-- Safe to run once in phpMyAdmin's SQL tab against your existing
-- database.
-- ============================================================

CREATE TABLE IF NOT EXISTS fancycolor (
    id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fncycolor VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS fancyint (
    id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fncyint VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

ALTER TABLE setup ADD COLUMN Fancyfilter VARCHAR(10) NOT NULL DEFAULT 'no';

UPDATE diamond_search SET active = 'no' WHERE fldname IN ('NatFancyColor', 'NatFancyColorIntensity');
UPDATE adv_filter SET active = 'no' WHERE fldname IN ('NatFancyColor', 'NatFancyColorIntensity');
