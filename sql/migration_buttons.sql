-- ============================================================
-- Migration: `buttons` — the buttons in the site header
-- (Home page and every other page that shows the header bar:
-- website pages, Diamond Search, Results, View Cart, and any
-- design / template you upload later).
--
--   button_label  text shown on the button
--   active        yes = shown, no = hidden
--   URL           where the browser goes when the button is clicked
--
-- There are exactly 10 slots (id 1–10). Buttons are shown left to
-- right in id order. Edit them in Admin → Manage Tables → Header
-- Buttons. The admin screen will not let a 11th row be added.
--
-- URL can be:
--   /about.php            a page on this site (recommended)
--   about.php             same, without the leading slash
--   modules/user/diamond_search.php
--   https://example.com   another website
--   mailto:sales@x.com    tel:+3232000000    #contact
--
-- Rows 1–4 reproduce the buttons the header had before this change
-- (Home, About Us, Inventory, Contact Us), so the site looks the same
-- right after running this file. Rows 5–10 are empty and inactive.
--
-- Safe to run more than once: the table is only created if missing,
-- and existing rows (same id) are left untouched.
-- Until this is run the header keeps showing the 4 original buttons.
-- ============================================================

CREATE TABLE IF NOT EXISTS `buttons` (
  `id`           INT          NOT NULL AUTO_INCREMENT,
  `button_label` VARCHAR(40)  NOT NULL DEFAULT '',
  `active`       VARCHAR(3)   NOT NULL DEFAULT 'no' COMMENT 'yes / no',
  `URL`          VARCHAR(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `buttons` (`id`, `button_label`, `active`, `URL`) VALUES
(1,  'Home',       'yes', '/index.php'),
(2,  'About Us',   'yes', '/about.php'),
(3,  'Inventory',  'yes', '/inventory.php'),
(4,  'Contact Us', 'yes', '/contact.php'),
(5,  '',           'no',  ''),
(6,  '',           'no',  ''),
(7,  '',           'no',  ''),
(8,  '',           'no',  ''),
(9,  '',           'no',  ''),
(10, '',           'no',  '');

SELECT `id`, `button_label`, `active`, `URL` FROM `buttons` ORDER BY `id`;
