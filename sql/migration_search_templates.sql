-- ============================================================
-- Migration: Search Templates (Admin → Search Templates)
--
-- Templates for the Diamond Search, Results and View Cart / View
-- (Selected) pages. Two built-in templates are saved:
--   solitaire — the current design (live)
--   classic   — the design before the Solitaire Station redesign
-- Admins can make new templates from either (own name and colours),
-- preview any template themselves, and choose which one is live.
--
-- Safe to run more than once (IF NOT EXISTS / INSERT IGNORE).
-- Until this is run the pages keep using the Solitaire Station design.
-- ============================================================

CREATE TABLE IF NOT EXISTS search_templates (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    template_key  VARCHAR(40)  NOT NULL UNIQUE,
    name          VARCHAR(80)  NOT NULL,
    description   VARCHAR(255) NULL,
    layout        VARCHAR(30)  NOT NULL COMMENT 'folder under templates/search/',
    is_builtin    TINYINT(1)   NOT NULL DEFAULT 0,
    is_active     TINYINT(1)   NOT NULL DEFAULT 0,
    settings      TEXT         NULL COMMENT 'JSON: colours and font',
    sort_order    INT          NOT NULL DEFAULT 0,
    updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO search_templates (template_key, name, description, layout, is_builtin, is_active, settings, sort_order) VALUES
('solitaire', 'Solitaire Station',
 'Current design: dark label bars, blue selected options, one-screen Diamond Search, bordered blue-header Results table with Selected totals.',
 'solitaire', 1, 1, NULL, 1),
('classic', 'Classic',
 'The original design: Theme Settings colours, rounded pills, stepper buttons on ranges, Results table with media icons in one column.',
 'classic', 1, 0, NULL, 2);

-- Exactly one live template: if none is live (e.g. a later edit), make Solitaire Station live.
UPDATE search_templates SET is_active = 1
WHERE template_key = 'solitaire'
  AND NOT EXISTS (SELECT 1 FROM (SELECT id FROM search_templates WHERE is_active = 1) AS live);

SELECT template_key, name, layout, is_builtin, is_active FROM search_templates ORDER BY sort_order;
