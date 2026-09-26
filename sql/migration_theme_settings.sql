-- ============================================================
-- Migration: replace the old font & colour logic with the
-- theme_settings / theme_sections tables.
--
--   * Creates theme_sections + theme_settings and fills them with
--     195 settings that reproduce the current look exactly.
--     Every colour, font family, font size, weight and text case on
--     every page (public site, user module, admin dashboard, memo
--     printout) now comes from these tables — see /theme.css.php.
--   * Carries forward the values you already chose:
--       font_and_color active slots -> Body font, Base font size,
--                                      Main text colour, Content area background
--       rsetup.fontype / fontsize   -> Results table font / font size
--     (only values that are valid CSS are carried over)
--   * Removes the old logic: DROPS the font_and_color table and the
--     rsetup.fontype / rsetup.fontsize columns. rsetup keeps its
--     sort-field columns.
--
-- Run ONCE in phpMyAdmin's SQL tab against your existing database,
-- AFTER taking a backup (Admin -> Backup & Restore).
-- Do NOT run against a new database created from the current
-- sql/schema.sql — it already contains these tables.
-- ============================================================

CREATE TABLE IF NOT EXISTS theme_sections (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    section_key VARCHAR(40)  NOT NULL UNIQUE,
    name        VARCHAR(80)  NOT NULL,
    description VARCHAR(255) NULL,
    sort_order  INT          NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- One row = one CSS custom property (setting_key "header-bg" -> var(--header-bg))
CREATE TABLE IF NOT EXISTS theme_settings (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    section_id     INT UNSIGNED NOT NULL,
    setting_key    VARCHAR(80)  NOT NULL UNIQUE,
    label          VARCHAR(120) NOT NULL,
    property_type  ENUM('color','font_family','font_size','font_weight','font_style','text_transform','size','number','color_scheme') NOT NULL,
    setting_value  VARCHAR(255) NOT NULL,
    default_value  VARCHAR(255) NOT NULL,
    description    VARCHAR(255) NULL,
    sort_order     INT          NOT NULL DEFAULT 0,
    created_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_theme_setting_section FOREIGN KEY (section_id)
        REFERENCES theme_sections(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_theme_section (section_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO theme_sections (id, section_key, name, description, sort_order) VALUES
(1, 'global', 'Global', 'Page background, base text, accent colours, fonts, corner radius', 1),
(2, 'header', 'Header & navigation', 'Logo bar and main menu at the top of every page (public and admin)', 2),
(3, 'content', 'Public pages', 'Home hero, page titles, info cards and the login box', 3),
(4, 'panels', 'Panels & cards', 'Panel, card and surface backgrounds and borders used on every page', 4),
(5, 'buttons', 'Buttons', 'Primary (gradient) and standard buttons', 5),
(6, 'forms', 'Forms & inputs', 'Text boxes, dropdowns, labels, hints, checkboxes and radios', 6),
(7, 'tables', 'Tables & pagination', 'Data tables on admin lists, Results and View Cart', 7),
(8, 'status', 'Alerts & status', 'Error / success / warning messages and status pills', 8),
(9, 'admin', 'Admin dashboard', 'Sidebar, dashboard heading and stat cards', 9),
(10, 'search', 'Diamond search & results', 'Filter pills, shape icons, results table and stock links', 10),
(11, 'dsp', 'Diamond Search page', 'Light palette used only on the Diamond Search filter page', 11),
(12, 'details', 'Diamond details', 'Diamond details page media and field list', 12),
(13, 'memo', 'Memo printout', 'Printable memo page (on screen and on paper)', 13),
(14, 'footer', 'Footer', 'Footer of every public page', 14);

INSERT IGNORE INTO theme_settings (section_id, setting_key, label, property_type, setting_value, default_value, description, sort_order) VALUES
-- Global
(1,'color-scheme','Native control scheme','color_scheme','dark','dark','Makes browser scrollbars, dropdowns and date pickers match a dark or light theme',1),
(1,'page-bg','Page background','color','#05070d','#05070d','Background of every page',2),
(1,'page-glow-1','Background glow (top right)','color','rgba(123, 92, 255, 0.20)','rgba(123, 92, 255, 0.20)','Soft glow painted over the page background',3),
(1,'page-glow-2','Background glow (top left)','color','rgba(79, 140, 255, 0.15)','rgba(79, 140, 255, 0.15)',NULL,4),
(1,'text-color','Main text colour','color','#eef2fb','#eef2fb','Default fore colour for text',5),
(1,'text-muted','Secondary text colour','color','#a9b3c9','#a9b3c9','Descriptions, sub-headings, labels',6),
(1,'text-faint','Faint text colour','color','#6c7690','#6c7690','Helper text, separators, version numbers',7),
(1,'link-color','Link colour','color','#24e0c9','#24e0c9',NULL,8),
(1,'link-hover-color','Link hover colour','color','#6ff2df','#6ff2df',NULL,9),
(1,'accent','Accent colour','color','#4f8cff','#4f8cff','Focus borders, highlighted values, active thumbnails',10),
(1,'selection-bg','Text selection background','color','rgba(79, 140, 255, 0.35)','rgba(79, 140, 255, 0.35)',NULL,11),
(1,'selection-text','Text selection colour','color','#ffffff','#ffffff',NULL,12),
(1,'font-body','Body font','font_family','''Inter'', -apple-system, ''Segoe UI'', Roboto, Helvetica, Arial, sans-serif','''Inter'', -apple-system, ''Segoe UI'', Roboto, Helvetica, Arial, sans-serif','Font for text, forms and tables',13),
(1,'font-heading','Heading font','font_family','''Inter'', -apple-system, ''Segoe UI'', Roboto, Helvetica, Arial, sans-serif','''Inter'', -apple-system, ''Segoe UI'', Roboto, Helvetica, Arial, sans-serif','Font for h1–h3 headings',14),
(1,'font-mono','Code / hex font','font_family','''SFMono-Regular'', Consolas, monospace','''SFMono-Regular'', Consolas, monospace','Used for colour hex codes',15),
(1,'font-size-base','Base font size','font_size','16px','16px','Text size of the page body',16),
(1,'line-height-base','Base line height','number','1.6','1.6',NULL,17),
(1,'heading-weight','Heading weight','font_weight','700','700','Weight of h1–h3 headings',18),
(1,'radius','Corner radius (large)','size','14px','14px','Panels, cards, hero',19),
(1,'radius-sm','Corner radius (small)','size','8px','8px','Inputs, table frames, list rows',20),
(1,'radius-pill','Corner radius (pill)','size','999px','999px','Rounded pills, search box, nav links',21),
(1,'shadow-color','Shadow colour','color','rgba(0, 0, 0, 0.6)','rgba(0, 0, 0, 0.6)','Drop shadows under floating elements',22),
(1,'glow-color','Glow shadow colour','color','rgba(79, 140, 255, 0.25)','rgba(79, 140, 255, 0.25)','Coloured glow under the hero and login box',23),
(1,'backdrop-color','Mobile menu backdrop','color','rgba(0, 0, 0, 0.55)','rgba(0, 0, 0, 0.55)','Dims the page behind the admin menu on phones',24),
-- Header & navigation
(2,'header-bg','Background','color','#ffffff','#ffffff','Kept light so logos stay visible',1),
(2,'header-border','Bottom border','color','#e3e4e8','#e3e4e8',NULL,2),
(2,'company-name-color','Company name colour','color','#1a1c26','#1a1c26',NULL,3),
(2,'company-name-font','Company name font','font_family','''Inter'', -apple-system, ''Segoe UI'', Roboto, Helvetica, Arial, sans-serif','''Inter'', -apple-system, ''Segoe UI'', Roboto, Helvetica, Arial, sans-serif',NULL,4),
(2,'company-name-size','Company name size','font_size','1.05rem','1.05rem',NULL,5),
(2,'company-name-weight','Company name weight','font_weight','700','700',NULL,6),
(2,'nav-text','Menu text colour','color','#3d4150','#3d4150',NULL,7),
(2,'nav-hover-text','Menu hover text colour','color','#0f1115','#0f1115',NULL,8),
(2,'nav-hover-bg','Menu hover background','color','#f1f2f5','#f1f2f5',NULL,9),
(2,'nav-font-size','Menu font size','font_size','0.92rem','0.92rem',NULL,10),
(2,'nav-font-weight','Menu font weight','font_weight','600','600',NULL,11),
(2,'nav-account-text','"Signed in as" text colour','color','#6b6f7a','#6b6f7a',NULL,12),
(2,'nav-account-size','"Signed in as" text size','font_size','0.85rem','0.85rem',NULL,13),
(2,'header-toggle-border','Mobile menu button border','color','#d7d8dd','#d7d8dd','Admin hamburger button on phones',14),
(2,'header-toggle-bar','Mobile menu button lines','color','#3d4150','#3d4150',NULL,15),
-- Public pages
(3,'content-area-bg','Content area background','color','transparent','transparent','Behind the main content of public pages',1),
(3,'hero-bg','Hero background','color','rgba(255, 255, 255, 0.04)','rgba(255, 255, 255, 0.04)','Welcome banner on the home page',2),
(3,'hero-glow','Hero glow','color','rgba(123, 92, 255, 0.35)','rgba(123, 92, 255, 0.35)',NULL,3),
(3,'hero-title-color','Hero title colour','color','#eef2fb','#eef2fb',NULL,4),
(3,'hero-title-size','Hero title size (max)','font_size','2.6rem','2.6rem','Shrinks automatically on small screens',5),
(3,'hero-title-weight','Hero title weight','font_weight','800','800',NULL,6),
(3,'hero-text-color','Hero text colour','color','#a9b3c9','#a9b3c9',NULL,7),
(3,'hero-text-size','Hero text size','font_size','1.05rem','1.05rem',NULL,8),
(3,'page-title-size','Page title size','font_size','1.9rem','1.9rem','About Us / Contact Us headings',9),
(3,'card-title-size','Card title size','font_size','1.05rem','1.05rem','Home highlight cards and details sections',10),
(3,'social-icon-bg','Social icon background','color','rgba(255, 255, 255, 0.04)','rgba(255, 255, 255, 0.04)',NULL,11),
(3,'login-card-bg','Login box background','color','rgba(255, 255, 255, 0.04)','rgba(255, 255, 255, 0.04)',NULL,12),
(3,'login-title-size','Login title size','font_size','1.6rem','1.6rem',NULL,13),
(3,'login-label-size','Login label size','font_size','0.85rem','0.85rem',NULL,14),
-- Panels & cards
(4,'panel-bg','Panel / card background','color','rgba(255, 255, 255, 0.04)','rgba(255, 255, 255, 0.04)','Panels, cards, stat cards, detail sections',1),
(4,'panel-border','Panel / card border','color','rgba(255, 255, 255, 0.08)','rgba(255, 255, 255, 0.08)',NULL,2),
(4,'panel-border-hover','Panel border on hover','color','rgba(120, 170, 255, 0.35)','rgba(120, 170, 255, 0.35)',NULL,3),
(4,'surface-subtle','Subtle surface','color','rgba(255, 255, 255, 0.015)','rgba(255, 255, 255, 0.015)','Import drop zone and other faint wells',4),
(4,'panel-title-size','Panel title size','font_size','1.1rem','1.1rem',NULL,5),
-- Buttons
(5,'btn-primary-bg','Primary background (start)','color','#4f8cff','#4f8cff','Gradient start — also used by pagination and selected pills',1),
(5,'btn-primary-bg-2','Primary background (end)','color','#7b5cff','#7b5cff','Gradient end',2),
(5,'btn-primary-text','Primary text','color','#ffffff','#ffffff',NULL,3),
(5,'btn-primary-shadow','Primary glow','color','rgba(79, 140, 255, 0.6)','rgba(79, 140, 255, 0.6)','Save / Log In / accent buttons',4),
(5,'btn-primary-shadow-hover','Primary glow on hover','color','rgba(79, 140, 255, 0.7)','rgba(79, 140, 255, 0.7)',NULL,5),
(5,'cta-shadow','Call-to-action / selected pill glow','color','rgba(79, 140, 255, 0.55)','rgba(79, 140, 255, 0.55)','Home page button and selected filter pills',6),
(5,'btn-primary-font-size','Primary font size','font_size','0.95rem','0.95rem','Login, Save and call-to-action buttons',7),
(5,'btn-bg','Standard background','color','transparent','transparent',NULL,8),
(5,'btn-text','Standard text','color','#eef2fb','#eef2fb',NULL,9),
(5,'btn-border','Standard border','color','rgba(255, 255, 255, 0.08)','rgba(255, 255, 255, 0.08)',NULL,10),
(5,'btn-hover-border','Standard hover border','color','rgba(120, 170, 255, 0.35)','rgba(120, 170, 255, 0.35)',NULL,11),
(5,'btn-font','Font','font_family','''Inter'', -apple-system, ''Segoe UI'', Roboto, Helvetica, Arial, sans-serif','''Inter'', -apple-system, ''Segoe UI'', Roboto, Helvetica, Arial, sans-serif',NULL,12),
(5,'btn-font-size','Standard font size','font_size','0.88rem','0.88rem',NULL,13),
(5,'btn-font-weight','Standard font weight','font_weight','600','600',NULL,14),
(5,'btn-radius','Corner radius','size','8px','8px',NULL,15),
-- Forms & inputs
(6,'input-bg','Input background','color','rgba(255, 255, 255, 0.03)','rgba(255, 255, 255, 0.03)',NULL,1),
(6,'input-border','Input border','color','rgba(255, 255, 255, 0.08)','rgba(255, 255, 255, 0.08)',NULL,2),
(6,'input-text','Input text','color','#eef2fb','#eef2fb',NULL,3),
(6,'input-placeholder','Placeholder text','color','#6c7690','#6c7690',NULL,4),
(6,'input-focus-border','Focus border','color','#4f8cff','#4f8cff',NULL,5),
(6,'input-focus-ring','Focus ring','color','rgba(79, 140, 255, 0.18)','rgba(79, 140, 255, 0.18)',NULL,6),
(6,'input-font-size','Input font size','font_size','0.9rem','0.9rem',NULL,7),
(6,'input-readonly-bg','Read-only background','color','rgba(255, 255, 255, 0.015)','rgba(255, 255, 255, 0.015)',NULL,8),
(6,'input-readonly-text','Read-only text','color','#6c7690','#6c7690',NULL,9),
(6,'label-color','Label colour','color','#a9b3c9','#a9b3c9',NULL,10),
(6,'label-font-size','Label size','font_size','0.82rem','0.82rem',NULL,11),
(6,'hint-color','Hint text colour','color','#6c7690','#6c7690',NULL,12),
(6,'option-bg','Dropdown list background','color','#ffffff','#ffffff','Open dropdown list (kept light for readability)',13),
(6,'option-text','Dropdown list text','color','#16181d','#16181d',NULL,14),
(6,'checkbox-bg','Checkbox / radio background','color','#ffffff','#ffffff',NULL,15),
(6,'checkbox-border','Checkbox / radio border','color','#4a4a4a','#4a4a4a',NULL,16),
(6,'checkbox-checked','Checkbox tick / radio dot','color','#000000','#000000',NULL,17),
-- Tables & pagination
(7,'table-head-bg','Header row background','color','rgba(255, 255, 255, 0.03)','rgba(255, 255, 255, 0.03)',NULL,1),
(7,'table-head-text','Header row text','color','#a9b3c9','#a9b3c9',NULL,2),
(7,'table-head-size','Header row size','font_size','0.75rem','0.75rem',NULL,3),
(7,'table-head-transform','Header row case','text_transform','uppercase','uppercase',NULL,4),
(7,'table-text','Cell text','color','#eef2fb','#eef2fb',NULL,5),
(7,'table-font-size','Cell text size','font_size','0.86rem','0.86rem','Admin data tables',6),
(7,'table-row-border','Row divider','color','rgba(255, 255, 255, 0.04)','rgba(255, 255, 255, 0.04)',NULL,7),
(7,'table-row-hover','Row hover background','color','rgba(255, 255, 255, 0.02)','rgba(255, 255, 255, 0.02)',NULL,8),
(7,'pill-neutral-bg','Neutral pill background','color','rgba(255, 255, 255, 0.06)','rgba(255, 255, 255, 0.06)',NULL,9),
-- Alerts & status
(8,'danger','Danger colour','color','#ff5d7a','#ff5d7a',NULL,1),
(8,'danger-bg','Danger background','color','rgba(255, 93, 122, 0.12)','rgba(255, 93, 122, 0.12)',NULL,2),
(8,'danger-border','Danger border','color','rgba(255, 93, 122, 0.35)','rgba(255, 93, 122, 0.35)',NULL,3),
(8,'danger-text','Danger text','color','#ffb0c0','#ffb0c0',NULL,4),
(8,'success','Success colour','color','#2be0a0','#2be0a0','Also the "Available" badge on Diamond Details',5),
(8,'success-bg','Success background','color','rgba(43, 224, 160, 0.12)','rgba(43, 224, 160, 0.12)',NULL,6),
(8,'success-border','Success border','color','rgba(43, 224, 160, 0.35)','rgba(43, 224, 160, 0.35)',NULL,7),
(8,'success-text','Success text','color','#a7f5da','#a7f5da',NULL,8),
(8,'warning','Warning colour','color','#ffb84f','#ffb84f',NULL,9),
(8,'warning-bg','Warning background','color','rgba(255, 184, 79, 0.12)','rgba(255, 184, 79, 0.12)',NULL,10),
(8,'warning-border','Warning border','color','rgba(255, 184, 79, 0.35)','rgba(255, 184, 79, 0.35)',NULL,11),
(8,'warning-text','Warning text','color','#ffdca7','#ffdca7',NULL,12),
(8,'pill-success-text','Success pill text','color','#7cf0c8','#7cf0c8','Status pills in tables, e.g. "approved"',13),
(8,'pill-warning-text','Warning pill text','color','#ffd08a','#ffd08a',NULL,14),
(8,'pill-danger-text','Danger pill text','color','#ff9fb2','#ff9fb2',NULL,15),
(8,'error-box-border','Memo / copy error border','color','rgba(239, 68, 68, 0.35)','rgba(239, 68, 68, 0.35)','Error boxes on Results and View Cart',16),
(8,'error-box-text','Memo / copy error text','color','#fca5a5','#fca5a5',NULL,17),
-- Admin dashboard
(9,'sidebar-bg','Sidebar background','color','rgba(9, 12, 20, 0.6)','rgba(9, 12, 20, 0.6)',NULL,1),
(9,'sidebar-border','Sidebar border','color','rgba(255, 255, 255, 0.08)','rgba(255, 255, 255, 0.08)',NULL,2),
(9,'sidebar-section-text','Sidebar group heading','color','#6c7690','#6c7690','"Modules", "Manage Tables" …',3),
(9,'sidebar-section-transform','Sidebar group heading case','text_transform','uppercase','uppercase',NULL,4),
(9,'sidebar-link-text','Sidebar link','color','#a9b3c9','#a9b3c9',NULL,5),
(9,'sidebar-link-hover-bg','Sidebar link hover background','color','rgba(255, 255, 255, 0.04)','rgba(255, 255, 255, 0.04)',NULL,6),
(9,'sidebar-link-hover-text','Sidebar link hover text','color','#eef2fb','#eef2fb',NULL,7),
(9,'sidebar-active-bg','Active link background (start)','color','rgba(79, 140, 255, 0.18)','rgba(79, 140, 255, 0.18)',NULL,8),
(9,'sidebar-active-bg-2','Active link background (end)','color','rgba(123, 92, 255, 0.18)','rgba(123, 92, 255, 0.18)',NULL,9),
(9,'sidebar-active-text','Active link text','color','#ffffff','#ffffff',NULL,10),
(9,'sidebar-active-ring','Active link outline','color','rgba(120, 170, 255, 0.35)','rgba(120, 170, 255, 0.35)',NULL,11),
(9,'sidebar-font-size','Sidebar font size','font_size','0.9rem','0.9rem',NULL,12),
(9,'dash-bg','Work area background','color','transparent','transparent',NULL,13),
(9,'dash-text','Work area text','color','#eef2fb','#eef2fb',NULL,14),
(9,'dash-title-size','Page title size','font_size','1.6rem','1.6rem',NULL,15),
(9,'stat-value-size','Stat number size','font_size','1.7rem','1.7rem',NULL,16),
(9,'stat-gradient-start','Stat number gradient (start)','color','#24e0c9','#24e0c9',NULL,17),
(9,'stat-gradient-end','Stat number gradient (end)','color','#4f8cff','#4f8cff',NULL,18),
-- Diamond search & results
(10,'ds-title-font','Page title font','font_family','''Inter'', -apple-system, ''Segoe UI'', Roboto, Helvetica, Arial, sans-serif','''Inter'', -apple-system, ''Segoe UI'', Roboto, Helvetica, Arial, sans-serif','Diamond Search / Results headings',1),
(10,'ds-title-size','Page title size','font_size','2rem','2rem',NULL,2),
(10,'ds-title-size-mobile','Page title size (phones)','font_size','1.5rem','1.5rem',NULL,3),
(10,'ds-section-title-transform','Filter heading case','text_transform','uppercase','uppercase',NULL,4),
(10,'pill-bg','Filter pill background','color','rgba(255, 255, 255, 0.04)','rgba(255, 255, 255, 0.04)',NULL,5),
(10,'pill-border','Filter pill border','color','rgba(255, 255, 255, 0.08)','rgba(255, 255, 255, 0.08)',NULL,6),
(10,'pill-text','Filter pill text','color','#eef2fb','#eef2fb',NULL,7),
(10,'pill-selected-text','Selected pill text','color','#ffffff','#ffffff','Selected pill background uses the primary button gradient',8),
(10,'pill-font-size','Filter pill size','font_size','0.88rem','0.88rem',NULL,9),
(10,'pill-font-size-mobile','Filter pill size (phones)','font_size','0.82rem','0.82rem',NULL,10),
(10,'shape-icon-bg','Shape icon background','color','#f6f3ee','#f6f3ee','Kept light because shape artwork is dark line-art',11),
(10,'shape-icon-border','Shape icon border','color','rgba(0, 0, 0, 0.08)','rgba(0, 0, 0, 0.08)',NULL,12),
(10,'shape-hover-shadow','Shape icon hover shadow','color','rgba(0, 0, 0, 0.35)','rgba(0, 0, 0, 0.35)',NULL,13),
(10,'shape-selected-bg','Selected shape background','color','#99c9ff','#99c9ff',NULL,14),
(10,'shape-label-color','Shape label','color','#a9b3c9','#a9b3c9',NULL,15),
(10,'shape-fallback-color','Shape placeholder text','color','#9a9384','#9a9384','Shown when a shape has no image',16),
(10,'filter-tag-bg','Applied filter tag background','color','rgba(79, 140, 255, 0.12)','rgba(79, 140, 255, 0.12)',NULL,17),
(10,'filter-tag-border','Applied filter tag border','color','rgba(79, 140, 255, 0.3)','rgba(79, 140, 255, 0.3)',NULL,18),
(10,'results-table-font','Results table font','font_family','Arial, sans-serif','Arial, sans-serif','Results and View Cart table (was rsetup.fontype)',19),
(10,'results-table-font-size','Results table font size','font_size','14px','14px','Results and View Cart table (was rsetup.fontsize)',20),
(10,'stockno-link-color','Stock No link colour','color','#4f8cff','#4f8cff',NULL,21),
(10,'stockno-notforweb-bg','"Not for web" Stock No background','color','#f33b2b','#f33b2b','Overrides the availability colour when notforweb = true',22),
-- Diamond Search page
(11,'dsp-bg','Page background','color','#fbf9f5','#fbf9f5',NULL,1),
(11,'dsp-text','Text colour','color','#3a3733','#3a3733',NULL,2),
(11,'dsp-border','Header / footer border','color','#e6e0d6','#e6e0d6',NULL,3),
(11,'dsp-muted','Muted text','color','#6b665f','#6b665f',NULL,4),
(11,'dsp-accent','Accent (teal)','color','#0d6b6b','#0d6b6b','Selected borders, Search button',5),
(11,'dsp-accent-hover','Accent hover','color','#0a5555','#0a5555',NULL,6),
(11,'dsp-accent-soft','Selected background','color','#eef6f5','#eef6f5',NULL,7),
(11,'dsp-gold','Title / selected text (gold)','color','#a97e4a','#a97e4a',NULL,8),
(11,'dsp-gold-hover','Gold hover','color','#8a6339','#8a6339',NULL,9),
(11,'dsp-section-title','Filter heading colour','color','#8a8478','#8a8478',NULL,10),
(11,'dsp-divider','Section divider','color','#ece7db','#ece7db',NULL,11),
(11,'dsp-control-bg','Pill / input background','color','#ffffff','#ffffff',NULL,12),
(11,'dsp-control-border','Pill / input border','color','#ddd6c7','#ddd6c7',NULL,13),
(11,'dsp-control-text','Pill / checkbox text','color','#4a463f','#4a463f',NULL,14),
(11,'dsp-shape-border','Shape icon border','color','#d8d2c4','#d8d2c4',NULL,15),
(11,'dsp-placeholder','Placeholder text','color','#9c968a','#9c968a',NULL,16),
(11,'dsp-stepper-bg','Stepper button background','color','#f3efe6','#f3efe6',NULL,17),
(11,'dsp-stepper-hover-bg','Stepper button hover','color','#e6e0d3','#e6e0d3',NULL,18),
(11,'dsp-btn-text','Search button text','color','#ffffff','#ffffff',NULL,19),
(11,'dsp-title-font','Title font','font_family','Georgia, ''Times New Roman'', serif','Georgia, ''Times New Roman'', serif',NULL,20),
(11,'dsp-title-weight','Title weight','font_weight','400','400',NULL,21),
-- Diamond details
(12,'image-well-bg','Image / video background','color','#f6f3ee','#f6f3ee','Behind diamond photos, videos and certificates',1),
(12,'dd-field-border','Field row divider','color','rgba(255, 255, 255, 0.05)','rgba(255, 255, 255, 0.05)',NULL,2),
(12,'dd-value-color','Field value colour','color','#a9b3c9','#a9b3c9',NULL,3),
-- Memo printout
(13,'memo-font','Font','font_family','Arial, Helvetica, sans-serif','Arial, Helvetica, sans-serif',NULL,1),
(13,'memo-font-size','Font size','font_size','9px','9px',NULL,2),
(13,'memo-make-size','"Make" column size','font_size','10px','10px','Combined Cut/Polish/Symmetry/Fluorescence column',3),
(13,'memo-text','Text colour','color','#000000','#000000',NULL,4),
(13,'memo-border','Table border','color','#000000','#000000',NULL,5),
(13,'memo-faint-border','Faint border (copies 2 and 3)','color','#d8d8d8','#d8d8d8',NULL,6),
(13,'memo-paper-bg','Paper colour','color','#ffffff','#ffffff',NULL,7),
(13,'memo-desk-bg','Screen backdrop','color','#e6e6e6','#e6e6e6','Behind the paper on screen; not printed',8),
(13,'memo-shadow','Paper shadow','color','rgba(0, 0, 0, 0.25)','rgba(0, 0, 0, 0.25)',NULL,9),
-- Footer
(14,'footer-bg','Background','color','rgba(5, 7, 13, 0.9)','rgba(5, 7, 13, 0.9)',NULL,1),
(14,'footer-border','Top border','color','rgba(255, 255, 255, 0.08)','rgba(255, 255, 255, 0.08)',NULL,2),
(14,'footer-text','Text colour','color','#6c7690','#6c7690',NULL,3),
(14,'footer-heading','Company name colour','color','#a9b3c9','#a9b3c9',NULL,4),
(14,'footer-link','Link colour','color','#24e0c9','#24e0c9',NULL,5),
(14,'footer-font-size','Text size','font_size','0.88rem','0.88rem',NULL,6);

-- ---- Carry forward the active Fonts & Colors selection ----------------
UPDATE theme_settings t
  JOIN (SELECT TRIM(CASE f.selected_fonttype WHEN 'font type-1' THEN f.`font type-1` WHEN 'font type-2' THEN f.`font type-2` WHEN 'font type-3' THEN f.`font type-3` WHEN 'font type-4' THEN f.`font type-4` WHEN 'font type-5' THEN f.`font type-5` END) AS v
          FROM font_and_color f ORDER BY f.id LIMIT 1) src
   SET t.setting_value = CONCAT('''', src.v, ''', sans-serif')
 WHERE t.setting_key = 'font-body' AND src.v REGEXP '^[A-Za-z0-9 -]{2,80}$';

UPDATE theme_settings t
  JOIN (SELECT TRIM(CASE f.selected_fontsize WHEN 'font-size-1' THEN f.`font-size-1` WHEN 'font-size-2' THEN f.`font-size-2` WHEN 'font-size-3' THEN f.`font-size-3` WHEN 'font-size-4' THEN f.`font-size-4` WHEN 'font-size-5' THEN f.`font-size-5` END) AS v
          FROM font_and_color f ORDER BY f.id LIMIT 1) src
   SET t.setting_value = CONCAT(src.v, 'px')
 WHERE t.setting_key = 'font-size-base' AND src.v REGEXP '^[0-9]{1,3}(\\.[0-9]{1,3})?$';

UPDATE theme_settings t
  JOIN (SELECT TRIM(CASE f.selected_fontsize WHEN 'font-size-1' THEN f.`font-size-1` WHEN 'font-size-2' THEN f.`font-size-2` WHEN 'font-size-3' THEN f.`font-size-3` WHEN 'font-size-4' THEN f.`font-size-4` WHEN 'font-size-5' THEN f.`font-size-5` END) AS v
          FROM font_and_color f ORDER BY f.id LIMIT 1) src
   SET t.setting_value = src.v
 WHERE t.setting_key = 'font-size-base' AND src.v REGEXP '^[0-9]{1,3}(\\.[0-9]{1,3})?(px|rem|em|%)$';

UPDATE theme_settings t
  JOIN (SELECT TRIM(CASE f.selected_forecolor WHEN 'forecolor-1' THEN f.`forecolor-1` WHEN 'forecolor-2' THEN f.`forecolor-2` WHEN 'forecolor-3' THEN f.`forecolor-3` WHEN 'forecolor-4' THEN f.`forecolor-4` WHEN 'forecolor-5' THEN f.`forecolor-5` END) AS v
          FROM font_and_color f ORDER BY f.id LIMIT 1) src
   SET t.setting_value = src.v
 WHERE t.setting_key = 'text-color' AND src.v REGEXP '^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$';

UPDATE theme_settings t
  JOIN (SELECT TRIM(CASE f.selected_backcolor WHEN 'backcolor-1' THEN f.`backcolor-1` WHEN 'backcolor-2' THEN f.`backcolor-2` WHEN 'backcolor-3' THEN f.`backcolor-3` WHEN 'backcolor-4' THEN f.`backcolor-4` WHEN 'backcolor-5' THEN f.`backcolor-5` END) AS v
          FROM font_and_color f ORDER BY f.id LIMIT 1) src
   SET t.setting_value = src.v
 WHERE t.setting_key = 'content-area-bg' AND src.v REGEXP '^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$';

-- ---- Carry forward the Results / View Cart font from rsetup ----------
UPDATE theme_settings t
  JOIN (SELECT TRIM(fontype) AS v FROM rsetup ORDER BY id DESC LIMIT 1) src
   SET t.setting_value = CONCAT('''', src.v, ''', sans-serif')
 WHERE t.setting_key = 'results-table-font' AND src.v REGEXP '^[A-Za-z0-9 -]{2,80}$'
   AND LOWER(src.v) <> 'arial';  -- plain Arial is already the default

UPDATE theme_settings t
  JOIN (SELECT fontsize AS v FROM rsetup ORDER BY id DESC LIMIT 1) src
   SET t.setting_value = CONCAT(src.v, 'px')
 WHERE t.setting_key = 'results-table-font-size' AND src.v BETWEEN 6 AND 72;

-- ---- Remove the old font & colour tables / columns --------------------
DROP TABLE IF EXISTS font_and_color;
ALTER TABLE rsetup
    DROP COLUMN fontype,
    DROP COLUMN fontsize;
