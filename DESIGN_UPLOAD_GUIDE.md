# Header buttons and uploading a new design

## 1. Header buttons (table `buttons`)

The buttons in the site header (Home, About Us, Inventory, Contact Us …) come
from the table `buttons`. The same buttons appear on every page — the website
pages, Diamond Search, Results, View Cart, the login page — and in every
design, including designs you upload later.

| Field          | Meaning                                                        |
|----------------|----------------------------------------------------------------|
| `id`           | Position. Buttons are shown left to right, lowest id first.    |
| `button_label` | Text on the button (max 40 characters).                        |
| `active`       | `yes` = shown, `no` = hidden.                                  |
| `URL`          | Where the browser goes when the button is clicked.             |

The table has 10 rows (10 buttons). Rows 1–4 start as Home, About Us,
Inventory and Contact Us; rows 5–10 start empty and inactive.

**Install (once):** back up the database (Admin → Backup & Restore), then run
`sql/migration_buttons.sql` in phpMyAdmin (SQL tab, this site's database
selected). It is safe to run twice. Until it is run, the header keeps showing
the original four buttons.

**Edit:** Admin → Manage Tables → **Header Buttons**. Click a row, change the
label, Active or URL, and save. "+ Add New" is hidden while all 10 rows exist;
to hide a button set Active to No rather than deleting it.

**What a URL can be**

| Example                              | Goes to                        |
|--------------------------------------|--------------------------------|
| `/about.php` or `about.php`          | a page on this site            |
| `/modules/user/diamond_search.php`   | Diamond Search                 |
| `https://www.example.com/page`       | another website                |
| `mailto:sales@example.com`           | a new email                    |
| `tel:+3232000000`                    | a phone call (on phones)       |
| `#contact`                           | a section on the same page     |

Anything else (for example `javascript:…`) is refused when you save. An active
button needs both a label and a URL. The button of the page you are on is
highlighted automatically.

## 2. What a design is

A design is one folder in `site/templates/`. The folder name is the design's
**key**: lower-case letters, digits and hyphens, starting with a letter (for
example `lumiere`). Folders that start with `_` are ignored, which is why the
starter is called `_starter`.

```
site/templates/lumiere/
├── design.json        required  name and options
├── header.php         header (used when "own_header": true)
├── footer.php         footer (used when "own_footer": true)
├── design.css         the design's styles
├── blocks/            optional  your own markup for any section
│   └── hero.php       (hero, intro, split, features, cards, steps, stats,
│                       shapes, testimonials, faq, credentials, cta,
│                       contact, offices)
└── img/               optional  images used by design.css
```

Sections without a file in `blocks/` use the shared markup in
`site/templates/_shared/blocks/` and are styled by your `design.css`.

### design.json

```json
{
    "name": "Lumière",
    "about": "Light and airy, wide photography, gold details.",
    "header": "Logo left, header buttons right",
    "hero": "Large photograph with the title over it",
    "footer": "Three columns on the dark colour",
    "own_header": true,
    "own_footer": true
}
```

- `name` is required. A folder with a missing or broken `design.json` is
  ignored, so it can never break the live site.
- `own_header` / `own_footer`: `true` uses this design's `header.php` /
  `footer.php`. `false` (or left out) keeps the shared header and footer bars
  that the inventory pages use.

### Rules every design must follow

1. **Header buttons:** the header prints the buttons with
   `<?= site_header_buttons('menu') ?>`. Never type menu links by hand — then
   the buttons from the `buttons` table work in your design automatically.
2. **First line of every .php file:**
   `if (!function_exists('site_render')) { http_response_code(404); exit; }`
   (stops the file being opened directly in a browser).
3. **CSS:** scope every rule under `.part--<key>` and use only the palette
   tokens (`--bg`, `--surface`, `--card`, `--ink`, `--muted`, `--accent`,
   `--on-accent`, `--line`, `--brand`, `--on-brand`, `--dark`, `--on-dark`,
   `--heading-font`, `--body-font`, `--heading-weight`, `--base-size`,
   `--btn-radius`, `--radius`, `--section-space`). Colours and fonts are then
   set per template in Admin → Theme Settings.
4. **Images** must be inside the design folder (`url("img/photo.webp")`) or
   uploaded in Page Content. Images from other websites are blocked by the
   site's security policy.

## 3. Steps to upload a new design

1. **Copy the starter.** Copy `site/templates/_starter/` and rename the copy
   to your key, e.g. `lumiere`. (`design-starter-template.zip` contains the
   same files, already named `mydesign`.)
2. **Name it.** Edit `design.json`: name, one-line description, and the short
   header / hero / footer descriptions shown in the admin.
3. **Set the key in the CSS.** In `design.css` replace every `mydesign` with
   your key (`.part--mydesign` → `.part--lumiere`).
4. **Design it.** Change `design.css`, and `header.php`, `footer.php`,
   `blocks/*.php` as needed. Keep the `site_header_buttons()` call in the
   header.
5. **Upload the folder** to `site/templates/` on the server — cPanel File
   Manager (upload the zip, then Extract), FTP, or commit it to the repository
   so the GitHub deploy copies it.
6. **Create a template that uses it.** Admin → Website → Templates →
   **Add template**. Give it a name, start from any built-in template (that
   sets its starting colours), then under *Header, hero and footer* and
   *Section styles* choose your design. You can also mix: for example your
   header with Heritage sections.
7. **Preview.** Click Preview. Only you see it; visitors keep the live design.
   Check the home page, an inner page and a phone-width window. Check that
   the header buttons and their links are right.
8. **Colours & fonts.** Adjust the template's palette if needed.
9. **Make live.** Back on Templates, click *Make live* on your template.

To update a design later, upload the changed files over the old ones; browsers
pick up the new `design.css` automatically. To remove one, first make another
template live, then delete the folder — templates that used it fall back to
their starting design.
