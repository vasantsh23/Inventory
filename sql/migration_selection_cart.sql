-- ============================================================
-- Migration: adds `selection`, used by the new "Add to Cart" /
-- "View Cart" feature on the Results page — one row per (user
-- email, stock number) added to a user's cart.
--
-- Structure matches the supplied dump exactly (no sample data
-- included, since those rows reference emails from a different
-- system).
--
-- Safe to run once in phpMyAdmin's SQL tab against your existing
-- database.
-- ============================================================

CREATE TABLE IF NOT EXISTS selection (
    emailid VARCHAR(100) NOT NULL,
    stockno VARCHAR(25) NOT NULL,
    id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    KEY idx_selection_emailid (emailid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
