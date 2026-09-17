-- ============================================================
-- Migration: add an `active` column to `clarity` and `lab`,
-- needed so the new Diamond Search filter page can filter them by
-- active = 'yes' (matching cut/polish/symmetry/fluorescence/shape/
-- color, which already have this column). Existing rows default to
-- 'yes' so nothing disappears from the filter screen unexpectedly.
--
-- Safe to run once in phpMyAdmin's SQL tab against your existing
-- database.
-- ============================================================

ALTER TABLE clarity
    ADD COLUMN active VARCHAR(10) NOT NULL DEFAULT 'yes';

ALTER TABLE lab
    ADD COLUMN active VARCHAR(10) NOT NULL DEFAULT 'yes';
