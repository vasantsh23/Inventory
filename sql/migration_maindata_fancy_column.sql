-- ============================================================
-- Migration: adds `fancy` to `maindata` (yes/no, defaults 'no'),
-- and activates the existing "Fancy" row in the `color` lookup
-- table so it appears as a selectable pill on Diamond Search's
-- Color filter.
--
-- Used by includes/diamond_search_query.php:
--   - selecting the "Fancy" pill  -> WHERE fancy = 'yes'
--   - selecting "Others" in Color -> WHERE srtcol = 35 AND fancy = 'no'
-- (both replace Color's normal "not present in the lookup table"
-- meaning of "Others" — see the code comment there.)
--
-- Set `fancy = 'yes'` on whichever existing rows are actually
-- fancy-colored stones once this runs — this migration only adds
-- the column (defaulted to 'no' for every existing row) and does
-- not attempt to guess which rows should be 'yes'.
--
-- Safe to run once in phpMyAdmin's SQL tab against your existing
-- database.
-- ============================================================

ALTER TABLE `maindata`
    ADD COLUMN `fancy` VARCHAR(10) NULL DEFAULT 'no' AFTER `srtcol`;

UPDATE `color` SET `active` = 'yes' WHERE `color` = 'Fancy';
