-- ------------------------------------------------------------
-- Migration: `flags` — the flag image shown in the Location
-- column of Results / View Cart.
--
-- maindata.location is matched against flags.location (case and
-- surrounding spaces ignored). `image` is a path relative to this
-- app's root folder (e.g. img/flags/india.svg) or a full URL.
--
-- Safe to run more than once: the table is only created if it is
-- missing, and existing rows (same id) are left untouched.
-- ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `flags` (
  `id`       INT NOT NULL AUTO_INCREMENT,
  `location` VARCHAR(100) NOT NULL,
  `image`    VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT IGNORE INTO `flags` (`id`, `location`, `image`) VALUES
(1, 'IND', 'img/flags/india.svg'),
(2, 'India - GF', 'img/flags/india.svg'),
(3, 'UAE', 'img/flags/uae.svg'),
(4, 'Belgium', 'img/flags/belgium.svg'),
(5, 'Botswana', 'img/flags/botswana.svg'),
(6, 'South Africa', 'img/flags/southafrica.svg'),
(7, 'Angola', 'img/flags/angola.svg'),
(8, 'USA', 'img/flags/usa.svg'),
(9, 'Hong Kong', 'img/flags/hk.svg');
