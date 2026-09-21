-- ============================================================
-- Migration: creates `fancycolor` and `fancyint` — admin-managed
-- lookup tables (Admin > Manage Tables > "Fancy Colors" / "Fancy
-- Color Intensities") that supply the pill options for Diamond
-- Search's "Nat Fancy Color" / "Nat Fancy Color Intensity" sections,
-- shown after Color when Site Setup's "Fancy Filter" is Yes.
--
-- Both are ordered by their own value column (fncycolor / fncyint)
-- when displayed, not by a separate sort column, per spec. `active`
-- follows the same convention as every other lookup table here
-- (color, clarity, cut, ...) so a value can be hidden without being
-- deleted.
--
-- The values in `maindata.NatFancyColor` / `maindata.NatFancyColorIntensity`
-- that these are matched against come from Diamond Data Upload's
-- automatic parsing of the Color field (e.g. "Fancy Deep Orange" ->
-- NatFancyColor "Orange", NatFancyColorIntensity "Deep") — see
-- modules/admin/diamond_data_upload.php.
--
-- Safe to run once in phpMyAdmin's SQL tab against your existing
-- database.
-- ============================================================

CREATE TABLE IF NOT EXISTS `fancycolor` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `fncycolor`  VARCHAR(50) NOT NULL,
    `active`     VARCHAR(10) DEFAULT 'yes',
    PRIMARY KEY (`id`),
    UNIQUE KEY `fncycolor` (`fncycolor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `fancycolor` (`fncycolor`, `active`) VALUES
('Yellow', 'yes'),
('Orange', 'yes'),
('Pink', 'yes'),
('Blue', 'yes'),
('Green', 'yes'),
('Brown', 'yes'),
('Gray', 'yes'),
('Black', 'yes'),
('Red', 'yes'),
('Purple', 'yes'),
('Violet', 'yes'),
('Champagne', 'yes'),
('Cognac', 'yes'),
('Olive', 'yes');

CREATE TABLE IF NOT EXISTS `fancyint` (
    `id`        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `fncyint`   VARCHAR(50) NOT NULL,
    `active`    VARCHAR(10) DEFAULT 'yes',
    PRIMARY KEY (`id`),
    UNIQUE KEY `fncyint` (`fncyint`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `fancyint` (`fncyint`, `active`) VALUES
('Light', 'yes'),
('Intense', 'yes'),
('Vivid', 'yes'),
('Dark', 'yes'),
('Deep', 'yes');
