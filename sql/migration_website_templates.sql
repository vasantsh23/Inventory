-- ============================================================
-- Migration: public website templates + page content
--
-- Adds everything the new public website needs WITHOUT changing any
-- existing inventory table:
--
--   site_templates  Every website template. 3 are built in (Atelier,
--                   Heritage, Noir); admins can create more in
--                   Admin -> Website -> Templates by duplicating one and
--                   choosing the header, hero, footer and section styles.
--                   Exactly one template is live.
--   site_blocks     Which sections each template shows on each page,
--                   and in what order.
--   site_content    Every text, image and link on every page. Rows are
--                   created automatically the first time you open
--                   Admin -> Website -> Page Content.
--   site_enquiries  Messages sent from the website's contact form.
--
--   theme_sections / theme_settings
--                   One section per template ("Website - <name> template")
--                   holding its colours and fonts, editable in
--                   Admin -> Theme Settings like every other setting.
--
-- Safe to run more than once (CREATE IF NOT EXISTS / INSERT IGNORE).
-- Run in phpMyAdmin's SQL tab with your database selected, after taking
-- a backup (Admin -> Backup & Restore).
-- ============================================================

CREATE TABLE IF NOT EXISTS site_templates (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    template_key    VARCHAR(30)  NOT NULL UNIQUE,
    name            VARCHAR(80)  NOT NULL,
    description     VARCHAR(255) NULL,
    is_builtin      TINYINT(1)   NOT NULL DEFAULT 0,
    is_active       TINYINT(1)   NOT NULL DEFAULT 0,
    settings_prefix VARCHAR(20)  NOT NULL,
    base_template   VARCHAR(30)  NOT NULL DEFAULT 'atelier',
    parts           TEXT         NULL COMMENT 'JSON: which style family each part uses',
    thumbnail       VARCHAR(255) NULL,
    sort_order      INT          NOT NULL DEFAULT 0,
    updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS site_blocks (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    template_key  VARCHAR(30)  NOT NULL,
    page_key      VARCHAR(40)  NOT NULL,
    block_key     VARCHAR(40)  NOT NULL,
    is_enabled    TINYINT(1)   NOT NULL DEFAULT 1,
    sort_order    INT          NOT NULL DEFAULT 0,
    UNIQUE KEY uq_site_block (template_key, page_key, block_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS site_content (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_key       VARCHAR(40)  NOT NULL,
    block_key      VARCHAR(40)  NOT NULL,
    field_key      VARCHAR(60)  NOT NULL,
    field_type     VARCHAR(20)  NOT NULL DEFAULT 'text',
    label          VARCHAR(120) NOT NULL,
    content_value  TEXT         NULL,
    default_value  TEXT         NULL,
    sort_order     INT          NOT NULL DEFAULT 0,
    updated_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_site_content (page_key, block_key, field_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS site_enquiries (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(120) NOT NULL,
    company     VARCHAR(150) NULL,
    email       VARCHAR(150) NOT NULL,
    phone       VARCHAR(50)  NULL,
    location    VARCHAR(120) NULL,
    message     TEXT         NOT NULL,
    page_key    VARCHAR(40)  NULL,
    ipadd       VARCHAR(45)  NULL,
    status      ENUM('new','read','archived') NOT NULL DEFAULT 'new',
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_site_enq_status (status, created_at),
    INDEX idx_site_enq_ip (ipadd, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Built-in templates (Atelier is live after install)
INSERT IGNORE INTO site_templates (template_key, name, description, is_builtin, is_active, settings_prefix, base_template, sort_order) VALUES
('atelier', 'Atelier', 'Light and editorial. Centred logo, full-width photography, fine serif headings and a black contact band.', 1, 1, 'at', 'atelier', 1),
('heritage', 'Heritage', 'Clear and trustworthy. Sticky menu with a button, brand-colour feature band, step-by-step timeline, reviews and FAQs.', 1, 0, 'he', 'heritage', 2),
('noir', 'Noir', 'Dark and dramatic. Top contact bar, full-screen hero with key figures, gold detailing and office cards.', 1, 0, 'no', 'noir', 3);

-- Colours & fonts of each built-in template (Admin -> Theme Settings)
INSERT IGNORE INTO theme_sections (section_key, name, description, sort_order) VALUES ('site_atelier', 'Website - Atelier template', 'Colours and fonts of the public website when the Atelier template is used', 21);
SET @s := (SELECT id FROM theme_sections WHERE section_key = 'site_atelier');
INSERT IGNORE INTO theme_settings (section_id, setting_key, label, property_type, setting_value, default_value, description, sort_order) VALUES
(@s,'at-bg','Page background','color','#ffffff','#ffffff',NULL,1),
(@s,'at-surface','Alternate section background','color','#f2f1ee','#f2f1ee','Bands between plain sections',2),
(@s,'at-card','Card background','color','#ffffff','#ffffff','Cards, form panels, office boxes',3),
(@s,'at-ink','Main text','color','#1d1c1a','#1d1c1a',NULL,4),
(@s,'at-muted','Secondary text','color','#6b6861','#6b6861',NULL,5),
(@s,'at-accent','Accent','color','#a8895a','#a8895a','Labels, icons, links, small details',6),
(@s,'at-on-accent','Text on accent','color','#ffffff','#ffffff',NULL,7),
(@s,'at-line','Borders and hairlines','color','#e2dfd8','#e2dfd8',NULL,8),
(@s,'at-brand','Brand band background','color','#1d1c1a','#1d1c1a','Feature band, reviews band, brand footer, main buttons',9),
(@s,'at-on-brand','Text on brand band','color','#f4f2ed','#f4f2ed',NULL,10),
(@s,'at-dark','Dark band background','color','#000000','#000000','Contact band and dark footers',11),
(@s,'at-on-dark','Text on dark band','color','#f4f2ed','#f4f2ed',NULL,12),
(@s,'at-topbar-bg','Top contact bar background','color','#1d1c1a','#1d1c1a','Used by the Noir header',13),
(@s,'at-heading-font','Heading font','font_family','''Cormorant Garamond'', serif','''Cormorant Garamond'', serif',NULL,14),
(@s,'at-body-font','Body font','font_family','''Jost'', sans-serif','''Jost'', sans-serif',NULL,15),
(@s,'at-heading-weight','Heading weight','font_weight','400','400',NULL,16),
(@s,'at-base-size','Base text size','font_size','16px','16px',NULL,17),
(@s,'at-btn-radius','Button corner radius','size','0','0','999px gives pill-shaped buttons',18),
(@s,'at-radius','Card corner radius','size','0','0',NULL,19),
(@s,'at-section-space','Space between sections','size','112px','112px','Shrinks automatically on phones',20);

INSERT IGNORE INTO theme_sections (section_key, name, description, sort_order) VALUES ('site_heritage', 'Website - Heritage template', 'Colours and fonts of the public website when the Heritage template is used', 22);
SET @s := (SELECT id FROM theme_sections WHERE section_key = 'site_heritage');
INSERT IGNORE INTO theme_settings (section_id, setting_key, label, property_type, setting_value, default_value, description, sort_order) VALUES
(@s,'he-bg','Page background','color','#ffffff','#ffffff',NULL,1),
(@s,'he-surface','Alternate section background','color','#edf0f1','#edf0f1','Bands between plain sections',2),
(@s,'he-card','Card background','color','#ffffff','#ffffff','Cards, form panels, office boxes',3),
(@s,'he-ink','Main text','color','#132b33','#132b33',NULL,4),
(@s,'he-muted','Secondary text','color','#52656b','#52656b',NULL,5),
(@s,'he-accent','Accent','color','#c9a45c','#c9a45c','Labels, icons, links, small details',6),
(@s,'he-on-accent','Text on accent','color','#132b33','#132b33',NULL,7),
(@s,'he-line','Borders and hairlines','color','#d6dcde','#d6dcde',NULL,8),
(@s,'he-brand','Brand band background','color','#0b3a47','#0b3a47','Feature band, reviews band, brand footer, main buttons',9),
(@s,'he-on-brand','Text on brand band','color','#ffffff','#ffffff',NULL,10),
(@s,'he-dark','Dark band background','color','#0b3a47','#0b3a47','Contact band and dark footers',11),
(@s,'he-on-dark','Text on dark band','color','#ffffff','#ffffff',NULL,12),
(@s,'he-topbar-bg','Top contact bar background','color','#082c36','#082c36','Used by the Noir header',13),
(@s,'he-heading-font','Heading font','font_family','''Playfair Display'', serif','''Playfair Display'', serif',NULL,14),
(@s,'he-body-font','Body font','font_family','''Mulish'', sans-serif','''Mulish'', sans-serif',NULL,15),
(@s,'he-heading-weight','Heading weight','font_weight','600','600',NULL,16),
(@s,'he-base-size','Base text size','font_size','16px','16px',NULL,17),
(@s,'he-btn-radius','Button corner radius','size','999px','999px','999px gives pill-shaped buttons',18),
(@s,'he-radius','Card corner radius','size','6px','6px',NULL,19),
(@s,'he-section-space','Space between sections','size','96px','96px','Shrinks automatically on phones',20);

INSERT IGNORE INTO theme_sections (section_key, name, description, sort_order) VALUES ('site_noir', 'Website - Noir template', 'Colours and fonts of the public website when the Noir template is used', 23);
SET @s := (SELECT id FROM theme_sections WHERE section_key = 'site_noir');
INSERT IGNORE INTO theme_settings (section_id, setting_key, label, property_type, setting_value, default_value, description, sort_order) VALUES
(@s,'no-bg','Page background','color','#0d0d0c','#0d0d0c',NULL,1),
(@s,'no-surface','Alternate section background','color','#141412','#141412','Bands between plain sections',2),
(@s,'no-card','Card background','color','#1b1a17','#1b1a17','Cards, form panels, office boxes',3),
(@s,'no-ink','Main text','color','#ece8df','#ece8df',NULL,4),
(@s,'no-muted','Secondary text','color','#a29d92','#a29d92',NULL,5),
(@s,'no-accent','Accent','color','#c6a15b','#c6a15b','Labels, icons, links, small details',6),
(@s,'no-on-accent','Text on accent','color','#14120e','#14120e',NULL,7),
(@s,'no-line','Borders and hairlines','color','rgba(255, 255, 255, 0.10)','rgba(255, 255, 255, 0.10)',NULL,8),
(@s,'no-brand','Brand band background','color','#1b1a17','#1b1a17','Feature band, reviews band, brand footer, main buttons',9),
(@s,'no-on-brand','Text on brand band','color','#ece8df','#ece8df',NULL,10),
(@s,'no-dark','Dark band background','color','#070706','#070706','Contact band and dark footers',11),
(@s,'no-on-dark','Text on dark band','color','#ece8df','#ece8df',NULL,12),
(@s,'no-topbar-bg','Top contact bar background','color','#070706','#070706','Used by the Noir header',13),
(@s,'no-heading-font','Heading font','font_family','''Libre Baskerville'', serif','''Libre Baskerville'', serif',NULL,14),
(@s,'no-body-font','Body font','font_family','''Poppins'', sans-serif','''Poppins'', sans-serif',NULL,15),
(@s,'no-heading-weight','Heading weight','font_weight','400','400',NULL,16),
(@s,'no-base-size','Base text size','font_size','15px','15px',NULL,17),
(@s,'no-btn-radius','Button corner radius','size','2px','2px','999px gives pill-shaped buttons',18),
(@s,'no-radius','Card corner radius','size','2px','2px',NULL,19),
(@s,'no-section-space','Space between sections','size','104px','104px','Shrinks automatically on phones',20);

