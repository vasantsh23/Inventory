-- ============================================================
-- Migration: add 6 lookup tables (cut, fluorescence, polish,
-- symmetry, shape, location) generated from data_dictionary.xlsx.
--
-- Every table's `id` column is the primary key and auto-increment,
-- per spec. The other integer/text columns (cut_id, order, active,
-- etc.) are plain nullable fields.
--
-- Safe to run once in phpMyAdmin's SQL tab against your existing
-- database — these are brand-new tables, nothing existing is touched.
-- ============================================================

CREATE TABLE IF NOT EXISTS cut (
    `cut_id`             INT NULL,
    `cut`                VARCHAR(255) NULL,
    `id`                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order`              INT NULL,
    `active`             VARCHAR(255) NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS fluorescence (
    `flu_id`             INT NULL,
    `flu`                VARCHAR(255) NULL,
    `id`                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order`              INT NULL,
    `active`             VARCHAR(255) NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS polish (
    `pol_id`             INT NULL,
    `pol`                VARCHAR(255) NULL,
    `id`                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order`              INT NULL,
    `active`             VARCHAR(255) NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS symmetry (
    `sym_id`             INT NULL,
    `sym`                VARCHAR(255) NULL,
    `id`                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order`              INT NULL,
    `active`             VARCHAR(255) NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS shape (
    `shape_id`           INT NULL,
    `shape`              VARCHAR(255) NULL,
    `id`                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order`              INT NULL,
    `Display _nm`        VARCHAR(255) NULL,
    `imgpath`            VARCHAR(255) NULL,
    `active`             VARCHAR(255) NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS location (
    `location`           VARCHAR(255) NULL,
    `id`                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `loc_id`             INT NULL,
    `shortnm`            VARCHAR(255) NULL
) ENGINE=InnoDB;
