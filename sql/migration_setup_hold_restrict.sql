-- ============================================================
-- Migration: adds `hold_restrict` to `setup`.
--
-- 'yes' (default) = current behaviour unchanged: stones whose
--   maindata.hold = 'yes' are left out of Diamond Search results
--   (Results page, its Excel export, and the app's search).
-- 'no'  = the hold check is switched off and ALL matching stones are
--   shown, held or not.
--
-- Applies to every user type alike. See Admin > Site Setup >
-- "Hold Restriction" and includes/diamond_search_query.php.
--
-- Safe to run once in phpMyAdmin's SQL tab against your existing
-- database. Do NOT run on a new database created from the current
-- sql/schema.sql — it already includes this column.
-- ============================================================

ALTER TABLE `setup`
    ADD COLUMN `hold_restrict` VARCHAR(10) NOT NULL DEFAULT 'yes';
