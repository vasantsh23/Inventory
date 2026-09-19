-- ============================================================
-- Migration: adds `rounding_rules` — lets an admin pick, from the
-- "Amount Rounding Rules" CRUD screen, how the Amount (`totamt`)
-- column is rounded for display on the Results and View Cart
-- (view_results.php) pages. See includes/functions.php's
-- round_amount_apply() / get_active_rounding_rule() / and the
-- display helper used by modules/user/results.php and
-- modules/user/view_results.php.
--
-- Seeded with the previous hardcoded behavior ("round up to next
-- whole dollar") already marked active, so nothing changes for
-- existing users until they pick a different rule from the admin
-- screen.
--
-- Safe to run once in phpMyAdmin's SQL tab against your existing
-- database — this is a brand-new table, nothing existing is touched.
-- ============================================================

CREATE TABLE IF NOT EXISTS `rounding_rules` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `rule_name`   VARCHAR(150)   NOT NULL,
    `method`      VARCHAR(50)    NOT NULL,
    `increment`   DECIMAL(10,4)  NULL,
    `active`      VARCHAR(10)    NOT NULL DEFAULT 'no',
    `description` VARCHAR(255)   NULL,
    `sort_order`  INT            NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `rounding_rules` (`rule_name`, `method`, `increment`, `active`, `description`, `sort_order`) VALUES
('Round up to next whole dollar (current default)', 'ceil_whole', NULL, 'yes', 'e.g. 10.01 -> 11, 10.99 -> 11. This is the built-in behavior that was already in place, so nothing changes unless you pick a different rule.', 1),
('Round to nearest whole dollar', 'round_whole', NULL, 'no', 'e.g. 125.49 -> 125, 125.50 -> 126.', 2),
('Round down to whole dollar', 'floor_whole', NULL, 'no', 'e.g. 10.99 -> 10.', 3),
('Round to nearest 5 cents', 'nearest_increment', 0.05, 'no', 'e.g. 10.02 -> 10.00, 10.03 -> 10.05. Change "increment" to round to any other step (10 cents, 25 cents, ...) without needing a new rule type.', 4),
('Always round up to nearest 5 cents', 'ceil_increment', 0.05, 'no', 'e.g. 10.01 -> 10.05, 10.06 -> 10.10.', 5),
('Always round down to nearest 5 cents', 'floor_increment', 0.05, 'no', 'e.g. 10.09 -> 10.05, 10.05 -> 10.05.', 6),
('No rounding (exact amount)', 'none', NULL, 'no', 'Shows Amount exactly as stored, no rounding at all.', 7);
