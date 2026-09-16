-- ============================================================
-- Migration: add user_types table, convert user.usertype to a
-- foreign key referencing it.
--
-- Run this ONCE against an EXISTING database that already has the
-- old ENUM('user','admin','superadmin') usertype column (i.e. any
-- database set up before this migration). Do NOT run this against a
-- brand-new database created from the current sql/schema.sql — that
-- file already reflects the end state below.
--
-- Safe to run in phpMyAdmin's SQL tab, top to bottom, in one go.
-- Existing accounts are automatically mapped:
--   'user'       -> Standard User (level 0)
--   'admin'      -> Admin         (level 8)
--   'superadmin' -> Super Admin   (level 9)
-- ============================================================

CREATE TABLE IF NOT EXISTS user_types (
    id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usertype VARCHAR(100) NOT NULL UNIQUE,
    level    TINYINT UNSIGNED NOT NULL
) ENGINE=InnoDB;

INSERT INTO user_types (usertype, level)
SELECT * FROM (
    SELECT 'Standard User' AS usertype, 0 AS level
    UNION ALL SELECT 'Admin', 8
    UNION ALL SELECT 'Super Admin', 9
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM user_types WHERE user_types.usertype = seed.usertype);

-- Add a temporary nullable column to hold the new FK values.
ALTER TABLE user ADD COLUMN usertype_new INT UNSIGNED NULL AFTER usertype;

-- Map every existing account to the matching new user_types row.
UPDATE user u
JOIN user_types t ON (
    (u.usertype = 'user'       AND t.usertype = 'Standard User') OR
    (u.usertype = 'admin'      AND t.usertype = 'Admin') OR
    (u.usertype = 'superadmin' AND t.usertype = 'Super Admin')
)
SET u.usertype_new = t.id;

-- Safety net: anything unmapped (shouldn't happen) defaults to Standard User.
UPDATE user
SET usertype_new = (SELECT id FROM user_types WHERE usertype = 'Standard User' LIMIT 1)
WHERE usertype_new IS NULL;

-- Swap the columns: drop the old ENUM, promote the new one in its place.
ALTER TABLE user DROP COLUMN usertype;
ALTER TABLE user CHANGE COLUMN usertype_new usertype INT UNSIGNED NOT NULL;

ALTER TABLE user
    ADD CONSTRAINT fk_user_usertype FOREIGN KEY (usertype) REFERENCES user_types(id);

-- Verify: every account should now show a readable type and level.
SELECT u.id, u.username, t.usertype, t.level
FROM user u JOIN user_types t ON t.id = u.usertype;
