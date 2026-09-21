-- ============================================================
-- Optional migration: adds `active` to `fancycolor` and `fancyint`,
-- for anyone whose copies of these tables were created without it
-- (e.g. created by hand rather than via migration_fancycolor_fancyint.sql).
--
-- Not required for Diamond Search's Nat Fancy Color / Nat Fancy
-- Color Intensity sections to work — they already fall back to
-- showing every row when this column is absent. This is only for
-- the optional "Active" Yes/No toggle on the Fancy Colors / Fancy
-- Color Intensities admin screens (Manage Tables), which needs the
-- column to exist to have anything to show.
--
-- Safe to run once in phpMyAdmin's SQL tab. Skip this if your
-- `fancycolor` / `fancyint` tables already have an `active` column
-- (running it twice will fail with a duplicate-column error, which
-- is harmless — it just means this was already applied).
-- ============================================================

ALTER TABLE `fancycolor`
    ADD COLUMN `active` VARCHAR(10) DEFAULT 'yes';

ALTER TABLE `fancyint`
    ADD COLUMN `active` VARCHAR(10) DEFAULT 'yes';
