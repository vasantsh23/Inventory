-- ============================================================
-- Migration: adds `loginscrn` to `setup`.
--
-- 'yes' (default) = current behaviour: clicking "Inventory" on the
--   home page always requires logging in first.
-- 'no' = clicking "Inventory" goes straight into the user module
--   (Diamond Search) without requiring a login — a public/open
--   browsing mode. Admin/Super Admin still always require a real
--   login regardless of this setting.
--
-- When set to 'no', remember that the admin login is no longer
-- linked from the public site — log in directly at:
--   https://yourdomain.com/inventory-admin-module/login.php
--
-- Safe to run once in phpMyAdmin's SQL tab against your existing
-- database.
-- ============================================================

ALTER TABLE setup
    ADD COLUMN loginscrn VARCHAR(10) NOT NULL DEFAULT 'yes';
