-- ============================================================
-- Migration:
--  1. Create `results` as a live clone of `diamond_search` — same
--     structure AND current data (including any renames/edits you've
--     already made to colname/fldname/active/orderid).
--  2. Add a `color` column to `availability`, for a status-badge
--     color per row (e.g. green for "Available", red for "Hold").
--
-- Safe to run once in phpMyAdmin's SQL tab against your existing
-- database.
-- ============================================================

CREATE TABLE results LIKE diamond_search;
INSERT INTO results SELECT * FROM diamond_search;

ALTER TABLE availability
    ADD COLUMN color VARCHAR(10) NULL AFTER avail;
