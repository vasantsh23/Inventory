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
    download_backup.php   Serves a backup file (authenticated only)
  superadmin/index.php   superadmin only
assets/css/style.css      Design system (dark, glassmorphic, gradient accents)
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
  `timings`, `font_and_color`, `upload`) gets add/edit/delete screens
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
- **Live theming from Fonts & Colors** — the `font_and_color` table
  stores 5 numbered slots each for font type, font size, text color
  and background color. Four `selected_*` columns pick which slot is
  active; `get_active_theme()` in `includes/functions.php` resolves
  the actual values, and `includes/header.php` applies them as CSS
  directly to the public page content area (Home, About Us,
  Inventory, Contact Us) — without touching the admin dashboard's own
  styling. Editing a font/color slot's value, or picking a different
  slot as "active", changes the public site's look immediately with
  no code changes.
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
   **If you already had the `font_and_color` table before this
   update**, also run `sql/migration_theme_selection.sql` to add the
   four `selected_*` columns.
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

