-- ============================================================
-- Migration: adds `Fancyfilter` to `setup`.
--
-- 'no' (default) = current Diamond Search behaviour unchanged: Color
--   shows a "Fancy" pill among its other options (mutually exclusive
--   with the rest — see includes/diamond_search_query.php).
-- 'yes' = Color's "Fancy" pill is removed, and two new sections —
--   "Nat Fancy Color" and "Nat Fancy Color Intensity" — appear
--   directly after Color, sourced from the new `fancycolor` /
--   `fancyint` lookup tables (see migration_fancycolor_fancyint.sql).
--
-- See modules/user/diamond_search.php and Admin > Site Setup >
-- "Fancy Filter".
--
-- Safe to run once in phpMyAdmin's SQL tab against your existing
-- database.
-- ============================================================

ALTER TABLE `setup`
    ADD COLUMN `Fancyfilter` VARCHAR(10) NOT NULL DEFAULT 'no';
