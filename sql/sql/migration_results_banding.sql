-- ============================================================
-- Migration: row banding (alternating row colours) on the
-- Results and View Cart (view_results) tables.
--
-- Adds two parameters to theme_settings, in the
-- "Diamond search & results" section:
--   band_color1  -> background of odd rows  (1st, 3rd, 5th ...)
--   band_color2  -> background of even rows (2nd, 4th, 6th ...)
-- They are served by /theme.css.php as --band_color1 / --band_color2
-- and used by assets/css/style.css on table.results-table.
--
-- The defaults suit the default dark theme. For the white / light-grey
-- look of the old site, set them in Admin -> Theme Settings to
--   band_color1 = #ffffff   band_color2 = #efefef
-- (and a dark "Cell text" colour in Tables & pagination).
--
-- Safe to run more than once (INSERT IGNORE on the unique key).
-- Do NOT need to run on a new database created from the current
-- sql/schema.sql — it already contains these rows.
-- ============================================================

INSERT IGNORE INTO theme_settings
    (section_id, setting_key, label, property_type, setting_value, default_value, description, sort_order)
VALUES
(10,'band_color1','Results row band colour 1','color','transparent','transparent','Background of odd rows (1st, 3rd, 5th ...) on Results and View Cart',23),
(10,'band_color2','Results row band colour 2','color','rgba(255, 255, 255, 0.05)','rgba(255, 255, 255, 0.05)','Background of even rows (2nd, 4th, 6th ...) on Results and View Cart',24);
