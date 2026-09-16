-- ============================================================
-- Migration: add selected_fonttype / selected_fontsize /
-- selected_forecolor / selected_backcolor to font_and_color.
--
-- Run this ONCE against an existing database that already has the
-- font_and_color table (created before this migration). Safe to run
-- in phpMyAdmin's SQL tab. Do NOT run against a brand-new database
-- created from the current sql/schema.sql — it already includes
-- these columns.
-- ============================================================

ALTER TABLE font_and_color
    ADD COLUMN selected_fonttype  VARCHAR(20) NULL AFTER `backcolor-5`,
    ADD COLUMN selected_fontsize  VARCHAR(20) NULL AFTER selected_fonttype,
    ADD COLUMN selected_forecolor VARCHAR(20) NULL AFTER selected_fontsize,
    ADD COLUMN selected_backcolor VARCHAR(20) NULL AFTER selected_forecolor;
