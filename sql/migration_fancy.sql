-- ============================================================
-- Migration: adds the `fancy` reference table (color/intensity
-- combos with a description and abbreviation), four new maindata
-- columns (fancy, fancy_short, hold, memo), and activates "Fancy"
-- as a selectable color option in Diamond Search.
--
-- Safe to run once in phpMyAdmin's SQL tab against your existing
-- database.
-- ============================================================

CREATE TABLE IF NOT EXISTS fancy (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    color        VARCHAR(100) NOT NULL,
    intensity    VARCHAR(100) NOT NULL DEFAULT '',
    description  VARCHAR(255) NOT NULL DEFAULT '',
    abbreviation VARCHAR(50)  NOT NULL DEFAULT ''
) ENGINE=InnoDB;

ALTER TABLE maindata
    ADD COLUMN fancy       VARCHAR(10) NOT NULL DEFAULT 'no',
    ADD COLUMN fancy_short VARCHAR(50) NULL,
    ADD COLUMN memo        VARCHAR(10) NOT NULL DEFAULT 'no';

-- `hold` already exists in maindata (VARCHAR(255), nullable, no
-- default) — rather than re-adding it, this gives it the requested
-- 'no' default and backfills existing NULL/blank rows, leaving its
-- type untouched to avoid any risk to existing data.
ALTER TABLE maindata ALTER COLUMN hold SET DEFAULT 'no';
UPDATE maindata SET hold = 'no' WHERE hold IS NULL OR hold = '';

UPDATE `color` SET active = 'yes' WHERE `color` = 'Fancy';
