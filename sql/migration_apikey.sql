-- ============================================================
-- Migration: Admin > API (API keys for customers).
--
-- Safe on BOTH kinds of database:
--   - No `apikey` table yet  -> creates it.
--   - The existing `apikey` table (name, company, emailid, apikey, id)
--     -> keeps every column and every existing key unchanged (so
--        getapikey.php keeps working) and only adds what's missing:
--          custid      which customer the key belongs to (one key per
--                      customer; NULL for keys made before this change)
--          created_at  when the key was generated
--        plus a unique index on custid.
--
-- Run ONCE in phpMyAdmin's SQL tab. Running it again does no harm.
-- ============================================================

CREATE TABLE IF NOT EXISTS apikey (
    id          INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
    custid      INT          NULL,
    name        VARCHAR(100) NOT NULL,
    company     VARCHAR(100) NOT NULL,
    emailid     VARCHAR(100) NOT NULL,
    apikey      VARCHAR(25)  NOT NULL,
    created_at  DATETIME     NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_apikey_custid (custid),
    KEY idx_apikey_apikey (apikey)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Existing (older) apikey table: add custid if it isn't there.
SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'apikey' AND COLUMN_NAME = 'custid');
SET @sql := IF(@col = 0,
    'ALTER TABLE apikey ADD COLUMN custid INT NULL AFTER id, ADD UNIQUE KEY uq_apikey_custid (custid)',
    'SELECT ''custid already present'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ... and created_at. Added without a default first, so keys that
-- already exist are left blank (their real date isn't known) instead
-- of all getting today's date; new keys get the time they're made.
SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'apikey' AND COLUMN_NAME = 'created_at');
SET @sql := IF(@col = 0,
    'ALTER TABLE apikey ADD COLUMN created_at DATETIME NULL',
    'SELECT ''created_at already present'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
ALTER TABLE apikey MODIFY COLUMN created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP;

-- ... and an index on apikey, so key look-ups stay fast.
SET @idx := (SELECT COUNT(*) FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'apikey' AND COLUMN_NAME = 'apikey');
SET @sql := IF(@idx = 0,
    'ALTER TABLE apikey ADD KEY idx_apikey_apikey (apikey)',
    'SELECT ''apikey index already present'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Check:
SELECT id, custid, name, company, emailid, created_at FROM apikey ORDER BY id;
