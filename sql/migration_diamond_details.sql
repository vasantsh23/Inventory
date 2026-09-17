-- ============================================================
-- Migration: create `diamond_details` as a live clone of `results`
-- (same structure AND current data), plus one new column:
-- `data_type`.
--
-- Safe to run once in phpMyAdmin's SQL tab against your existing
-- database.
-- ============================================================

CREATE TABLE diamond_details LIKE results;
INSERT INTO diamond_details SELECT * FROM results;

ALTER TABLE diamond_details
    ADD COLUMN data_type VARCHAR(50) NULL AFTER fldname;
