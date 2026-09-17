-- ============================================================
-- Migration: adds `rsetup` — controls the font family/size used on
-- the Results and View Cart screens, plus up to 6 configurable
-- sort-field/sort-order pairs (A = Ascending, D = Descending) for
-- future use.
--
-- Safe to run once in phpMyAdmin's SQL tab against your existing
-- database.
-- ============================================================

CREATE TABLE IF NOT EXISTS rsetup (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fontype    VARCHAR(150) NOT NULL DEFAULT '',
    fontsize   INT NOT NULL DEFAULT 14,
    sortfld1   VARCHAR(100) NOT NULL DEFAULT '',
    sortorder1 VARCHAR(1)   NOT NULL DEFAULT '',
    sortfld2   VARCHAR(100) NOT NULL DEFAULT '',
    sortorder2 VARCHAR(1)   NOT NULL DEFAULT '',
    sortfld3   VARCHAR(100) NOT NULL DEFAULT '',
    sortorder3 VARCHAR(1)   NOT NULL DEFAULT '',
    sortfld4   VARCHAR(100) NOT NULL DEFAULT '',
    sortorder4 VARCHAR(1)   NOT NULL DEFAULT '',
    sortfld5   VARCHAR(100) NOT NULL DEFAULT '',
    sortorder5 VARCHAR(1)   NOT NULL DEFAULT '',
    sortfld6   VARCHAR(100) NOT NULL DEFAULT '',
    sortorder6 VARCHAR(1)   NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO rsetup (fontype, fontsize) VALUES ('Arial', 14);
