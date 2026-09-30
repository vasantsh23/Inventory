# Public website + template system

This adds the public website (Home, About, Services, Diamonds, Responsible
Practices, Sustainability, Contact Us) with 3 built-in designs and an admin
option to create more. The inventory module is not changed.

## Install (existing installation)

1. **Back up** the database: Admin → Backup & Restore.
2. Upload the files, then open **Admin → Website → Templates** and click
   **Install website tables now**. That button runs
   `sql/migration_website_templates.sql` for you through the site's own
   database connection. If anything fails, the database's exact error is
   shown on screen. Alternatively, run that file yourself in phpMyAdmin
   (SQL tab, with this site's database selected). It only creates four new
   tables — `website_templates`, `website_blocks`, `website_content` and
   `website_enquiries` — and adds new Theme Settings rows. It never changes or
   removes existing tables (for example `site_content` or `site_options`
   from other software), and is safe to run twice.
3. Make sure **`assets/site/uploads/`** is writable by the
   web server (usually 755). Images uploaded in the admin are stored there.
4. Open **Admin → Website → Templates**. Atelier is live by default.

## What changed in existing files

| File | Change |
|---|---|
| `index.php`, `about.php`, `contact.php` | Replaced: they now render the new website pages. |
| `includes/admin_header.php` | Added a **Website** section to the sidebar (Templates, Page Content, Enquiries). |

Everything else is new. `includes/header.php`, which the inventory screens
use, is untouched. Its Home / About / Contact links now lead to the new pages.

New pages: `services.php`, `diamonds.php`, `responsible-practices.php`,
`sustainability.php`.

## Admin → Website

**Templates** — every template, with a preview picture and a Live badge.

- **Preview**: see a template on the real site. Only you see it; visitors
  keep the live one. A bar at the bottom lets you make it live or exit.
- **Make live**: switch the website to this template.
- **Add template / Duplicate**: create your own template as a copy of any
  other one. Then choose the style of each part (header, hero, footer, and
  each of the 13 section types) from Atelier, Heritage or Noir, upload a
  preview picture, and set its own colours and fonts.
- **Colours & fonts**: opens that template's section in Theme Settings.
- **Delete**: removes one of your templates, including its colours and page
  layouts. Built-in templates cannot be deleted, and nor can the live one.

**Page Content** — every text, image and link, page by page, shared by all
templates.

- **Sections on this page**: which sections the chosen template shows, and
  in what order. Each template keeps its own arrangement.
- **Placeholders**: `{company}`, `{phone}`, `{email}`, `{address}` and
  `{year}` insert details from Site Setup.
- **Images**: upload JPEG, PNG, WebP or GIF (max 6 MB), or pick an
  existing image.
- **Links**: use `page:contact`, `inventory`, `/login.php`, or a full
  `https://` link.
- **Site-wide**: menu labels (leave one blank to hide that page), the
  header button, footer text, contact-form settings, and a logo for dark
  backgrounds.

**Enquiries** — messages from the contact form: reply, mark as read,
archive or delete. New messages are also emailed to the company email
(or the address set in Page Content → Site-wide → Contact form) when the
server can send mail.

## Good to know

- **Images must be on your own server.** The site's security policy
  (`config/config.php`) blocks images and maps from other websites. Upload
  photos instead of linking to them. "Directions" links to Google Maps work.
- **Logo on dark headers**: the current logo has a white background. For the
  Noir header and the dark footers, upload a light/transparent version in
  Page Content → Site-wide → Brand. Until then the company name is shown.
- **The bundled images** in `assets/site/img/` are original renders made for
  this site, so you can use them freely. Replace them with your own
  photography whenever you like.
- **Contact form protection**: security token, hidden spam field, minimum
  fill time, and a limit of 5 messages per hour per visitor.

## For developers: adding a new style family

A *template* is a record in `site_templates` that picks a *family* for each
part. To add a new family (for example `lumiere`):

1. Create `site/templates/lumiere/` containing `header.php` and
   `footer.php`. Optionally add `blocks/<type>.php` for any section whose
   HTML should differ; the rest fall back to `site/templates/_shared/blocks/`.
2. Create `assets/site/css/lumiere.css`, with section rules nested under
   `.part--lumiere`. Use only the generic tokens: `--bg`, `--surface`,
   `--card`, `--ink`, `--muted`, `--accent`, `--line`, `--brand`,
   `--on-brand`, `--dark`, `--on-dark`, `--heading-font`, `--body-font`, etc.
3. Add it to `Site::FAMILIES` (and to `BUILTIN` + `PALETTES` if it should
   also appear as a built-in template).

It then shows up as a choice for every part in Add / Edit template.
