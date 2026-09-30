-- ============================================================
-- Migration: website templates, page content and website enquiries
--
-- Adds everything the public website (Home, About, Services,
-- Diamonds, Responsible Practices, Sustainability, Contact) needs:
--
--   site_options      one row per site-wide option; active_template
--                     is the template chosen in
--                     Admin -> Appearance -> Website Template
--   site_content      every heading, paragraph, list item, image path
--                     and button on the public pages
--                     (Admin -> Manage Tables -> Website Content)
--   contact_messages  enquiries sent from the Contact page
--                     (Admin -> Manage Tables -> Website Enquiries)
--   theme_sections / theme_settings
--                     one section per template (Maison, Noir, Atelier)
--                     with its colours, fonts and sizes, editable in
--                     Admin -> Appearance -> Theme Settings
--
-- Safe to run on a fresh install AND on an existing database, and
-- safe to run more than once: tables are created only if missing and
-- rows are inserted with INSERT IGNORE, so nothing you have already
-- edited is overwritten. Back up first (Admin -> Backup & Restore),
-- then run the whole file in phpMyAdmin's SQL tab.
-- ============================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS site_options (
    option_key   VARCHAR(60)  NOT NULL PRIMARY KEY,
    option_value VARCHAR(255) NULL,
    updated_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO site_options (option_key, option_value) VALUES ('active_template', 'maison');

CREATE TABLE IF NOT EXISTS site_content (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page        VARCHAR(40)  NOT NULL,
    block       VARCHAR(40)  NOT NULL,
    sort_order  INT          NOT NULL DEFAULT 0,
    title       VARCHAR(255) NULL,
    subtitle    VARCHAR(255) NULL,
    body        TEXT         NULL,
    image       VARCHAR(255) NULL,
    icon        VARCHAR(40)  NULL,
    link_label  VARCHAR(100) NULL,
    link_url    VARCHAR(255) NULL,
    link2_label VARCHAR(100) NULL,
    link2_url   VARCHAR(255) NULL,
    active      VARCHAR(3)   NOT NULL DEFAULT 'yes',
    updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_site_content (page, block, sort_order),
    INDEX idx_site_content_page (page, block, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- email and phone are encrypted (AES-256-GCM) like every other
-- personal field in this system; the admin screens decrypt them.
CREATE TABLE IF NOT EXISTS contact_messages (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status      VARCHAR(20)  NOT NULL DEFAULT 'new',
    name        VARCHAR(120) NOT NULL,
    company     VARCHAR(150) NULL,
    email       VARBINARY(512) NULL,
    phone       VARBINARY(255) NULL,
    location    VARCHAR(120) NULL,
    subject     VARCHAR(150) NULL,
    message     TEXT         NOT NULL,
    ip_address  VARCHAR(45)  NULL,
    admin_notes TEXT         NULL,
    INDEX idx_contact_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Theme sections + settings, one section per template
-- ------------------------------------------------------------
INSERT IGNORE INTO theme_sections (section_key, name, description, sort_order) VALUES
('tpl_maison', 'Website template: Maison', 'Colours, fonts and spacing of the Maison website template (light, editorial)', 20),
('tpl_noir', 'Website template: Noir', 'Colours, fonts and spacing of the Noir website template (dark, gold)', 21),
('tpl_atelier', 'Website template: Atelier', 'Colours, fonts and spacing of the Atelier website template (clean, corporate)', 22);

-- Website template: Maison
INSERT IGNORE INTO theme_settings (section_id, setting_key, label, property_type, setting_value, default_value, description, sort_order) VALUES
((SELECT id FROM theme_sections WHERE section_key = 'tpl_maison'), 'mz-bg', 'Page background', 'color', '#ffffff', '#ffffff', 'Main background of every page', 1),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_maison'), 'mz-bg-alt', 'Alternate band background', 'color', '#f3f1ec', '#f3f1ec', 'Every other section band', 2),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_maison'), 'mz-text', 'Body text', 'color', '#46423c', '#46423c', NULL, 3),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_maison'), 'mz-muted', 'Secondary text', 'color', '#7d776e', '#7d776e', 'Captions, labels, small print', 4),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_maison'), 'mz-heading', 'Headings', 'color', '#1d1b18', '#1d1b18', NULL, 5),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_maison'), 'mz-accent', 'Accent (champagne)', 'color', '#a88a55', '#a88a55', 'Eyebrows, icons, numerals, rules', 6),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_maison'), 'mz-line', 'Hairlines and borders', 'color', '#e3ded4', '#e3ded4', NULL, 7),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_maison'), 'mz-dark-bg', 'Dark band background', 'color', '#1a1814', '#1a1814', 'Hero, contact band and footer', 8),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_maison'), 'mz-dark-text', 'Text on dark bands', 'color', '#f4f0e8', '#f4f0e8', NULL, 9),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_maison'), 'mz-dark-muted', 'Secondary text on dark bands', 'color', '#aaa397', '#aaa397', NULL, 10),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_maison'), 'mz-hero-overlay', 'Hero photo overlay', 'color', 'rgba(16, 14, 11, 0.55)', 'rgba(16, 14, 11, 0.55)', 'Darkens a hero photo so the headline stays readable', 11),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_maison'), 'mz-btn-bg', 'Button background', 'color', '#1d1b18', '#1d1b18', NULL, 12),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_maison'), 'mz-btn-text', 'Button text', 'color', '#ffffff', '#ffffff', NULL, 13),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_maison'), 'mz-btn-dark-bg', 'Button background on dark bands', 'color', '#ffffff', '#ffffff', NULL, 14),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_maison'), 'mz-btn-dark-text', 'Button text on dark bands', 'color', '#1d1b18', '#1d1b18', NULL, 15),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_maison'), 'mz-font-heading', 'Heading font', 'font_family', '''Cormorant Garamond'', serif', '''Cormorant Garamond'', serif', NULL, 16),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_maison'), 'mz-font-body', 'Body font', 'font_family', '''Jost'', sans-serif', '''Jost'', sans-serif', NULL, 17),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_maison'), 'mz-heading-weight', 'Heading weight', 'font_weight', '400', '400', NULL, 18),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_maison'), 'mz-body-weight', 'Body text weight', 'font_weight', '300', '300', NULL, 19),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_maison'), 'mz-h1-size', 'Hero headline size (max)', 'font_size', '4.6rem', '4.6rem', 'Shrinks automatically on small screens', 20),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_maison'), 'mz-h2-size', 'Section heading size (max)', 'font_size', '2.8rem', '2.8rem', NULL, 21),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_maison'), 'mz-h3-size', 'Card heading size', 'font_size', '1.55rem', '1.55rem', NULL, 22),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_maison'), 'mz-body-size', 'Body text size', 'font_size', '1.06rem', '1.06rem', NULL, 23),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_maison'), 'mz-line-height', 'Body line height', 'number', '1.75', '1.75', NULL, 24),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_maison'), 'mz-eyebrow-size', 'Small label size', 'font_size', '0.74rem', '0.74rem', 'Text above headings', 25),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_maison'), 'mz-eyebrow-case', 'Small label case', 'text_transform', 'uppercase', 'uppercase', NULL, 26),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_maison'), 'mz-nav-case', 'Menu text case', 'text_transform', 'uppercase', 'uppercase', NULL, 27),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_maison'), 'mz-radius', 'Corner radius', 'size', '0px', '0px', 'Buttons, form fields, cards', 28),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_maison'), 'mz-space', 'Section spacing (max)', 'size', '128px', '128px', 'Space above and below each section', 29),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_maison'), 'mz-container', 'Content width (max)', 'size', '1200px', '1200px', NULL, 30);

-- Website template: Noir
INSERT IGNORE INTO theme_settings (section_id, setting_key, label, property_type, setting_value, default_value, description, sort_order) VALUES
((SELECT id FROM theme_sections WHERE section_key = 'tpl_noir'), 'nr-bg', 'Page background', 'color', '#1a1a1d', '#1a1a1d', NULL, 1),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_noir'), 'nr-bg-alt', 'Alternate band background', 'color', '#212125', '#212125', NULL, 2),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_noir'), 'nr-surface', 'Card background', 'color', '#28282d', '#28282d', NULL, 3),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_noir'), 'nr-text', 'Body text', 'color', '#cfcac1', '#cfcac1', NULL, 4),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_noir'), 'nr-muted', 'Secondary text', 'color', '#8f8a82', '#8f8a82', NULL, 5),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_noir'), 'nr-heading', 'Headings', 'color', '#f5f1e9', '#f5f1e9', NULL, 6),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_noir'), 'nr-accent', 'Accent (gold)', 'color', '#c9a45c', '#c9a45c', 'Buttons, icons, numbers, rules', 7),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_noir'), 'nr-accent-text', 'Text on gold', 'color', '#1a1a1d', '#1a1a1d', NULL, 8),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_noir'), 'nr-line', 'Borders', 'color', '#36363c', '#36363c', NULL, 9),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_noir'), 'nr-topbar-bg', 'Top info bar background', 'color', '#131315', '#131315', 'Email / address / phone strip', 10),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_noir'), 'nr-header-bg', 'Header background', 'color', 'rgba(26, 26, 29, 0.94)', 'rgba(26, 26, 29, 0.94)', NULL, 11),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_noir'), 'nr-footer-bg', 'Footer background', 'color', '#131315', '#131315', NULL, 12),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_noir'), 'nr-hero-overlay', 'Hero overlay', 'color', 'rgba(14, 14, 16, 0.62)', 'rgba(14, 14, 16, 0.62)', 'Darkens the hero picture', 13),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_noir'), 'nr-font-heading', 'Heading font', 'font_family', '''Playfair Display'', serif', '''Playfair Display'', serif', NULL, 14),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_noir'), 'nr-font-body', 'Body font', 'font_family', '''Poppins'', sans-serif', '''Poppins'', sans-serif', NULL, 15),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_noir'), 'nr-heading-weight', 'Heading weight', 'font_weight', '400', '400', NULL, 16),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_noir'), 'nr-body-weight', 'Body text weight', 'font_weight', '300', '300', NULL, 17),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_noir'), 'nr-hero-style', 'Hero headline style', 'font_style', 'italic', 'italic', NULL, 18),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_noir'), 'nr-h1-size', 'Hero headline size (max)', 'font_size', '3.5rem', '3.5rem', NULL, 19),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_noir'), 'nr-h2-size', 'Section heading size (max)', 'font_size', '2.4rem', '2.4rem', NULL, 20),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_noir'), 'nr-h3-size', 'Card heading size', 'font_size', '1.2rem', '1.2rem', NULL, 21),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_noir'), 'nr-body-size', 'Body text size', 'font_size', '0.95rem', '0.95rem', NULL, 22),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_noir'), 'nr-line-height', 'Body line height', 'number', '1.8', '1.8', NULL, 23),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_noir'), 'nr-eyebrow-size', 'Small label size', 'font_size', '0.7rem', '0.7rem', NULL, 24),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_noir'), 'nr-eyebrow-case', 'Small label case', 'text_transform', 'uppercase', 'uppercase', NULL, 25),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_noir'), 'nr-nav-case', 'Menu text case', 'text_transform', 'uppercase', 'uppercase', NULL, 26),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_noir'), 'nr-radius', 'Corner radius', 'size', '4px', '4px', NULL, 27),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_noir'), 'nr-space', 'Section spacing (max)', 'size', '112px', '112px', NULL, 28),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_noir'), 'nr-container', 'Content width (max)', 'size', '1140px', '1140px', NULL, 29);

-- Website template: Atelier
INSERT IGNORE INTO theme_settings (section_id, setting_key, label, property_type, setting_value, default_value, description, sort_order) VALUES
((SELECT id FROM theme_sections WHERE section_key = 'tpl_atelier'), 'at-bg', 'Page background', 'color', '#ffffff', '#ffffff', NULL, 1),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_atelier'), 'at-bg-alt', 'Alternate band background', 'color', '#eff0ee', '#eff0ee', NULL, 2),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_atelier'), 'at-text', 'Body text', 'color', '#34403f', '#34403f', NULL, 3),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_atelier'), 'at-muted', 'Secondary text', 'color', '#667270', '#667270', NULL, 4),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_atelier'), 'at-heading', 'Headings', 'color', '#0c3038', '#0c3038', NULL, 5),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_atelier'), 'at-primary', 'Primary colour', 'color', '#0c3038', '#0c3038', 'Buttons, feature band, footer', 6),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_atelier'), 'at-primary-text', 'Text on primary', 'color', '#ffffff', '#ffffff', NULL, 7),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_atelier'), 'at-primary-muted', 'Secondary text on primary', 'color', '#a9c0c2', '#a9c0c2', NULL, 8),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_atelier'), 'at-marker', 'Marker colour', 'color', '#c4302b', '#c4302b', 'Small icons, dots and label rules', 9),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_atelier'), 'at-line', 'Borders', 'color', '#dbe0df', '#dbe0df', NULL, 10),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_atelier'), 'at-header-bg', 'Header background', 'color', '#ffffff', '#ffffff', NULL, 11),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_atelier'), 'at-font-heading', 'Heading font', 'font_family', '''DM Serif Display'', serif', '''DM Serif Display'', serif', NULL, 12),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_atelier'), 'at-font-body', 'Body font', 'font_family', '''Work Sans'', sans-serif', '''Work Sans'', sans-serif', NULL, 13),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_atelier'), 'at-heading-weight', 'Heading weight', 'font_weight', '400', '400', NULL, 14),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_atelier'), 'at-body-weight', 'Body text weight', 'font_weight', '400', '400', NULL, 15),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_atelier'), 'at-h1-size', 'Hero headline size (max)', 'font_size', '3.4rem', '3.4rem', NULL, 16),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_atelier'), 'at-h2-size', 'Section heading size (max)', 'font_size', '2.5rem', '2.5rem', NULL, 17),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_atelier'), 'at-h3-size', 'Card heading size', 'font_size', '1.3rem', '1.3rem', NULL, 18),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_atelier'), 'at-body-size', 'Body text size', 'font_size', '1rem', '1rem', NULL, 19),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_atelier'), 'at-line-height', 'Body line height', 'number', '1.65', '1.65', NULL, 20),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_atelier'), 'at-eyebrow-size', 'Small label size', 'font_size', '0.78rem', '0.78rem', NULL, 21),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_atelier'), 'at-eyebrow-case', 'Small label case', 'text_transform', 'none', 'none', NULL, 22),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_atelier'), 'at-nav-case', 'Menu text case', 'text_transform', 'none', 'none', NULL, 23),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_atelier'), 'at-radius', 'Corner radius (cards)', 'size', '12px', '12px', NULL, 24),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_atelier'), 'at-radius-btn', 'Corner radius (buttons)', 'size', '999px', '999px', '999px gives pill-shaped buttons', 25),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_atelier'), 'at-space', 'Section spacing (max)', 'size', '104px', '104px', NULL, 26),
((SELECT id FROM theme_sections WHERE section_key = 'tpl_atelier'), 'at-container', 'Content width (max)', 'size', '1160px', '1160px', NULL, 27);

-- ------------------------------------------------------------
-- Starting content for every page. Edit it in Admin -> Manage
-- Tables -> Website Content. {company} is replaced with the
-- company name from Site Setup.
-- ------------------------------------------------------------
INSERT IGNORE INTO site_content (page, block, sort_order, title, subtitle, body, icon, link_label, link_url, link2_label, link2_url) VALUES
('global', 'footer', 10, '{company}', 'Natural polished diamonds for the jewellery trade', 'Manufacturer and trader of natural polished diamonds, supplying jewellers, wholesalers and manufacturers worldwide.', NULL, NULL, NULL, NULL, NULL),
('home', 'hero', 10, 'Polished diamonds, cut and certified for the trade', 'Diamond manufacturer and trader', 'We cut, grade and supply natural polished diamonds to jewellers, wholesalers and manufacturers, with live stock you can search online.', NULL, 'Browse inventory', '/inventory.php', 'Contact us', '/contact.php'),
('home', 'hero', 20, 'Every stone graded before it is listed', 'Consistent make', 'Our graders check each diamond against its certificate, so the stone you order is the stone that arrives.', NULL, 'See our diamonds', '/diamonds.php', 'Contact us', '/contact.php'),
('home', 'hero', 30, 'From rough to brilliant, under one roof', 'Manufacturing', 'Planning, cutting and polishing in our own workshop gives us control over make, quality and delivery time.', NULL, 'Our services', '/services.php', 'Contact us', '/contact.php'),
('home', 'stats', 10, 'GIA · IGI · HRD', 'Certificates we supply', NULL, NULL, NULL, NULL, NULL, NULL),
('home', 'stats', 20, '0.01–10 ct', 'Size range in stock', NULL, NULL, NULL, NULL, NULL, NULL),
('home', 'stats', 30, '1 day', 'Typical reply to a request', NULL, NULL, NULL, NULL, NULL, NULL),
('home', 'stats', 40, 'B2B', 'Trade customers only', NULL, NULL, NULL, NULL, NULL, NULL),
('home', 'intro', 10, 'A family business built on precise work', 'About {company}', '{company} buys rough, plans and polishes diamonds, and supplies finished stones to jewellery businesses around the world. We focus on well-cut goods with consistent make.\n\nWe work directly with every client, from a single certified stone to regular parcel supply, and keep our grading and pricing transparent from the first quote.', NULL, 'Read our story', '/about.php', NULL, NULL),
('home', 'specialities', 10, 'Round brilliants', NULL, 'Excellent-cut rounds graded for cut, polish and symmetry, from melee to large certified stones.', 'diamond', NULL, NULL, NULL, NULL),
('home', 'specialities', 20, 'Hearts & arrows', NULL, 'Rounds cut to optical symmetry and checked under a scope before they are shipped.', 'spark', NULL, NULL, NULL, NULL),
('home', 'specialities', 30, 'Fancy shapes', NULL, 'Ovals, pears, emeralds, cushions and more, matched into pairs or layouts on request.', 'gem', NULL, NULL, NULL, NULL),
('home', 'specialities', 40, 'Fancy colours', NULL, 'Natural yellows, browns and pinks, graded and priced by colour intensity.', 'drop', NULL, NULL, NULL, NULL),
('home', 'feature', 10, 'Consistent make, stone after stone', 'Quality you can check', 'Every diamond we sell is inspected in-house before it is listed. We report what the certificate says and what our graders see, so there are no surprises when the parcel is opened.', NULL, 'See our diamonds', '/diamonds.php', NULL, NULL),
('home', 'process', 10, 'Search the inventory', NULL, 'Filter live stock by shape, carat, colour, clarity, lab and price.', 'search', NULL, NULL, NULL, NULL),
('home', 'process', 20, 'Request a quote or memo', NULL, 'Tell us what you need. We confirm availability and price within one business day.', 'mail', NULL, NULL, NULL, NULL),
('home', 'process', 30, 'Inspect with confidence', NULL, 'Stones travel insured with their certificates, or view them at our office.', 'shield', NULL, NULL, NULL, NULL),
('home', 'process', 40, 'Delivered worldwide', NULL, 'Insured shipping through specialist carriers to trade customers around the world.', 'truck', NULL, NULL, NULL, NULL),
('home', 'faq', 10, 'Who can buy from {company}?', NULL, 'We supply trade customers: jewellers, retailers, wholesalers and manufacturers. Register for an inventory account and we will verify your business before approving access.', NULL, NULL, NULL, NULL, NULL),
('home', 'faq', 20, 'Which laboratories certify your diamonds?', NULL, 'Most of our certified stones carry GIA, IGI or HRD reports. The lab and report number are listed with each stone in the inventory.', NULL, NULL, NULL, NULL, NULL),
('home', 'faq', 30, 'Can I take stones on memo?', NULL, 'Yes. Approved trade accounts can request stones on memo, subject to our standard memo terms.', NULL, NULL, NULL, NULL, NULL),
('home', 'faq', 40, 'How are shipments insured?', NULL, 'Every shipment is insured from our office to your door and sent through specialist secure carriers.', NULL, NULL, NULL, NULL, NULL),
('home', 'cta', 10, 'Looking for a specific stone?', NULL, 'Search our live inventory, or send us your requirements and we will source it for you.', NULL, 'Browse inventory', '/inventory.php', 'Send a request', '/contact.php'),
('about', 'hero', 10, 'Diamonds are our family trade', 'About us', 'We cut, grade and trade natural polished diamonds for jewellery businesses around the world.', NULL, NULL, NULL, NULL, NULL),
('about', 'story', 10, 'Where we come from', 'Our story', '{company} started as a small cutting workshop. Over the years the business grew into a manufacturer and trader supplying polished diamonds to clients across Europe, the Middle East, Asia and the Americas.\n\nThe principles have not changed. We know every stone we sell, we grade honestly, and we build long relationships with the businesses we supply.\n\nToday a second generation runs the company, combining traditional cutting skill with modern planning technology and an online inventory our clients can search at any time.', NULL, 'Our services', '/services.php', NULL, NULL),
('about', 'timeline', 10, 'The cutting workshop', NULL, 'A small team of cutters and polishers working on rough bought in the diamond district.', NULL, NULL, NULL, NULL, NULL),
('about', 'timeline', 20, 'Our trading office', NULL, 'A sales office opens to supply polished goods directly to jewellers and wholesalers.', NULL, NULL, NULL, NULL, NULL),
('about', 'timeline', 30, 'Worldwide supply', NULL, 'Regular clients across Europe, the Middle East, Asia and the Americas.', NULL, NULL, NULL, NULL, NULL),
('about', 'timeline', 40, 'Online inventory', NULL, 'Trade customers can search our full stock and request stones online.', NULL, NULL, NULL, NULL, NULL),
('about', 'values', 10, 'Consistency', NULL, 'The same make and the same grading standard on every stone, every parcel, every time.', 'scale', NULL, NULL, NULL, NULL),
('about', 'values', 20, 'Transparency', NULL, 'Clear grading, clear pricing and full disclosure on every stone we offer.', 'eye', NULL, NULL, NULL, NULL),
('about', 'values', 30, 'Long relationships', NULL, 'Most of our clients have bought from us for many years. We intend to keep it that way.', 'handshake', NULL, NULL, NULL, NULL),
('about', 'quote', 10, 'We stand out through our make and our consistency. The stone on the certificate is the stone in the box.', 'The {company} team', NULL, NULL, NULL, NULL, NULL, NULL),
('services', 'hero', 10, 'What we do for the trade', 'Services', 'Manufacturing, trading and sourcing of natural polished diamonds, with the service a trade partner needs.', NULL, NULL, NULL, NULL, NULL),
('services', 'intro', 10, 'One partner from rough to finished stone', 'How we can help', 'Whether you need a single certified stone for a client or regular parcels for production, we handle planning, cutting, grading, certification and delivery.', NULL, NULL, NULL, NULL, NULL),
('services', 'services', 10, 'Manufacturing', NULL, 'We plan, cut and polish rough in our own workshop, with full control over make and yield.', 'factory', NULL, NULL, NULL, NULL),
('services', 'services', 20, 'Polished trading', NULL, 'Certified and non-certified polished diamonds in a wide range of sizes, shapes and qualities.', 'diamond', NULL, NULL, NULL, NULL),
('services', 'services', 30, 'Certification', NULL, 'We submit stones to GIA, IGI and HRD and manage the process from submission to report.', 'certificate', NULL, NULL, NULL, NULL),
('services', 'services', 40, 'Sourcing on request', NULL, 'Tell us the specification. We search our stock and our trading network to find it.', 'search', NULL, NULL, NULL, NULL),
('services', 'services', 50, 'Memo and consignment', NULL, 'Approved clients can take stones on memo to show their own customers before buying.', 'handshake', NULL, NULL, NULL, NULL),
('services', 'services', 60, 'Pairs and layouts', NULL, 'Matched pairs, calibrated melee and full layouts sorted to your drawing.', 'layers', NULL, NULL, NULL, NULL),
('services', 'process', 10, 'Share your brief', NULL, 'Shape, size, colour, clarity, lab and budget, or send us a drawing.', 'mail', NULL, NULL, NULL, NULL),
('services', 'process', 20, 'Receive a proposal', NULL, 'We send matching stones with certificates, pictures and prices.', 'document', NULL, NULL, NULL, NULL),
('services', 'process', 30, 'Inspect the stones', NULL, 'On memo, at our office, or through high-resolution images and video.', 'eye', NULL, NULL, NULL, NULL),
('services', 'process', 40, 'Delivery and invoice', NULL, 'Insured shipping and invoicing to your company details.', 'truck', NULL, NULL, NULL, NULL),
('services', 'cta', 10, 'Tell us what you need', NULL, 'Send a specification and we will come back with matching stones and prices.', NULL, 'Send a request', '/contact.php', 'Browse inventory', '/inventory.php'),
('diamonds', 'hero', 10, 'Natural polished diamonds in stock', 'Our diamonds', 'Round brilliants, fancy shapes, melee and fancy colours, graded in-house and certified by leading laboratories.', NULL, 'Browse inventory', '/inventory.php', NULL, NULL),
('diamonds', 'intro', 10, 'What we carry', 'Our range', 'Our stock covers the goods our clients use most, from calibrated melee to large certified stones. Everything we list has been checked by our own graders.', NULL, NULL, NULL, NULL, NULL),
('diamonds', 'categories', 10, 'Round brilliants', NULL, 'Excellent cut, polish and symmetry, including hearts & arrows goods.', 'diamond', NULL, NULL, NULL, NULL),
('diamonds', 'categories', 20, 'Fancy shapes', NULL, 'Oval, pear, marquise, emerald, cushion, radiant, princess and heart.', 'gem', NULL, NULL, NULL, NULL),
('diamonds', 'categories', 30, 'Melee and small sizes', NULL, 'Calibrated, sorted parcels for setting and production.', 'layers', NULL, NULL, NULL, NULL),
('diamonds', 'categories', 40, 'Fancy colours', NULL, 'Natural yellow, brown and pink diamonds, graded by intensity.', 'drop', NULL, NULL, NULL, NULL),
('diamonds', 'categories', 50, 'Large certified stones', NULL, 'Single stones with GIA, IGI or HRD reports for engagement and high jewellery.', 'certificate', NULL, NULL, NULL, NULL),
('diamonds', 'categories', 60, 'Matched pairs', NULL, 'Pairs matched on size, colour, clarity and make for earrings and side stones.', 'ring', NULL, NULL, NULL, NULL),
('diamonds', 'inventory', 10, 'Search the live inventory', 'Inventory', 'Our inventory is updated from our stock system. Filter by shape, size, colour, clarity, lab and price, then request stones directly.', NULL, 'Open inventory', '/inventory.php', 'Ask about a stone', '/contact.php'),
('diamonds', 'education', 10, 'Cut', NULL, 'Cut decides how a diamond returns light. A well-cut stone shows brightness, fire and scintillation.', 'diamond', NULL, NULL, NULL, NULL),
('diamonds', 'education', 20, 'Colour', NULL, 'Colourless diamonds are the rarest. The grade runs from D, colourless, to Z, light yellow or brown.', 'drop', NULL, NULL, NULL, NULL),
('diamonds', 'education', 30, 'Clarity', NULL, 'Clarity describes inclusions and blemishes, graded from Flawless to Included under 10x magnification.', 'search', NULL, NULL, NULL, NULL),
('diamonds', 'education', 40, 'Carat', NULL, 'Carat is weight: one carat is 0.2 grams. Larger stones are rarer, so price rises faster than size.', 'scale', NULL, NULL, NULL, NULL),
('responsible', 'hero', 10, 'Trading with integrity', 'Responsible practices', 'How we source, trade and disclose, so our clients can sell our diamonds with confidence.', NULL, NULL, NULL, NULL, NULL),
('responsible', 'intro', 10, 'Our responsibility to the trade and to your customers', 'Our commitment', 'Confidence in diamonds depends on every company in the chain. We hold ourselves to clear standards on sourcing, business conduct and disclosure, and we ask the same of our suppliers.', NULL, NULL, NULL, NULL, NULL),
('responsible', 'practices', 10, 'Responsible sourcing', NULL, 'We buy rough and polished only from known suppliers within the Kimberley Process, and we avoid any trade in diamonds linked to conflict or human rights abuse.', 'shield', NULL, NULL, NULL, NULL),
('responsible', 'practices', 20, 'Fair business', NULL, 'Safe working conditions, fair pay and respect for labour law in our workshop, and we expect the same from the partners we work with.', 'handshake', NULL, NULL, NULL, NULL),
('responsible', 'practices', 30, 'Full disclosure', NULL, 'We trade natural diamonds and disclose any treatment. Our goods are screened to detect lab-grown and treated stones before they are sold.', 'eye', NULL, NULL, NULL, NULL),
('responsible', 'standards', 10, 'Kimberley Process', NULL, 'Every import and export of rough travels with a Kimberley Process certificate.', 'certificate', NULL, NULL, NULL, NULL),
('responsible', 'standards', 20, 'System of Warranties', NULL, 'Our invoices carry the World Diamond Council System of Warranties statement.', 'document', NULL, NULL, NULL, NULL),
('responsible', 'standards', 30, 'Know your customer', NULL, 'We verify the businesses we trade with, in line with anti-money-laundering rules.', 'users', NULL, NULL, NULL, NULL),
('responsible', 'standards', 40, 'Screening', NULL, 'Polished goods are screened for synthetic and treated diamonds.', 'search', NULL, NULL, NULL, NULL),
('responsible', 'quote', 10, 'We would rather lose a sale than sell a stone we cannot stand behind.', '{company}', NULL, NULL, NULL, NULL, NULL, NULL),
('sustainability', 'hero', 10, 'Reducing the footprint of every carat', 'Sustainability', 'The steps we are taking in our workshop, our office and our supply chain.', NULL, NULL, NULL, NULL, NULL),
('sustainability', 'intro', 10, 'Precision is also about waste', 'Our approach', 'Careful planning of every rough stone means more of it becomes polished diamond. We apply the same thinking to energy, materials and shipping.', NULL, NULL, NULL, NULL, NULL),
('sustainability', 'pillars', 10, 'Energy', NULL, 'We are reducing electricity use in cutting and polishing, and moving to renewable power where we can.', 'leaf', NULL, NULL, NULL, NULL),
('sustainability', 'pillars', 20, 'Materials', NULL, 'Diamond powder, scaife dust and packaging are collected and recycled.', 'recycle', NULL, NULL, NULL, NULL),
('sustainability', 'pillars', 30, 'People', NULL, 'Training, safe workplaces and long-term employment for our cutters and staff.', 'users', NULL, NULL, NULL, NULL),
('sustainability', 'pillars', 40, 'Shipping', NULL, 'Consolidated insured shipments and reusable parcel papers and boxes.', 'box', NULL, NULL, NULL, NULL),
('sustainability', 'closing', 10, 'Questions about our sourcing or environmental work?', NULL, 'We are happy to share more detail with clients who need it for their own reporting.', NULL, 'Contact us', '/contact.php', NULL, NULL),
('contact', 'hero', 10, 'Get in touch with our team', 'Contact us', 'Questions about a stone, a parcel or a trade account? We reply within one business day.', NULL, NULL, NULL, NULL, NULL),
('contact', 'intro', 10, 'Visit or call us', 'Our office', 'Trade customers are welcome at our office by appointment. Call or email us to arrange a visit.', NULL, NULL, NULL, NULL, NULL),
('contact', 'form', 10, 'Send us a message', NULL, 'Tell us what you are looking for and how to reach you.', NULL, NULL, NULL, NULL, NULL),
('home', 'specialities_intro', 10, 'What we cut and trade', 'Our specialities', NULL, NULL, NULL, NULL, NULL, NULL),
('home', 'process_intro', 10, 'How buying from us works', 'Step by step', NULL, NULL, NULL, NULL, NULL, NULL),
('home', 'faq_intro', 10, 'Questions from the trade', 'FAQ', 'Anything else? Our team is a phone call away.', NULL, 'Contact us', '/contact.php', NULL, NULL),
('about', 'timeline_intro', 10, 'How the business grew', 'Milestones', NULL, NULL, NULL, NULL, NULL, NULL),
('about', 'values_intro', 10, 'What we stand for', 'Our values', NULL, NULL, NULL, NULL, NULL, NULL),
('services', 'services_intro', 10, 'Everything a trade partner needs', 'Our services', NULL, NULL, NULL, NULL, NULL, NULL),
('services', 'process_intro', 10, 'How a request works', 'The process', NULL, NULL, NULL, NULL, NULL, NULL),
('diamonds', 'categories_intro', 10, 'Shapes, sizes and colours', 'In stock', NULL, NULL, NULL, NULL, NULL, NULL),
('diamonds', 'education_intro', 10, 'The four Cs', 'Diamond basics', 'Every diamond is graded on four characteristics. Together they decide its beauty, rarity and price.', NULL, NULL, NULL, NULL, NULL),
('responsible', 'practices_intro', 10, 'Principles we trade by', 'Our practices', NULL, NULL, NULL, NULL, NULL, NULL),
('responsible', 'standards_intro', 10, 'Standards and compliance', 'Compliance', NULL, NULL, NULL, NULL, NULL, NULL),
('sustainability', 'pillars_intro', 10, 'Where we are making changes', 'Our focus', NULL, NULL, NULL, NULL, NULL, NULL);
