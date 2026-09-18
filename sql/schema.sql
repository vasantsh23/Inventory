-- ============================================================
-- Inventory Management System - Database Schema
-- Derived from Admintablestructures.xlsx
-- Engine: MySQL 8.x / MariaDB 10.5+
--
-- IMPORTANT (shared hosting / cPanel / BigRock):
-- This file does NOT create or switch databases. On shared hosting
-- your database is already provisioned via cPanel (e.g.
-- atest8a6_inventorydb) and the restricted DB user typically cannot
-- run CREATE DATABASE. In phpMyAdmin, select your existing database
-- from the left sidebar FIRST, then run this file's contents on the
-- SQL tab. Running it in the wrong database context is a common
-- cause of "table not found" errors after import.
--
-- If you ARE on your own server/VPS with full privileges, you may
-- uncomment the two lines below to create a dedicated database.
-- ============================================================

-- CREATE DATABASE IF NOT EXISTS inventory_system
--   CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE inventory_system;

-- ------------------------------------------------------------
-- USER_TYPES table
-- Defines the available user roles. `level` drives module access:
--   0-7  custom user-level roles (define as many as you like)
--   8    Admin   -> user + admin modules
--   9    Super Admin -> user + admin + superadmin modules
-- The `user` table's `usertype` column stores the id of a row here.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS user_types (
    id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usertype VARCHAR(100) NOT NULL UNIQUE,
    level    TINYINT UNSIGNED NOT NULL
) ENGINE=InnoDB;

INSERT INTO user_types (usertype, level) VALUES
    ('Standard User', 0),
    ('Admin', 8),
    ('Super Admin', 9);

-- ------------------------------------------------------------
-- USER table
-- Holds login credentials and personal data.
-- password        -> stored as a bcrypt hash (never reversible)
-- emailid, telephone, gsm, address-* -> stored ENCRYPTED (AES-256-GCM)
--   using the helper functions in includes/security.php.
--   Columns are VARBINARY because encrypted output is binary.
-- usertype        -> stores the id of a row in user_types (see below)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS user (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username        VARCHAR(100)  NOT NULL UNIQUE,
    emailid         VARBINARY(512) NOT NULL,
    emailid_hash    CHAR(64)      NOT NULL UNIQUE, -- HMAC-SHA256 of email, used for lookups
    password        VARCHAR(255)  NOT NULL,        -- bcrypt hash (password_hash)
    company         VARCHAR(150)  NULL,
    fname           VARCHAR(100)  NULL,
    lname           VARCHAR(100)  NULL,
    `address-1`     VARBINARY(512) NULL,
    `address-2`     VARBINARY(512) NULL,
    `address-3`     VARBINARY(512) NULL,
    `address-4`     VARBINARY(512) NULL,
    `address-5`     VARBINARY(512) NULL,
    telephone       VARBINARY(255) NULL,
    gsm             VARBINARY(255) NULL,
    usertype        INT UNSIGNED NOT NULL DEFAULT 1, -- FK -> user_types.id (default: 'Standard User')
    approval        ENUM('pending','approved','rejected','disabled') NOT NULL DEFAULT 'pending',
    creation_date   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ipadd           VARCHAR(45)   NULL,             -- IPv4/IPv6 of last login
    lang            VARCHAR(10)   NULL DEFAULT 'en',
    failed_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    locked_until    DATETIME NULL,
    last_login      DATETIME NULL,
    CONSTRAINT fk_user_usertype FOREIGN KEY (usertype) REFERENCES user_types(id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- SETUP table
-- Single-row (or per-tenant) site configuration used to render
-- the public home page: company name, address, phone, email, logo.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS setup (
    id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `Page title`          VARCHAR(255) NULL,
    `Page Desc`           TEXT NULL,
    `Favicon`             VARCHAR(255) NULL,
    `Logo`                VARCHAR(255) NULL,
    `Logo-2`              VARCHAR(255) NULL,
    `company`             VARCHAR(150) NULL,
    `address-1`           VARCHAR(255) NULL,
    `address-2`           VARCHAR(255) NULL,
    `address-3`           VARCHAR(255) NULL,
    `address-4`           VARCHAR(255) NULL,
    `address-5`           VARCHAR(255) NULL,
    `address-6`           VARCHAR(255) NULL,
    `telno-1`             VARCHAR(50)  NULL,
    `telno-2`             VARCHAR(50)  NULL,
    `telno-3`             VARCHAR(50)  NULL,
    `emailid1`            VARCHAR(150) NULL,
    `emailid2`            VARCHAR(150) NULL,
    `Meta Title`          VARCHAR(255) NULL,
    `Meta Desc`           TEXT NULL,
    `Meta Keyword`        VARCHAR(255) NULL,
    `Meta Extra Keyword`  VARCHAR(255) NULL,
    `Meta image`          VARCHAR(255) NULL,
    `OG url`              VARCHAR(255) NULL,
    `OG Title`            VARCHAR(255) NULL,
    `OG Desc`             TEXT NULL,
    `OG image`            VARCHAR(255) NULL,
    `SM1 card` VARCHAR(50) NULL, `SM1 url` VARCHAR(255) NULL, `SM1 title` VARCHAR(255) NULL, `SM1 Desc` TEXT NULL, `SM1 Image` VARCHAR(255) NULL,
    `SM2 card` VARCHAR(50) NULL, `SM2 url` VARCHAR(255) NULL, `SM2 title` VARCHAR(255) NULL, `SM2 Desc` TEXT NULL, `SM2 Image` VARCHAR(255) NULL,
    `SM3 card` VARCHAR(50) NULL, `SM3 url` VARCHAR(255) NULL, `SM3 title` VARCHAR(255) NULL, `SM3 Desc` TEXT NULL, `SM3 Image` VARCHAR(255) NULL,
    `SM4 card` VARCHAR(50) NULL, `SM4 url` VARCHAR(255) NULL, `SM4 title` VARCHAR(255) NULL, `SM4 Desc` TEXT NULL, `SM4 Image` VARCHAR(255) NULL,
    `SM5 card` VARCHAR(50) NULL, `SM5 url` VARCHAR(255) NULL, `SM5 title` VARCHAR(255) NULL, `SM5 Desc` TEXT NULL, `SM5 Image` VARCHAR(255) NULL,
    `SM6 card` VARCHAR(50) NULL, `SM6 url` VARCHAR(255) NULL, `SM6 title` VARCHAR(255) NULL, `SM6 Desc` TEXT NULL, `SM6 Image` VARCHAR(255) NULL,
    `Alt text`            VARCHAR(255) NULL,
    `Reservation`         VARCHAR(10)  NULL,
    `Take away`           VARCHAR(10)  NULL,
    `Google Ananlytics`   VARCHAR(50)  NULL,
    `Marquee`             VARCHAR(255) NULL,
    `Marquee active`      VARCHAR(10)  NULL,
    `text-1`  TEXT NULL, `text-2`  TEXT NULL, `text-3`  TEXT NULL, `text-4`  TEXT NULL, `text-5`  TEXT NULL,
    `text-6`  TEXT NULL, `text-7`  TEXT NULL, `text-8`  TEXT NULL, `text-9`  TEXT NULL, `text-10` TEXT NULL,
    `loginscrn` VARCHAR(10) NOT NULL DEFAULT 'yes'
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- PATH table
-- Generic key/value style table used to look up file paths
-- (e.g. the logo file) by searching `description`.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS path (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    description  VARCHAR(100) NOT NULL,
    path         VARCHAR(255) NOT NULL,
    INDEX idx_description (description)
) ENGINE=InnoDB;

-- Seed example row for the logo (adjust the path to the real file)
INSERT INTO path (description, path) VALUES ('logo', '/assets/img/company-logo.png');

-- ------------------------------------------------------------
-- TIMINGS table (business hours)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS timings (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `Day`         VARCHAR(20) NULL,
    `start time1` VARCHAR(20) NULL,
    `end time1`   VARCHAR(20) NULL,
    `start time2` VARCHAR(20) NULL,
    `end time2`   VARCHAR(20) NULL,
    `start time3` VARCHAR(20) NULL,
    `end time3`   VARCHAR(20) NULL,
    `holiday`     VARCHAR(10) NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- FONT AND COLOR table (site theming)
-- The selected_* columns pick which numbered slot (1-5) is the
-- active theme, e.g. selected_fonttype = 'font type-3' means "use
-- whatever is stored in the `font type-3` column". Public pages
-- read this via get_active_theme() in includes/functions.php.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS font_and_color (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `font type-1` VARCHAR(50) NULL, `font-size-1` VARCHAR(10) NULL,
    `font type-2` VARCHAR(50) NULL, `font-size-2` VARCHAR(10) NULL,
    `font type-3` VARCHAR(50) NULL, `font-size-3` VARCHAR(10) NULL,
    `font type-4` VARCHAR(50) NULL, `font-size-4` VARCHAR(10) NULL,
    `font type-5` VARCHAR(50) NULL, `font-size-5` VARCHAR(10) NULL,
    `forecolor-1` VARCHAR(10) NULL, `backcolor-1` VARCHAR(10) NULL,
    `forecolor-2` VARCHAR(10) NULL, `backcolor-2` VARCHAR(10) NULL,
    `forecolor-3` VARCHAR(10) NULL, `backcolor-3` VARCHAR(10) NULL,
    `forecolor-4` VARCHAR(10) NULL, `backcolor-4` VARCHAR(10) NULL,
    `forecolor-5` VARCHAR(10) NULL, `backcolor-5` VARCHAR(10) NULL,
    selected_fonttype  VARCHAR(20) NULL, -- one of: font type-1..5
    selected_fontsize  VARCHAR(20) NULL, -- one of: font-size-1..5
    selected_forecolor VARCHAR(20) NULL, -- one of: forecolor-1..5
    selected_backcolor VARCHAR(20) NULL  -- one of: backcolor-1..5
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- UPLOAD table (API / import-export configuration)
-- API key / secret are stored ENCRYPTED, same as PII above.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS upload (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `API`            VARCHAR(10) NULL,
    `API link`       VARCHAR(255) NULL,
    `API key`        VARBINARY(512) NULL,
    `API secret key` VARBINARY(512) NULL,
    `Excel`          VARCHAR(10) NULL,
    `CSV`            VARCHAR(10) NULL,
    `Path`           VARCHAR(255) NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- ------------------------------------------------------------
-- MAINDATA table (diamond inventory records)
-- Column list generated from the supplied field-definition
-- spreadsheet. `id` is the primary key and is NOT auto-increment
-- — supply an id explicitly when inserting (e.g. via import).
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS maindata (
    `altprice`                   DECIMAL(14,2) NULL,
    `avail`                      VARCHAR(255) NULL,
    `bgm`                        VARCHAR(255) NULL,
    `black_incl`                 VARCHAR(255) NULL,
    `brand`                      VARCHAR(255) NULL,
    `Cash discount`              DECIMAL(14,2) NULL,
    `Cash price`                 DECIMAL(14,2) NULL,
    `cert_comment`               TEXT NULL,
    `CertificateImage`           TEXT NULL,
    `CertificateNo`              VARCHAR(255) NULL,
    `CityStateCountry`           VARCHAR(255) NULL,
    `Clarity`                    VARCHAR(255) NULL,
    `clremark`                   VARCHAR(255) NULL,
    `Color`                      VARCHAR(255) NULL,
    `CrownAngle`                 VARCHAR(255) NULL,
    `CrownHeight`                VARCHAR(255) NULL,
    `CuletCondition`             VARCHAR(255) NULL,
    `CuletSize`                  VARCHAR(255) NULL,
    `CutGrade`                   VARCHAR(255) NULL,
    `Depthpct`                   DECIMAL(14,2) NULL,
    `eyeclean`                   VARCHAR(255) NULL,
    `Fancycolor`                 VARCHAR(255) NULL,
    `FluorescenceColor`          VARCHAR(255) NULL,
    `FluorescenceIntensity`      VARCHAR(255) NULL,
    `fluoroth`                   VARCHAR(255) NULL,
    `Girdlecond`                 VARCHAR(255) NULL,
    `Girdlepct`                  DECIMAL(14,2) NULL,
    `GirdleThinorGirdleThick`    VARCHAR(255) NULL,
    `height`                     DECIMAL(14,2) NULL,
    `hold`                       VARCHAR(255) NULL,
    `id`                         INT NOT NULL,
    `imglink`                    TEXT NULL,
    `keytosym`                   VARCHAR(255) NULL,
    `Lab`                        VARCHAR(255) NULL,
    `LaserInscription`           VARCHAR(255) NULL,
    `length`                     DECIMAL(14,2) NULL,
    `list`                       VARCHAR(255) NULL,
    `location`                   VARCHAR(255) NULL,
    `lotno`                      VARCHAR(255) NULL,
    `Measurements`               TEXT NULL,
    `Member_Comment`             TEXT NULL,
    `milkyid`                    INT NULL,
    `mkamt`                      DECIMAL(14,2) NULL,
    `mkdis`                      DECIMAL(14,2) NULL,
    `mkprice`                    DECIMAL(14,2) NULL,
    `NatFancyColor`              VARCHAR(255) NULL,
    `NatFancyColorIntensity`     VARCHAR(255) NULL,
    `NatFancyColorOvertone`      VARCHAR(255) NULL,
    `notforweb`                  VARCHAR(255) NULL,
    `open inclusion`             VARCHAR(255) NULL,
    `orapprice`                  DECIMAL(14,2) NULL,
    `origin`                     VARCHAR(255) NULL,
    `Pair`                       VARCHAR(255) NULL,
    `pairnm`                     VARCHAR(255) NULL,
    `PairSeparable`              VARCHAR(255) NULL,
    `parcel`                     VARCHAR(255) NULL,
    `ParcelStoneCount`           INT NULL,
    `PavilionAngle`              DECIMAL(14,2) NULL,
    `PavilionDepth`              DECIMAL(14,2) NULL,
    `pcs`                        INT NULL,
    `pdflink`                    TEXT NULL,
    `Polish`                     VARCHAR(255) NULL,
    `Price`                      DECIMAL(14,2) NULL,
    `Rap`                        DECIMAL(14,2) NULL,
    `RapOff`                     DECIMAL(14,2) NULL,
    `shadeid`                    INT NULL,
    `Shape`                      VARCHAR(255) NULL,
    `shapeoth`                   VARCHAR(255) NULL,
    `sizedesc`                   VARCHAR(255) NULL,
    `srtcla`                     INT NULL,
    `srtcol`                     INT NULL,
    `srtcts`                     INT NULL,
    `srtcut`                     INT NULL,
    `srtflu`                     INT NULL,
    `srtloc`                     INT NULL,
    `srtpol`                     INT NULL,
    `srtshp`                     INT NULL,
    `srtsiz`                     INT NULL,
    `srtsym`                     INT NULL,
    `starlength`                 VARCHAR(255) NULL,
    `statusid`                   INT NULL,
    `StockNo`                    VARCHAR(255) NULL,
    `Symmetry`                   VARCHAR(255) NULL,
    `table_incl`                 VARCHAR(255) NULL,
    `Tablepct`                   DECIMAL(14,2) NULL,
    `totamt`                     DECIMAL(14,2) NULL,
    `TradeShow`                  VARCHAR(255) NULL,
    `TreatmentLD`                VARCHAR(255) NULL,
    `vidlink`                    TEXT NULL,
    `viewyn`                     VARCHAR(255) NULL,
    `Weight`                     DECIMAL(14,2) NULL,
    `white_incl`                 VARCHAR(255) NULL,
    `width`                      DECIMAL(14,2) NULL,
    `heart`                      VARCHAR(255) NULL,
    `black_incl_crown`           VARCHAR(255) NULL,
    `white_incl_crown`           VARCHAR(255) NULL,
    `ratio`                      DECIMAL(10,2) NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- UPLOADREF table: maps an external CSV file's column names
-- (excolname) to the maindata column they populate (colname), for
-- the Diamond Data Upload program. Pre-populated with every maindata
-- field; excolname is left blank for the admin to fill in.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS uploadref (
    id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    colname   VARCHAR(100) NOT NULL,
    excolname VARCHAR(150) NOT NULL DEFAULT '',
    active    VARCHAR(10)  NOT NULL DEFAULT 'yes'
) ENGINE=InnoDB;

INSERT INTO uploadref (colname, excolname, active)
SELECT COLUMN_NAME, '', 'yes'
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'maindata'
ORDER BY ORDINAL_POSITION;

CREATE TABLE IF NOT EXISTS diamond_search (
    id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    colname  VARCHAR(255) NOT NULL,
    fldname  VARCHAR(255) NOT NULL,
    active   VARCHAR(10) NOT NULL DEFAULT '',
    orderid  INT NULL
) ENGINE=InnoDB;

-- Seed diamond_search from maindata's field list. colname/fldname
-- start identical, and id/orderid start identical, per spec.
INSERT INTO diamond_search (id, colname, fldname, active, orderid) VALUES
(1, 'altprice', 'altprice', 'yes', 1),
(2, 'avail', 'avail', 'yes', 2),
(3, 'bgm', 'bgm', 'yes', 3),
(4, 'black_incl', 'black_incl', 'yes', 4),
(5, 'brand', 'brand', 'yes', 5),
(6, 'Cash discount', 'Cash discount', 'yes', 6),
(7, 'Cash price', 'Cash price', 'yes', 7),
(8, 'cert_comment', 'cert_comment', 'yes', 8),
(9, 'CertificateImage', 'CertificateImage', 'yes', 9),
(10, 'CertificateNo', 'CertificateNo', 'yes', 10),
(11, 'CityStateCountry', 'CityStateCountry', 'yes', 11),
(12, 'Clarity', 'Clarity', 'yes', 12),
(13, 'clremark', 'clremark', 'yes', 13),
(14, 'Color', 'Color', 'yes', 14),
(15, 'CrownAngle', 'CrownAngle', 'yes', 15),
(16, 'CrownHeight', 'CrownHeight', 'yes', 16),
(17, 'CuletCondition', 'CuletCondition', 'yes', 17),
(18, 'CuletSize', 'CuletSize', 'yes', 18),
(19, 'CutGrade', 'CutGrade', 'yes', 19),
(20, 'Depthpct', 'Depthpct', 'yes', 20),
(21, 'eyeclean', 'eyeclean', 'yes', 21),
(22, 'Fancycolor', 'Fancycolor', 'yes', 22),
(23, 'FluorescenceColor', 'FluorescenceColor', 'yes', 23),
(24, 'FluorescenceIntensity', 'FluorescenceIntensity', 'yes', 24),
(25, 'fluoroth', 'fluoroth', 'yes', 25),
(26, 'Girdlecond', 'Girdlecond', 'yes', 26),
(27, 'Girdlepct', 'Girdlepct', 'yes', 27),
(28, 'GirdleThinorGirdleThick', 'GirdleThinorGirdleThick', 'yes', 28),
(29, 'height', 'height', 'yes', 29),
(30, 'hold', 'hold', 'yes', 30),
(31, 'id', 'id', 'yes', 31),
(32, 'imglink', 'imglink', 'yes', 32),
(33, 'keytosym', 'keytosym', 'yes', 33),
(34, 'Lab', 'Lab', 'yes', 34),
(35, 'LaserInscription', 'LaserInscription', 'yes', 35),
(36, 'length', 'length', 'yes', 36),
(37, 'list', 'list', 'yes', 37),
(38, 'location', 'location', 'yes', 38),
(39, 'lotno', 'lotno', 'yes', 39),
(40, 'Measurements', 'Measurements', 'yes', 40),
(41, 'Member_Comment', 'Member_Comment', 'yes', 41),
(42, 'milkyid', 'milkyid', 'yes', 42),
(43, 'mkamt', 'mkamt', 'yes', 43),
(44, 'mkdis', 'mkdis', 'yes', 44),
(45, 'mkprice', 'mkprice', 'yes', 45),
(46, 'NatFancyColor', 'NatFancyColor', 'yes', 46),
(47, 'NatFancyColorIntensity', 'NatFancyColorIntensity', 'yes', 47),
(48, 'NatFancyColorOvertone', 'NatFancyColorOvertone', 'yes', 48),
(49, 'notforweb', 'notforweb', 'yes', 49),
(50, 'open inclusion', 'open inclusion', 'yes', 50),
(51, 'orapprice', 'orapprice', 'yes', 51),
(52, 'origin', 'origin', 'yes', 52),
(53, 'Pair', 'Pair', 'yes', 53),
(54, 'pairnm', 'pairnm', 'yes', 54),
(55, 'PairSeparable', 'PairSeparable', 'yes', 55),
(56, 'parcel', 'parcel', 'yes', 56),
(57, 'ParcelStoneCount', 'ParcelStoneCount', 'yes', 57),
(58, 'PavilionAngle', 'PavilionAngle', 'yes', 58),
(59, 'PavilionDepth', 'PavilionDepth', 'yes', 59),
(60, 'pcs', 'pcs', 'yes', 60),
(61, 'pdflink', 'pdflink', 'yes', 61),
(62, 'Polish', 'Polish', 'yes', 62),
(63, 'Price', 'Price', 'yes', 63),
(64, 'Rap', 'Rap', 'yes', 64),
(65, 'RapOff', 'RapOff', 'yes', 65),
(66, 'shadeid', 'shadeid', 'yes', 66),
(67, 'Shape', 'Shape', 'yes', 67),
(68, 'shapeoth', 'shapeoth', 'yes', 68),
(69, 'sizedesc', 'sizedesc', 'yes', 69),
(70, 'srtcla', 'srtcla', 'yes', 70),
(71, 'srtcol', 'srtcol', 'yes', 71),
(72, 'srtcts', 'srtcts', 'yes', 72),
(73, 'srtcut', 'srtcut', 'yes', 73),
(74, 'srtflu', 'srtflu', 'yes', 74),
(75, 'srtloc', 'srtloc', 'yes', 75),
(76, 'srtpol', 'srtpol', 'yes', 76),
(77, 'srtshp', 'srtshp', 'yes', 77),
(78, 'srtsiz', 'srtsiz', 'yes', 78),
(79, 'srtsym', 'srtsym', 'yes', 79),
(80, 'starlength', 'starlength', 'yes', 80),
(81, 'statusid', 'statusid', 'yes', 81),
(82, 'StockNo', 'StockNo', 'yes', 82),
(83, 'Symmetry', 'Symmetry', 'yes', 83),
(84, 'table_incl', 'table_incl', 'yes', 84),
(85, 'Tablepct', 'Tablepct', 'yes', 85),
(86, 'totamt', 'totamt', 'yes', 86),
(87, 'TradeShow', 'TradeShow', 'yes', 87),
(88, 'TreatmentLD', 'TreatmentLD', 'yes', 88),
(89, 'vidlink', 'vidlink', 'yes', 89),
(90, 'viewyn', 'viewyn', 'yes', 90),
(91, 'Weight', 'Weight', 'yes', 91),
(92, 'white_incl', 'white_incl', 'yes', 92),
(93, 'width', 'width', 'yes', 93),
(94, 'heart', 'heart', 'yes', 94),
(95, 'black_incl_crown', 'black_incl_crown', 'yes', 95),
(96, 'white_incl_crown', 'white_incl_crown', 'yes', 96),
(97, 'ratio', 'ratio', 'yes', 97);

-- RESULTS table: a live clone of diamond_search (structure + data),
-- created fresh here so it stays a true snapshot on new installs too.
CREATE TABLE results LIKE diamond_search;
INSERT INTO results SELECT * FROM diamond_search;

-- DIAMOND_DETAILS table: a live clone of results, plus a data_type column.
CREATE TABLE diamond_details LIKE results;
INSERT INTO diamond_details SELECT * FROM results;
ALTER TABLE diamond_details ADD COLUMN data_type VARCHAR(50) NULL AFTER fldname;

-- ADV_FILTER table: a live clone of diamond_search.
CREATE TABLE adv_filter LIKE diamond_search;
INSERT INTO adv_filter SELECT * FROM diamond_search;

-- ------------------------------------------------------------
-- LOOKUP TABLES (cut, fluorescence, polish, symmetry, shape,
-- location) generated from data_dictionary.xlsx. `id` is the
-- primary key and auto-increment in every table.
-- ------------------------------------------------------------
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
    `shortnm`            VARCHAR(255) NULL,
    `active`             VARCHAR(10) NOT NULL DEFAULT 'yes',
    `order`              INT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS availability (
    id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    avail   VARCHAR(50) NOT NULL,
    shortnm VARCHAR(50) NOT NULL DEFAULT '',
    color   VARCHAR(10) NULL,
    active  VARCHAR(10) NOT NULL DEFAULT 'yes',
    `order` INT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO availability (avail, active, `order`) VALUES
('Available', 'yes', 1),
('Memo / Consignment', 'yes', 2),
('Hold', 'yes', 3),
('Trade Show', 'yes', 4);

-- ------------------------------------------------------------
-- SIZE and LAB tables (from size.sql / lab.sql). `size.id` is
-- NOT auto-increment (ids come from your data source); `lab.id`
-- is auto-increment with a unique constraint on `lab`.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `size` (
    `id`       INT NOT NULL,
    `sizedesc` VARCHAR(50) NOT NULL,
    `sizefr`   DECIMAL(10,2) NOT NULL,
    `sizeto`   DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `size` (`id`, `sizedesc`, `sizefr`, `sizeto`) VALUES
(2, '0.01-0.99', 0.01, 0.99),
(3, '0.90-0.99', 0.90, 0.99),
(4, '1.00-1.49', 1.00, 1.49),
(5, '1.50-1.99', 1.50, 1.99),
(6, '2.00-2.99', 2.00, 2.99),
(7, '3.00-3.99', 3.00, 3.99),
(8, '4.00-4.99', 4.00, 4.99),
(9, '5.00-5.99', 5.00, 5.99),
(10, '6.00-6.99', 6.00, 6.99),
(11, '7.00-7.99', 7.00, 7.99),
(12, '8.00-8.99', 8.00, 8.99),
(13, '9.00-9.99', 9.00, 9.99),
(14, '10 UP', 10.00, 99999.99);

CREATE TABLE IF NOT EXISTS `lab` (
    `id`  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `lab` VARCHAR(10) NOT NULL,
    `active` VARCHAR(10) NOT NULL DEFAULT 'yes',
    PRIMARY KEY (`id`),
    UNIQUE KEY `lab` (`lab`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=6;

INSERT INTO `lab` (`id`, `lab`) VALUES
(5, 'AGS'),
(1, 'ALL'),
(4, 'GIA'),
(2, 'HRD'),
(3, 'IGI');

-- ------------------------------------------------------------
-- CLARITY and COLOR tables (from clarity.sql / color.sql). Both
-- use auto-increment `id` with a unique constraint on the label
-- column. color.active uses 'yes'/'no' (converted from the
-- source dump's 0/1 int) to match this app's `active` convention.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `clarity` (
    `id`      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `clarity` VARCHAR(10) NOT NULL,
    `active`  VARCHAR(10) NOT NULL DEFAULT 'yes',
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

-- ------------------------------------------------------------
-- MEMO FEATURE tables: customer, memo, dmemo (from the supplied
-- dumps). memo/dmemo hold footer/signature text used per user
-- level (4 -> dmemo, 5 -> memo).
-- ------------------------------------------------------------
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

CREATE TABLE IF NOT EXISTS selection (
    emailid VARCHAR(100) NOT NULL,
    stockno VARCHAR(25) NOT NULL,
    id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    KEY idx_selection_emailid (emailid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS self_short_urls (
    urlid  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ids    VARCHAR(50) NOT NULL,
    timeon VARCHAR(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS customer (
    custnm  VARCHAR(100) NOT NULL,
    address VARCHAR(250) NOT NULL DEFAULT '',
    email   VARCHAR(100) NOT NULL DEFAULT '',
    shortnm VARCHAR(10)  NOT NULL DEFAULT '',
    custid  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=112;

INSERT INTO `customer` (`custnm`, `address`, `email`, `shortnm`, `custid`) VALUES
('Select Customer', '', '', 'Test', 17),
('ABC and co', 'test', 'test@gmail.com', 'TST', 18),
('XYZ and co', 'Testadd', 'testxyz@gmail.com', 'XYZ', 27),
('VASANT', 'aaa', 'abc@gmail.com', 'vbggvf', 33),
('ARYAN', 'abc', 'Aaryan@gmail.com', '', 35),
('AARYAN', '903, GOANDEVI hill ,GOANDEVI road Bhandup west', '', '', 37),
('TEST1', 'test1', 'test1@gmail.com', 'test1', 38),
('MANISH JEWELLERS LLC', 'UNIT 2026, BUILDING NO. 2 GOLD AND DIAMOND PARK. AL QUOZ', '', '', 39),
('CARA JEWELLERS FZCO', 'GDP . DUBAI UAE', '', 'CARA JEWEL', 40),
('KGK DIAMOND DMCC', 'UNIT 51 A ALMAS TOWER. JLT. DUBAI. UAE', '', '', 41),
('LUXURY GEMS AND DIAMOND TRADING DMCC', '28 A , ALMAS TOWER. JLT. DUBAI. UAE', '', '', 42),
('BRILLIANT DIAMOND DMCC', 'UNIT 37F ALMAS TOWER. JLT', '', '', 43),
('RAEN FZCO', 'DUBAI UAE. PUNIT :- 0522648372', '', '', 44),
('BAFLEH JEWELLERY LLC', 'Hind plaza 10b, 3rd floor gold Souq. Deira.', '', '', 45),
('BUTI VALLAHDAS MAMIYA JEWELLERY LLC', 'GOLD AND DIAMOND PARK. DUBAI UAE', '', '', 46),
('DIAMOND DEAL LLC', 'OFFICE 103, HIND PLAZA 4B. GOLD SOUQ. DEIRA. DUBAI', '', '', 47),
('AL KANZ JEWELLERY LLC', 'GOLD SOUQ. DEIRA. DUBAI. UAE', '', 'AL KANZ JE', 48),
('STARGEMS JEWELLERY DMCC', 'UNIT 31 ALMAS TOWER. JLT', '', 'STARGEMS J', 49),
('AL SAMAKEH DIAMONDS AND JEWELLERY DMCC', ' LAITH. DUBAI. UAE', '', '', 50),
('DHAMANI JEWELS LLC', '', '', '', 51),
('ISHTARA JEWELS LLC', 'GOLD SOUQ. DEIRA. DUBAI. UAE', '', '', 52),
('NIVODA DMCC', 'UNIT 49A - 27 ALMAS TOWER. JLT. DUBAI UAE', '', '', 53),
('ZAINA JEWELLERS LLC', 'UNIT 225, GOLD LAND BLDG.,GOLD SOUQ. DEIRA.', '', '', 54),
('NATIONAL JEWELLERY LLC', 'SHOP NO 1 AND 2 GOLD SOUQ. DEIRA DUBAI . UAE', '', '', 55),
('RISING STAR FZCO', 'UNIT 16 G ALMAS TOWER. JLT. DUBAI', '', 'RISING STA', 56),
('HASSAN GEMS LLC', 'GOLD SOUQ. DEIRA. DUBAI. UAE', '', '', 57),
('DEEP SHAH', '', '', 'DEEP SHAH', 58),
('YRS DIAM DMCC', '', '', 'YRS DIAM D', 59),
('K S JEWELS LLC', 'GOLD SOUQ. DEIRA. DUBAI. UAE', '', 'K S JEWELS', 60),
('SUNNY DIAM LLC', 'GOLD SOUQ. DEIRA. DUBAI. UAE', '', '', 61),
('AARAV DIAM LLC', 'GOLD LAND. BLDG., GOLD SOUQ. DEIRA. ', '', 'AARAV DIAM', 62),
('VISHAL GORADIA ; EMID :- 784197884816816', 'Deira gold souk', '', '', 63),
('DHYAN DIAM LLC', 'GOLD SOUQ. DEIRA. DUBAI. UAE', '', 'DHYAN DIAM', 64),
('SHASHANK DIAMOND LLC', 'GOLD SOUQ. DEIRA. DUBAI. UAE', '', '', 65),
('PRECIOUS GEMS LLC', 'ROYAL DIAMOND BLDG GOLD SOUQ. DEIRA. DUBAI', '', '', 66),
('MOHANLAL VALLABHDAS AND  BROS LLC', 'SHOP NO 5 GOLD SOUQ. DEIRA. DUBAI', '', '', 67),
('NEW VERONA FZCO', '', '', '', 68),
('SOVEREIGN GEMS LLC', '', '', '', 69),
('ELITE STAR FZ-LLC - AMITBHAI 0549984906', '', '', '', 70),
('AMAN DIAMONDS AND JEWELLERY LLC', 'GOLD SOUQ. DEIRA. DUBAI. UAE', '', '', 71),
('DEVJI AURUM DMCC', '', '', 'DEVJI AURU', 72),
('TEJORI GEMS LLC', 'GDP . DUBAI UAE', '', 'TEJORI GEM', 73),
('KAIA DIAMONDS FZCO', 'UNIT 8-I SILVER TOWER. JLT DUBAI. UAE', '', 'KAIA DIAMO', 74),
('WAZNI JEWELLERY', 'ABU DHABI. UAE', '', '', 75),
('J MANAK FZCO', 'UNIT 31 H ALMAS TOWER', '', '', 76),
('SAMAY JEWEL FZC', '', '', '', 77),
('PRISTINE JEWELS FZCO', '', '', 'PRISTINE J', 78),
('HRD ANTWERP DMCC', '', '', '', 79),
('S R DIA', '', '', '', 80),
('RENEE INTERNATIONAL DMCC', '', '', '', 81),
('DIAMOND DIRECT LLC', '', '', 'DIAMOND DI', 82),
('VIE JEWELS DMCC', '', '', 'VIE JEWELS', 83),
('DIAMOND PASSION', 'M-47 GREATER KAILASH, NEW DELHI', '', 'DIAMOND PA', 84),
('SAEID GEM GOLD ', '', '', '', 85),
('SAEID GEM GOLD & JEWELLERY TRADING LLC', 'SHOP NO 16. GOLD SOUQ.', '', 'SAEID GEM ', 86),
('AL NOBALA DIAMONDS LLC', '', '', 'AL NOBALA ', 87),
('REWA GEMS FZCO', '26 C ALMAS TOWER', '', 'REWA GEMS ', 88),
('HOUSE OF WINDSOR ', '', '', 'HOUSE OF W', 89),
('NEMI GEMS FZE', 'NEME GEMS FZE', '', '', 90),
('NOVEL FINE JEWELLERY DMCC', '', '', 'NOVEL FINE', 91),
('FUTURE GEMS FZCO', '', '', 'FUTURE GEM', 92),
('FAKIH DIAMONDS DMCC', 'FAKIH DIAMONDS DMCC', '', '', 93),
('AUROSTAR FZCO', 'AUROSTAR FZCO', '', '', 94),
('HIR INVESTMENTS LTD.', '', '', 'HIR INVEST', 95),
('INFINITY DIAMONDS LLC', 'GOLD HOUSE BUILDING . AL RAS . DEIRA.', '', '', 96),
('MONILI JEWELLERS FZCO', '', '', 'MONILI JEW', 97),
('KGK DIAMOND AND JEWELLERY DMCC', '', '', 'KGK DIAMON', 98),
('VRAMS DIAMONDS FZCO', '', '', 'VRAMS DIAM', 99),
('BELADAMAZ JEWELLERY FZCO', '', '', 'BELADAMAZ ', 100),
('YRS DIAM FZCO', '', '', 'YRS DIAM F', 101),
('STARGEMS JEWELLERY FZCO', '', '', 'STARGEMS J', 102),
('CARAT DIAMOND COMPANY FZCO', '40G ALMAS TOWER', '', 'CARAT DIAM', 103),
('NOVEL FINE JEWELLERY FZCO', '', '', 'NOVEL FINE', 104),
('BRIJESH PATEL', '', '', 'BRIJESH PA', 105),
('LUXURY GEMS AND DIAMOND TRADING FZCO', '', '', 'LUXURY GEM', 106),
('VVS DIAMONDS FZCO', '', '', 'VVS DIAMON', 107),
('PRANA DIAM LLC', '', '', 'PRANA DIAM', 108),
('DIAMOND ROCKS TRADING FZCO', '', '', 'DIAMOND RO', 109),
('ARVA DIAMONDS FZCO', '12-C ALMAS TOWER.', '', 'ARVA DIAMO', 110),
('KARATISE FZCO', 'GOLD TOWER AG-07-F', '', 'KARATISE F', 111);

CREATE TABLE IF NOT EXISTS memo (
    id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company VARCHAR(150) NOT NULL DEFAULT '',
    address VARCHAR(250) NOT NULL DEFAULT '',
    telno   VARCHAR(50)  NOT NULL DEFAULT '',
    fax     VARCHAR(50)  NOT NULL DEFAULT '',
    gsm     VARCHAR(100) NOT NULL DEFAULT '',
    email   VARCHAR(150) NOT NULL DEFAULT '',
    web     VARCHAR(150) NOT NULL DEFAULT '',
    field1 TEXT NOT NULL,
    field2 TEXT NOT NULL,
    field3 TEXT NOT NULL,
    field4 TEXT NOT NULL,
    field5 TEXT NOT NULL,
    field6 TEXT NOT NULL,
    dated  INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=14;

INSERT INTO memo (id, company, address, telno, fax, gsm, email, web, field1, field2, field3, field4, field5, field6, dated) VALUES
(13, 'Veera Dimon b.v', 'Hoveniersstraat 30, Office 318-319, P.Box - 184, Antwerpen 2018, Belgium.', '(03) 232 - 9747', '(03) 234-9546', '0472-922800 / 0479-262844', 'sales@veeradimon.com', 'www.veeradimon.com', 'Received by', 'For Veera Dimon b.v', ' Receiver''s signature', '', '', 'These goods may only be sold with our authorisation and must be returned upon first request.  The goods are for sale, but ONLY WITH OUR AGREEMENT or have to be returned on request. By signature of this document, the consignee agrees to take full responsibility for the goods as detailed above. Under no circumstances may the goods be further consigned without the prior authorisation of the consigner/owner of the goods. At all times the goods will be under the ownership of Veera Dimon bv.<strong>Discounts will be based on the latest Rapaport list.</strong>', 1549350957);

CREATE TABLE IF NOT EXISTS dmemo (
    id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company VARCHAR(150) NOT NULL DEFAULT '',
    address VARCHAR(250) NOT NULL DEFAULT '',
    telno   VARCHAR(50)  NOT NULL DEFAULT '',
    fax     VARCHAR(50)  NOT NULL DEFAULT '',
    gsm     VARCHAR(100) NOT NULL DEFAULT '',
    email   VARCHAR(150) NOT NULL DEFAULT '',
    web     VARCHAR(150) NOT NULL DEFAULT '',
    field1 TEXT NOT NULL,
    field2 TEXT NOT NULL,
    field3 TEXT NOT NULL,
    field4 TEXT NOT NULL,
    field5 TEXT NOT NULL,
    field6 TEXT NOT NULL,
    dated  INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=14;

INSERT INTO dmemo (id, company, address, telno, fax, gsm, email, web, field1, field2, field3, field4, field5, field6, dated) VALUES
(13, 'Veera Dimon FZCO', 'Hoveniersstraat 30, Office 318-319, P.Box - 184, Antwerpen 2018, Belgium.', '(03) 232 - 9747', '(03) 234-9546', '0472-922800 / 0479-262844', 'sales@veeradimon.com', 'www.veeradimon.com', 'Received by', 'For Veera Dimon FZCO', ' Receiver''s signature', ' ', '.', 'These goods may only be sold with our authorisation and must be returned upon first request.  The goods are for sale, but ONLY WITH OUR AGREEMENT or have to be returned on request. By signature of this document, the consignee agrees to take full responsibility for the goods as detailed above. Under no circumstances may the goods be further consigned without the prior authorisation of the consigner/owner of the goods. At all times the goods will be under the ownership of Veera Dimon FZCO.<strong>Discounts will be based on the latest Rapaport list.</strong>', 1549350957);

-- Login audit trail (recommended addition for security monitoring)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS login_audit (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(100) NOT NULL,
    ipadd       VARCHAR(45)  NOT NULL,
    success     TINYINT(1)   NOT NULL,
    attempted_at DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_attempted_at (attempted_at)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- REMEMBER-ME tokens ("Remember me" on the login screen)
-- Uses the selector/validator pattern: selector is looked up
-- directly, validator is compared as a hash — so a leaked DB row
-- alone can't be replayed, and a leaked cookie is useless without
-- matching the stored hash.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS remember_tokens (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id      INT UNSIGNED NOT NULL,
    selector     CHAR(24)  NOT NULL UNIQUE,
    validator_hash CHAR(64) NOT NULL,
    expires_at   DATETIME  NOT NULL,
    created_at   DATETIME  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- BACKUP LOG (record of backups taken from the admin dashboard)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS backup_log (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    filename     VARCHAR(255) NOT NULL,
    size_bytes   BIGINT UNSIGNED NOT NULL DEFAULT 0,
    created_by   VARCHAR(100) NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- FANCY (fancy-color reference table): color/intensity combos with
-- a matching description and short abbreviation, used to derive
-- maindata.fancy_short during CSV import (see Diamond Data Upload).
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS fancy (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    color        VARCHAR(100) NOT NULL,
    intensity    VARCHAR(100) NOT NULL DEFAULT '',
    description  VARCHAR(255) NOT NULL DEFAULT '',
    abbreviation VARCHAR(50)  NOT NULL DEFAULT ''
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- New maindata fields: fancy (yes/no — set by the upload program
-- when a row's Color contains "Fancy"), fancy_short (looked up from
-- the fancy table's description/abbreviation), and memo (yes/no,
-- default no). `hold` already exists in maindata (VARCHAR(255),
-- nullable, no default) — rather than re-adding it, this gives it
-- the requested 'no' default and backfills existing NULL rows,
-- leaving its type untouched to avoid any risk to existing data.
ALTER TABLE maindata
    ADD COLUMN fancy       VARCHAR(10) NOT NULL DEFAULT 'no',
    ADD COLUMN fancy_short VARCHAR(50) NULL,
    ADD COLUMN memo        VARCHAR(10) NOT NULL DEFAULT 'no';

ALTER TABLE maindata ALTER COLUMN hold SET DEFAULT 'no';
UPDATE maindata SET hold = 'no' WHERE hold IS NULL OR hold = '';

-- "Fancy" already exists as a color option (id 33) but was inactive
-- — activate it so it appears as a selectable pill in Diamond Search.
UPDATE `color` SET active = 'yes' WHERE `color` = 'Fancy';

