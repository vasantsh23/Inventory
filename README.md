# Inventory Management System — Admin Module

A PHP 8 + MySQL implementation of the admin module: a public home
page with a futuristic dark/glass design, login-gated entry into the
Inventory area with "remember me" and a password visibility toggle,
and a full admin dashboard with generic CRUD, Excel import/export,
and database backup/restore for every table.

## Structure

```
config/
  config.php      Environment-driven configuration, security headers, secure sessions, BASE_URL auto-detection
  db.php          PDO connection (prepared statements only)
includes/
  security.php    AES-256-GCM encryption, CSRF tokens, output escaping
  functions.php   Reads company info from `setup`, logo path from `path`
  auth.php        Login, lockout, session, "remember me", role-based access control
  crud_config.php Table/column metadata registry for the admin CRUD screens
  crud_engine.php Generic list/get/save/delete + Excel export/import logic, driven by table metadata
  xlsx_lite.php   Dependency-free XLSX reader/writer (ZipArchive + SimpleXML — no Composer needed)
  backup.php      Full SQL dump backup + restore
  header.php / footer.php           Public site layout (Home / About Us / Inventory / Contact Us)
  admin_header.php / admin_footer.php  Admin dashboard shell (sidebar + top bar)
index.php / about.php / contact.php  Public pages
inventory.php     Entry point — sends to /login.php if not authenticated,
                   otherwise routes to the correct module by usertype
login.php         Login screen (remember me + show/hide password)
logout.php        Destroys the session and any remember-me token
modules/
  user/index.php        user, admin, superadmin
  admin/
    index.php            Dashboard overview (stats + quick links)
    table_view.php       Generic list view: search, pagination, export/import buttons
    table_form.php       Generic add/edit form (any table)
    table_delete.php     Row deletion (POST + CSRF)
    table_export.php     Streams the current table as .xlsx
    table_import.php     Uploads and imports an .xlsx file into a table
    backup.php            Backup & restore UI
    theme_settings.php    Theme Settings: every colour/font/size, quick edit + live preview
    theme_setting_form.php Add / edit one theme setting
    theme_sections.php    Add / rename / reorder / delete theme sections
    theme_actions.php     Delete / reset (one, section, all) — POST + CSRF
    download_backup.php   Serves a backup file (authenticated only)
  superadmin/index.php   superadmin only
assets/css/style.css      Design system — every colour and font is a var(--…) from theme_settings
theme.css.php             Outputs theme_settings as CSS custom properties (:root { --key: value; })
includes/ThemeSettings.php  Theme data access + validation
includes/theme.php        theme_head_tags(): Google Fonts link + theme.css.php link for every page
includes/fonts.php        Font dropdown library + Google Fonts URL builder
storage/backups/          Generated .sql backups (blocked from direct HTTP access)
sql/schema.sql            Table definitions matching Admintablestructures.xlsx
.env.example               Required environment variables (for hosts that support them)
```

## How it maps to the spec

- **Home page options** (Home, About Us, Inventory, Contact Us) are rendered
  by `includes/header.php` on every page, styled with the new dark/glass theme.
- **Company name, address, telephone, logo, email** come from the `setup`
  table via `get_setup()` in `includes/functions.php`.
- **Logo path** is resolved from the `path` table by searching
  `description = 'logo'` — see `get_logo_path()`.
- **Selecting "Inventory"** always routes through `login.php` first; the
  login screen has a **password show/hide toggle** and a **"Remember me
  for 30 days"** checkbox (secure selector/validator token, rotated on
  every use, revoked on logout).
- **Three user types**, enforced server-side on every protected page via
  `require_module_access()`:
  - `user` → user module only
  - `admin` → user + admin modules
  - `superadmin` → user + admin + superadmin modules
- **Admin dashboard CRUD** — every table (`user`, `user_types`, `setup`, `path`,
  `timings`, `upload`) gets add/edit/delete screens
  automatically, driven by `includes/crud_config.php` +
  `includes/crud_engine.php`. Column types (select, color picker,
  password, encrypted, textarea, lookup/foreign-key dropdown) are
  inferred from the database schema plus a small per-table override
  list — new columns show up automatically without code changes.
- **User types & access levels** — roles are no longer a fixed 3-value
  set. The `user_types` table defines any number of named roles, each
  with a `level` (0-9): levels 0-7 are custom user-only roles (define
  as many as you like — "Warehouse Staff", "Cashier", etc.), level 8
  is Admin (user + admin modules), level 9 is Super Admin (all three
  modules). The `user` table's `usertype` column stores the id of the
  selected row. Adding/editing a user shows a live dropdown of
  available types (`table_form.php`'s "lookup" field type), and
  deleting a type still assigned to an account is blocked by a
  foreign-key constraint rather than silently corrupting data.
- **Theme settings (colours, fonts, sizes)** — every colour, font
  family, font size, font weight, text case and themed spacing on
  every page (public site, user module, admin dashboard and the
  printable memo) comes from the `theme_settings` table, grouped by
  `theme_sections` (Global, Header & navigation, Buttons, Forms,
  Tables, Admin dashboard, Diamond Search page, Memo printout, …).
  One row = one CSS custom property: `/theme.css.php` turns
  `header-bg = #ffffff` into `--header-bg: #ffffff;` and
  `assets/css/style.css` only ever uses `var(--header-bg)`.
  Edit them in **Admin → Appearance → Theme Settings**: colour
  pickers, a font dropdown (any Google Fonts family can be typed as a
  custom stack and is loaded automatically), live previews, search,
  save many at once, and reset one setting, a section or the whole
  theme to its default. Values are validated on save *and* again when
  the CSS is generated, so a bad value can never break or inject into
  a page. The stylesheet URL carries a version hash, so changes show
  immediately. The Results / View Cart table font (formerly
  `rsetup.fontype` / `fontsize`) and the "not for web" Stock No
  highlight are theme settings too. To style something new, add a
  setting (e.g. key `promo-border`) and use `var(--promo-border)` in
  `assets/css/style.css`.
- **Excel import/export** — every table list view has an "Export to
  Excel" button and an "Import from Excel" upload form. Built with a
  **dependency-free** XLSX reader/writer (`includes/xlsx_lite.php`)
  using PHP's built-in ZipArchive/SimpleXML, since shared hosting
  (BigRock/cPanel) typically has no Composer access for PhpSpreadsheet.
  Import matches rows by the primary key column to update existing
  records, or inserts new ones.
- **Backup & restore** — `modules/admin/backup.php` generates a full
  SQL dump (structure + data) of every table, lists previous backups,
  and lets a superadmin restore from an uploaded `.sql` file. Backups
  are stored under `storage/backups/`, blocked from direct HTTP access
  via `.htaccess`, and only ever served through the authenticated
  `download_backup.php`.

## Security measures implemented

| Concern                | Mitigation |
|-------------------------|------------|
| SQL injection            | PDO prepared statements everywhere, including the generic CRUD engine (column names validated against an information_schema-derived allowlist, values always bound) |
| Password storage          | `password_hash()` / `password_verify()` (bcrypt), never reversible |
| Sensitive PII at rest     | Email, phone, GSM, address, and API keys encrypted with AES-256-GCM (auto-detected for VARBINARY columns); lookups use an HMAC hash column instead of plaintext |
| "Remember me" tokens       | Selector/validator pattern — a stolen cookie or a leaked DB row alone can't be replayed; rotated on every use, revoked on logout |
| Brute-force login          | Failed-attempt counter + temporary account lockout, logged to `login_audit` |
| Session hijacking / fixation | `session_regenerate_id()` on login, `HttpOnly`/`Secure`/`SameSite` cookies |
| CSRF                       | Per-session token verified on every state-changing POST (login, CRUD save/delete, import, backup/restore) |
| XSS                        | All dynamic output passed through `e()` (`htmlspecialchars`) |
| Transport security          | Forces HTTPS in production, sends CSP / X-Frame-Options / nosniff headers |
| Information leakage         | Generic "invalid login ID or password" message; no user-enumeration; DB errors never shown to the client; password/secret columns never included in exports or re-displayed in edit forms |
| Broken access control        | Every module and admin page calls `require_module_access()` server-side *before* touching the database — the UI never relies on hiding links alone |
| Destructive operations        | Restoring a backup (which drops and recreates tables) is restricted to superadmin accounts; an admin cannot delete their own logged-in account by mistake |
| Path traversal                | Backup filenames are validated against a strict pattern before being read from disk; `storage/` is blocked from direct HTTP access |

## Setup

1. In phpMyAdmin, **select your existing database first** (e.g.
   `atest8a6_inventorydb` on BigRock/cPanel), then run `sql/schema.sql`
   on the SQL tab. The file no longer creates or switches databases —
   shared-hosting DB users typically can't run `CREATE DATABASE`.
   **If you're upgrading a database that already has an older version
   of this app** (i.e. `user.usertype` is still the old
   `ENUM('user','admin','superadmin')`), run
   `sql/migration_user_types.sql` instead — it adds the new
   `user_types` table and converts your existing accounts over
   without losing any data. Do not run both files against the same
   database.
   **Upgrading an existing installation to theme settings:** back up
   the database (Admin → Backup & Restore), then run
   `sql/migration_theme_settings.sql` once, **before** uploading the
   new files. It creates `theme_sections` / `theme_settings` with
   values that reproduce the current look, carries over your active
   Fonts & Colors choices and Results font, then drops the old
   `font_and_color` table and the `rsetup.fontype` / `fontsize`
   columns. (Fresh installs from `sql/schema.sql` already include the
   theme tables — don't run the migration there.)
2. Copy `.env.example` to your environment if your host supports env
   vars; otherwise set `DB_PASS` and the encryption keys directly in
   `config/config.php` (see the warnings at the top of that file).
3. Insert a `setup` row with your company details, and a `path` row with
   `description = 'logo'` pointing at your logo file.
4. Create your first superadmin account with a securely hashed password
   (e.g. via a one-off script calling `password_hash()`), with
   `approval = 'approved'`.
5. Point your web server's document root at this folder, ensure HTTPS is
   configured, and add a `.htaccess` blocking direct access to
   `config/config.php` (snippet included at the bottom of that file).

## Notes / next phases

- User and superadmin module *content* (beyond the landing pages and
  the shared CRUD dashboard) is out of scope for this phase per the
  spec.
- This was tested end-to-end against a real MySQL/MariaDB instance:
  schema load, CRUD add/edit/delete with encryption, Excel export →
  re-import round-trips (verified byte-for-byte with an independent
  Python XLSX reader), and a full backup → delete → restore cycle
  that correctly recovers deleted data.
- Consider adding 2FA and a Web Application Firewall in front of the
  app for defense-in-depth, since "secured against any type of hack"
  has no single technical solution — layered controls are what
  actually reduce risk.


## Public website and template selection

The public website has seven pages: **Home** (`index.php`), **About**
(`about.php`), **Services** (`services.php`), **Diamonds**
(`diamonds.php`, links into the inventory), **Responsible Practices**
(`responsible-practices.php`), **Sustainability** (`sustainability.php`)
and **Contact Us** (`contact.php`).

Three designs ship with it. Choose one in **Admin → Website → Website
Template**:

| Template | Look | Based on |
|----------|------|----------|
| **Maison** | Light and editorial: fine serif, champagne accents, full-bleed image halves, dark contact band | Amour Pur, Kediam |
| **Noir** | Dark charcoal and gold: info bar, rotating hero, key-figures strip, card grids | NN Diamonds |
| **Atelier** | Clean and corporate: deep teal band, split hero, step timeline, ruled lists with red markers, FAQ accordion | Diamond Brothers, Trishla |

Switching template changes the **layout of every page** at once. The
content stays the same, because all three templates read the same rows.

### Setup

1. Back up the database (Admin → Backup & Restore).
2. Run `sql/migration_site_templates.sql` in phpMyAdmin's SQL tab. It
   works on fresh and existing installs and is safe to run again,
   because it never overwrites rows you have edited.
3. Upload the files. Maison is live by default.

### What the admin controls

- **Which template is live.** Use Website Template → *Make live*.
  *Preview* shows another template only to you (a blue bar appears on
  the site); visitors keep seeing the live one until you make it live.
- **Colours, fonts and sizes.** Each template has its own section in
  Theme Settings ("Website template: Maison / Noir / Atelier"), about 30
  settings each, with keys `--mz-*`, `--nr-*` and `--at-*`. Every
  template keeps its own palette, so switching back restores it.
- **Every heading, paragraph, image, icon and button.** These are in
  Admin → Website Content (the `site_content` table). Each row belongs
  to a *page* and a *block* (for example `home` / `hero`). Rows with the
  same page and block form a list, shown in `sort_order`. A block named
  `<list>_intro`, such as `services_intro`, is the heading above that
  list.
  - `{company}` in any text inserts the company name from Site Setup.
  - Set *Show on website* to No to hide a row. A block with no visible
    rows is left out of the page.
  - Image paths must be files on this website, such as
    `/assets/img/site/hero.jpg`, because the site's Content Security
    Policy blocks images from other domains. An empty image shows the
    template's built-in diamond artwork.
  - Several `home` / `hero` rows become slides in Noir's rotating hero.
    The other templates use the first row only.
- **Company details.** The logo, address, phone numbers, email, social
  links, opening hours, meta tags and favicon come from Site Setup,
  File Paths and Business Hours, exactly as before. Put a light version
  of the logo in `setup.Logo-2` for the dark headers and footers. With
  no logo file, the company name is shown as a wordmark.
- **Enquiries.** Messages from the contact form go to Admin → Website
  Enquiries (`contact_messages`), with email and phone encrypted like
  all other personal data. When the server can send mail, a copy also
  goes to `emailid1`. The form checks the CSRF token, ignores spam bots
  (a hidden honeypot field), allows one message per 30 seconds per
  visitor, and validates every field.

### Changing or adding templates

Each template is a folder in `templates/<key>/` plus a stylesheet
`assets/css/site/<key>.css`:

- `template.php` holds the name, description and the section list of
  every page. Reorder or remove lines there to change a page's
  structure.
- `sections.php` holds the section renderers.
- `header.php` and `footer.php` hold the page frame.

A new folder with these files appears automatically in Website
Template. Give it a Theme Settings section as the migration does for
the existing three. `includes/site.php` holds the shared engine and
`includes/site_icons.php` the icon set.
