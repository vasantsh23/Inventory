-- ============================================================
-- Migration: add `color` and `clarity` tables (from color.sql /
-- clarity.sql). Both use auto-increment `id` with a unique
-- constraint on the label column, matching their source dumps.
--
-- `color.active` was an int (0/1) in the source dump — converted
-- here to a VARCHAR('yes'/'no') to match the `active` convention
-- used elsewhere in this app (cut, polish, symmetry, etc.), with
-- the existing 1/0 values mapped to yes/no accordingly.
--
-- Charset changed from the source dumps' latin1 to utf8mb4 to match
-- the rest of this app's schema — safe here since all the data is
-- plain ASCII.
--
-- Safe to run once in phpMyAdmin's SQL tab against your existing
-- database.
-- ============================================================

CREATE TABLE IF NOT EXISTS `clarity` (
    `id`      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `clarity` VARCHAR(10) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `clarity` (`clarity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=14;

INSERT INTO `clarity` (`id`, `clarity`) VALUES
(1, 'ALL'),
(2, 'FL'),
(11, 'I1'),
(12, 'I2'),
(13, 'I3'),
(3, 'IF'),
(8, 'SI1'),
(9, 'SI2'),
(10, 'SI3'),
(6, 'VS1'),
(7, 'VS2'),
(4, 'VVS1'),
(5, 'VVS2');

CREATE TABLE IF NOT EXISTS `color` (
    `id`     INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `color`  VARCHAR(10) NOT NULL,
    `active` VARCHAR(10) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `color` (`color`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=36;

INSERT INTO `color` (`id`, `color`, `active`) VALUES
(1, 'All', 'yes'),
(2, 'D', 'yes'),
(3, 'E', 'yes'),
(4, 'F', 'yes'),
(5, 'G', 'yes'),
(6, 'H', 'yes'),
(7, 'I', 'yes'),
(8, 'J', 'yes'),
(9, 'K', 'yes'),
(10, 'L', 'yes'),
(11, 'M', 'yes'),
(12, 'N', 'no'),
(13, 'O', 'no'),
(14, 'P', 'no'),
(15, 'Q', 'no'),
(16, 'R', 'no'),
(17, 'S', 'no'),
(18, 'T', 'no'),
(19, 'U', 'no'),
(20, 'V', 'no'),
(21, 'W', 'no'),
(22, 'X', 'no'),
(23, 'Y', 'no'),
(24, 'Z', 'no'),
(25, 'Collection', 'no'),
(26, 'White', 'no'),
(27, 'TLC', 'no'),
(28, 'LC', 'no'),
(29, 'Cape', 'no'),
(30, 'TTLB', 'no'),
(31, 'TLB', 'no'),
(32, 'LB', 'no'),
(33, 'Fancy', 'no'),
(34, 'Black', 'no'),
(35, 'Others', 'yes');
