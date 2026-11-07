-- ============================================================
-- Migration: Shade field + Range option
--
-- 1. maindata.shade        VARCHAR(50) NULL  (character field)
-- 2. results               a "Shade" column (fldname = shade) for every
--                          user type, active, added at the end, so it
--                          shows on Results and View Cart / View
--                          (Selected). Rename / reorder / deactivate it
--                          per user type in Admin > Results.
-- 3. diamond_search.range  VARCHAR(10) NOT NULL DEFAULT 'no'
--    adv_filter.range      (same, so Advanced Filter sections can use it)
--                          'yes' = on Diamond Search, ticking two options
--                          also ticks every option between them
--                          (e.g. Color D + G -> E and F too).
-- 4. diamond_search / adv_filter / uploadref get a 'shade' row, left
--    INACTIVE / unmapped: activate it in Diamond Search Fields if you
--    want a Shade text filter, and enter the CSV header name in Upload
--    Field Mapping so Diamond Data Upload fills the new column.
--
-- RANGE is a reserved word in MySQL 8, so the column is always written
-- `range` (with backticks).
--
-- Safe to run more than once in phpMyAdmin's SQL tab: every step
-- checks first and skips what already exists.
-- ============================================================

-- 1. maindata.shade
SET @has := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'maindata' AND COLUMN_NAME = 'shade');
SET @sql := IF(@has = 0,
    'ALTER TABLE `maindata` ADD COLUMN `shade` VARCHAR(50) NULL',
    'SELECT ''maindata.shade already present'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3. diamond_search.range / adv_filter.range
SET @has := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'diamond_search' AND COLUMN_NAME = 'range');
SET @sql := IF(@has = 0,
    'ALTER TABLE `diamond_search` ADD COLUMN `range` VARCHAR(10) NOT NULL DEFAULT ''no'' AFTER `active`',
    'SELECT ''diamond_search.range already present'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'adv_filter' AND COLUMN_NAME = 'range');
SET @sql := IF(@has = 0,
    'ALTER TABLE `adv_filter` ADD COLUMN `range` VARCHAR(10) NOT NULL DEFAULT ''no'' AFTER `active`',
    'SELECT ''adv_filter.range already present'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2. results: one active "Shade" column per user type, at the end.
--    (extract = 'no' keeps it out of the API stock CSV until you choose
--    otherwise; skipped automatically if results has no extract column.)
SET @hasExtract := (SELECT COUNT(*) FROM information_schema.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'results' AND COLUMN_NAME = 'extract');
SET @sql := IF(@hasExtract = 0,
    'INSERT INTO results (usertype, colname, fldname, active, orderid)
     SELECT t.id, ''Shade'', ''shade'', ''yes'',
            COALESCE((SELECT MAX(r.orderid) FROM results r WHERE r.usertype = t.id), 0) + 1
     FROM user_types t
     WHERE NOT EXISTS (SELECT 1 FROM results x WHERE x.usertype = t.id AND x.fldname = ''shade'')',
    'INSERT INTO results (usertype, colname, fldname, active, extract, orderid)
     SELECT t.id, ''Shade'', ''shade'', ''yes'', ''no'',
            COALESCE((SELECT MAX(r.orderid) FROM results r WHERE r.usertype = t.id), 0) + 1
     FROM user_types t
     WHERE NOT EXISTS (SELECT 1 FROM results x WHERE x.usertype = t.id AND x.fldname = ''shade'')');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4. Inactive filter rows + unmapped upload mapping (see header).
INSERT INTO diamond_search (colname, fldname, active, `range`, orderid)
SELECT 'Shade', 'shade', 'no', 'no', COALESCE(MAX(orderid), 0) + 1 FROM diamond_search
HAVING NOT EXISTS (SELECT 1 FROM diamond_search WHERE fldname = 'shade');

INSERT INTO adv_filter (colname, fldname, active, `range`, orderid)
SELECT 'Shade', 'shade', 'no', 'no', COALESCE(MAX(orderid), 0) + 1 FROM adv_filter
HAVING NOT EXISTS (SELECT 1 FROM adv_filter WHERE fldname = 'shade');

INSERT INTO uploadref (colname, excolname, active)
SELECT 'shade', '', 'yes' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM uploadref WHERE colname = 'shade');

-- Check
SELECT t.usertype, r.colname, r.fldname, r.active, r.orderid
FROM results r JOIN user_types t ON t.id = r.usertype
WHERE r.fldname = 'shade' ORDER BY t.id;
