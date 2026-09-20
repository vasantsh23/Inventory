-- ============================================================
-- Migration: adds `Memo` to `setup`.
--
-- 'yes' (default) = current behaviour unchanged: the customer picker
--   plus Memo-1 / Memo-3 buttons show on the Results page for any
--   logged-in user whose role level is 4 or 5.
-- 'no' = those are hidden on Results for everyone, regardless of
--   level — a single site-wide off switch for the memo feature.
--
-- See modules/user/results.php ($canMemo) and Admin > Site Setup >
-- "Memo Feature".
--
-- Safe to run once in phpMyAdmin's SQL tab against your existing
-- database.
-- ============================================================

ALTER TABLE `setup`
    ADD COLUMN `Memo` VARCHAR(10) NOT NULL DEFAULT 'yes';
