-- ============================================================
-- Migration: add heart, black_incl_crown, white_incl_crown to
-- maindata; rename `white inclusion` to `white_incl`; keep
-- diamond_search in sync with both changes.
--
-- Safe to run once in phpMyAdmin's SQL tab against your existing
-- database.
-- ============================================================

-- 1. Rename the existing column (data is preserved automatically).
ALTER TABLE maindata
    CHANGE COLUMN `white inclusion` `white_incl` VARCHAR(255) NULL;

-- 2. Add the 3 new text columns.
ALTER TABLE maindata
    ADD COLUMN `heart` VARCHAR(255) NULL,
    ADD COLUMN `black_incl_crown` VARCHAR(255) NULL,
    ADD COLUMN `white_incl_crown` VARCHAR(255) NULL;

-- 3. Keep diamond_search's existing "white inclusion" row pointing at
--    the renamed column instead of adding a stale duplicate entry.
UPDATE diamond_search
SET colname = 'white_incl', fldname = 'white_incl'
WHERE colname = 'white inclusion';

-- 4. Add entries for the 3 new fields, continuing the existing
--    orderid sequence (1-93 already in use).
INSERT INTO diamond_search (colname, fldname, active, orderid) VALUES
    ('heart', 'heart', 'yes', 94),
    ('black_incl_crown', 'black_incl_crown', 'yes', 95),
    ('white_incl_crown', 'white_incl_crown', 'yes', 96);
