-- ============================================================
-- Migration: add active/order to `location` (matching the pattern
-- used by cut/polish/symmetry/fluorescence/shape), and create a new
-- `availability` lookup table for the Availability filter.
--
-- Safe to run once in phpMyAdmin's SQL tab against your existing
-- database.
-- ============================================================

ALTER TABLE location
    ADD COLUMN active VARCHAR(10) NOT NULL DEFAULT 'yes',
    ADD COLUMN `order` INT NULL;

CREATE TABLE IF NOT EXISTS availability (
    id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    avail   VARCHAR(50) NOT NULL,
    active  VARCHAR(10) NOT NULL DEFAULT 'yes',
    `order` INT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO availability (avail, active, `order`) VALUES
('Available', 'yes', 1),
('Memo / Consignment', 'yes', 2),
('Hold', 'yes', 3),
('Trade Show', 'yes', 4);
