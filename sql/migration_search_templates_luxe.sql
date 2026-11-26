-- ============================================================
-- Migration: the "Atelier Luxe" search template for Diamond Search, Results
-- and View Cart / View (Selected) —
--   elegant: ivory page, black and gold, serif headings,
--   filter rows with a sticky "Your selection" panel, black-and-gold
--   Results table (layout "luxe").
--
-- It appears in Admin → Search Templates. Preview it (only you see it),
-- then click "Make live". To change colours or fonts, create a new
-- template from it and edit it there.
--
-- Needs migration_search_templates.sql to have been run first.
-- Safe to run more than once. Makes nothing live.
-- ============================================================

INSERT IGNORE INTO search_templates (template_key, name, description, layout, is_builtin, is_active, settings, sort_order) VALUES
('luxe', 'Atelier Luxe',
 'Elegant jewellery-house look: ivory, black and gold, serif headings, filter rows with a sticky "Your selection" panel, black-and-gold Results table.',
 'luxe', 1, 0,
 '{"accent":"#b08d57","ink":"#1a1a1a","page_bg":"#faf7f2","card_bg":"#ffffff","muted":"#7a7064","heading_font":"''Playfair Display'', Georgia, serif","font":"''Mulish'', ''Segoe UI'', Arial, sans-serif"}',
 6);

SELECT template_key, name, layout, is_active FROM search_templates ORDER BY sort_order;
