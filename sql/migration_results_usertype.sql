-- ============================================================
-- Migration: per-user-type Results columns.
--
-- Adds a `usertype` column to the `results` table (FK -> user_types.id,
-- the same id stored in user.usertype). The Results and View Cart /
-- View Selected pages now load ONLY the `results` rows whose usertype
-- matches the viewer's user type. If a user type has no rows at all in
-- `results`, those pages show an error message instead of a table.
--
-- Guests (setup.loginscrn = 'no', nobody logged in) use the user type
-- set by GUEST_RESULTS_USERTYPE_ID in config/config.php (default 1,
-- 'Standard User').
--
-- What this script does to your existing data:
--   1. Adds results.usertype.
--   2. Assigns every existing results row to user type id 1
--      ('Standard User').
--   3. Copies that same column set to EVERY other user type, so nobody
--      loses their Results page after the upgrade. Afterwards, edit or
--      delete rows per user type in Admin -> Results.
--   4. Makes the column NOT NULL and adds the foreign key + an index.
--
-- Run ONCE in phpMyAdmin's SQL tab against your existing database.
-- Do NOT run on a new database created from the current
-- sql/schema.sql — it already includes this change.
-- ============================================================

ALTER TABLE results
    ADD COLUMN usertype INT UNSIGNED NULL AFTER id;

-- Step 2: existing rows belong to user type 1 ('Standard User').
-- Change the 1 here if your Standard User row has a different id.
UPDATE results SET usertype = 1 WHERE usertype IS NULL;

-- Step 3: copy the column set to every other user type.
INSERT INTO results (usertype, colname, fldname, active, orderid)
SELECT t.id, r.colname, r.fldname, r.active, r.orderid
FROM user_types t
CROSS JOIN results r
WHERE r.usertype = 1
  AND t.id <> 1;

-- Step 4: lock it down.
ALTER TABLE results
    MODIFY COLUMN usertype INT UNSIGNED NOT NULL,
    ADD INDEX idx_results_usertype_order (usertype, orderid),
    ADD CONSTRAINT fk_results_usertype FOREIGN KEY (usertype) REFERENCES user_types(id);

-- Check: rows per user type.
SELECT t.id, t.usertype, COUNT(r.id) AS result_rows,
       SUM(r.active = 'yes') AS active_columns
FROM user_types t
LEFT JOIN results r ON r.usertype = t.id
GROUP BY t.id, t.usertype
ORDER BY t.id;

-- ------------------------------------------------------------
-- LATER: when you add a NEW user type, its users will see the
-- "not set up" error on Results until it has rows here. To start it
-- from an existing type's columns, run (set both ids first):
--
--   INSERT INTO results (usertype, colname, fldname, active, orderid)
--   SELECT <new_type_id>, colname, fldname, active, orderid
--   FROM results WHERE usertype = <copy_from_type_id>;
-- ------------------------------------------------------------
