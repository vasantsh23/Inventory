-- ============================================================
-- Migration: three colourful, modern search templates (layout "prism")
-- for Diamond Search, Results and View Cart / View (Selected).
--
--   Prism     violet → pink → amber gradient on a light lilac page
--   Lagoon    sky blue → teal → lime on a light aqua page
--   Midnight  cyan → violet → pink on a dark navy page
--
-- They appear in Admin → Search Templates. Preview one (only you see
-- it), then click "Make live". To change colours, create a new template
-- from one of them and edit its colours there.
--
-- Needs migration_search_templates.sql to have been run first.
-- Safe to run more than once (INSERT IGNORE). Makes nothing live.
-- ============================================================

INSERT IGNORE INTO search_templates (template_key, name, description, layout, is_builtin, is_active, settings, sort_order) VALUES
('prism', 'Prism',
 'Colourful and modern: violet-pink-amber gradient banner, filter cards, large shape tiles, sticky "Show diamonds" bar, gradient Results header.',
 'prism', 1, 0,
 '{"grad_from":"#7c3aed","grad_via":"#db2777","grad_to":"#f59e0b","page_bg":"#f5f3ff","card_bg":"#ffffff","text":"#1e1b4b","muted":"#6b6890","font":"''Poppins'', ''Segoe UI'', Arial, sans-serif"}',
 3),
('lagoon', 'Lagoon',
 'Fresh and bright: sky blue, teal and lime gradients on a light aqua page, same modern card layout as Prism.',
 'prism', 1, 0,
 '{"grad_from":"#0284c7","grad_via":"#0d9488","grad_to":"#65a30d","page_bg":"#effbfa","card_bg":"#ffffff","text":"#0f2a3a","muted":"#55707d","font":"''Poppins'', ''Segoe UI'', Arial, sans-serif"}',
 4),
('midnight', 'Midnight',
 'Dark and vivid: cyan, violet and pink neon gradients on deep navy, same modern card layout as Prism.',
 'prism', 1, 0,
 '{"grad_from":"#06b6d4","grad_via":"#8b5cf6","grad_to":"#ec4899","page_bg":"#0b1022","card_bg":"#141b36","text":"#e8ecff","muted":"#9aa4cc","font":"''Poppins'', ''Segoe UI'', Arial, sans-serif"}',
 5);

SELECT template_key, name, layout, is_active FROM search_templates ORDER BY sort_order;
