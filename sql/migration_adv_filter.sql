-- ============================================================
-- Migration: create `adv_filter` as a live clone of `diamond_search`
-- (same structure AND current data, including any renames/edits
-- you've already made to colname/fldname/active/orderid).
--
-- Safe to run once in phpMyAdmin's SQL tab against your existing
-- database.
-- ============================================================

CREATE TABLE adv_filter LIKE diamond_search;
INSERT INTO adv_filter SELECT * FROM diamond_search;
