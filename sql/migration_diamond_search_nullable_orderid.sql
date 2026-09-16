-- ============================================================
-- Migration: allow diamond_search.orderid to be NULL, and change
-- active's default from 'yes' to '' (empty) — both needed so an
-- import can genuinely blank these columns out (not just set them
-- to a fallback value) for rows the uploaded file doesn't cover.
--
-- Safe to run once in phpMyAdmin's SQL tab against your existing
-- database. Existing data is not changed by this migration itself.
-- ============================================================

ALTER TABLE diamond_search
    MODIFY COLUMN orderid INT NULL;

ALTER TABLE diamond_search
    ALTER COLUMN active SET DEFAULT '';
