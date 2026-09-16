-- ============================================================
-- Migration: adds `shortnm` to `availability` — this is the value
-- that gets matched against maindata.avail (which typically stores a
-- short code, not the full description) to look up the row's color
-- for the Stock No background on Results/View Cart.
--
-- Safe to run once in phpMyAdmin's SQL tab against your existing
-- database.
-- ============================================================

ALTER TABLE availability
    ADD COLUMN shortnm VARCHAR(50) NOT NULL DEFAULT '' AFTER avail;
