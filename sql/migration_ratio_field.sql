-- ============================================================
-- Migration: adds `ratio` (DECIMAL(10,2)) to `maindata`, and
-- registers it as a field in both `diamond_search` and `results` so
-- it can be activated as a search filter / display column via the
-- existing admin CRUD screens.
--
-- Safe to run once in phpMyAdmin's SQL tab against your existing
-- database.
-- ============================================================

ALTER TABLE maindata
    ADD COLUMN ratio DECIMAL(10,2) NULL;

INSERT INTO diamond_search (colname, fldname, active, orderid)
SELECT 'ratio', 'ratio', 'yes', COALESCE(MAX(orderid), 0) + 1 FROM diamond_search;

INSERT INTO results (colname, fldname, active, orderid)
SELECT 'ratio', 'ratio', 'yes', COALESCE(MAX(orderid), 0) + 1 FROM results;
