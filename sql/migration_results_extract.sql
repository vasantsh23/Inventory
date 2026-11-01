-- ============================================================
-- Migration: results.extract — which columns go into the customer
-- stock CSV (Admin > API > CSV Columns; api/stock_csv.php).
--
--   'yes' (default) = the column is in the CSV
--   'no'            = left out of the CSV
--
-- Independent of results.active (which controls the Results page).
-- The CSV uses the rows of the Standard User type (or whichever user
-- type APIKEY_CSV_USERTYPE_ID is set to in config/config.php).
--
-- Every existing row starts as 'yes', so after running this the CSV
-- contains ALL columns of that user type — including ones not shown
-- on the Results page. Set the ones customers shouldn't get to 'no'
-- in Admin > API > CSV Columns.
--
-- Run ONCE in phpMyAdmin's SQL tab. Running it again does no harm.
-- ============================================================

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'results' AND COLUMN_NAME = 'extract');
SET @sql := IF(@col = 0,
    'ALTER TABLE results ADD COLUMN extract VARCHAR(10) NOT NULL DEFAULT ''yes'' AFTER active',
    'SELECT ''extract already present'' AS info');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Check: columns per user type going into the CSV.
SELECT t.usertype, SUM(r.extract = 'yes') AS extract_yes, SUM(r.extract = 'no') AS extract_no
FROM results r JOIN user_types t ON t.id = r.usertype
GROUP BY t.id, t.usertype ORDER BY t.id;
