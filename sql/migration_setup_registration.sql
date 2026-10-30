-- ============================================================
-- Migration: self-registration settings in `setup`.
--
-- `Admin emailid` : address that receives the "new registration"
--                   e-mail. If left blank, emailid1 / emailid2 are
--                   used instead.
-- `registration`  : 'yes' (default) shows the Register button on the
--                   login screen and opens register.php. 'no' hides the
--                   button and closes the registration page.
--
-- New accounts are created with approval = 'pending' and the default
-- user type, so they can't log in until an admin approves them in
-- Admin > User.
--
-- Run ONCE in phpMyAdmin's SQL tab against your existing database.
-- Do NOT run on a new database created from the current
-- sql/schema.sql — it already includes these columns.
-- ============================================================

ALTER TABLE `setup`
    ADD COLUMN `Admin emailid` VARCHAR(150) NULL,
    ADD COLUMN `registration`  VARCHAR(10)  NOT NULL DEFAULT 'yes';

-- Then set the admin address, e.g.:
-- UPDATE `setup` SET `Admin emailid` = 'admin@yourdomain.com';
