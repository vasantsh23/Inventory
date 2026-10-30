-- ============================================================
-- Migration: adds `hold_restrict` to `setup`.
--
-- 'yes' (default) = stones whose maindata.hold = 'yes' are left out of
--   Diamond Search results (Results page, its Excel export, and the
--   app's search) for plain user types and guests. memo (level 5),
--   memo-dubai (level 4), Admin (8) and Super Admin (9) always see
--   every stone (HOLD_EXEMPT_LEVELS in includes/diamond_search_query.php).
-- 'no'  = the hold check is switched off and ALL matching stones are
--   shown to everyone, held or not.
--
-- See Admin > Site Setup >
-- "Hold Restriction" and includes/diamond_search_query.php.
--
-- Safe to run once in phpMyAdmin's SQL tab against your existing
-- database. Do NOT run on a new database created from the current
-- sql/schema.sql — it already includes this column.
-- ============================================================

ALTER TABLE `setup`
    ADD COLUMN `hold_restrict` VARCHAR(10) NOT NULL DEFAULT 'yes';
