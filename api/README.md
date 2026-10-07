# Mobile app API

JSON endpoints used by the Diamond Search Android app. They reuse the
website's own login, search, results and cart code, so the app always
shows exactly what the website shows.

## Installing

Upload these files to the server, keeping their folders:

```
api/                         (new folder, 9 endpoints + this README)
includes/api.php             (new)
includes/diamond_search_form.php   (new: search-filter builder, shared by web + app)
includes/diamond_copy.php    (new: "Copy" text generator, shared by web + app)
modules/user/diamond_search.php    (changed: now uses diamond_search_form.php)
modules/user/copy_generate.php     (changed: now uses diamond_copy.php)
```

No database changes are needed. The site must be served over HTTPS
(it already forces this in production).

The two changed pages behave exactly as before; their logic was moved
into the shared includes, not altered.

## Endpoints

| Endpoint | Method | Purpose |
|---|---|---|
| `session.php` | GET | Sign-in required? Cart on? Who's signed in, company name/logo |
| `login.php` | POST | Sign in (same lockout/approval/audit rules as the website) |
| `logout.php` | POST | Sign out and revoke "remember me" tokens |
| `search_form.php` | GET | Filters, from `diamond_search` / `adv_filter` (incl. Fancy Filter) |
| `search.php` | POST | One page of results + total + applied-filter summary |
| `diamond.php?id=` | GET | Diamond Details fields, media links, certificate link |
| `cart.php` | GET/POST | The `selection` cart: list, add, remove, clear |
| `selected.php` | POST | Stones by id (guest mode, when there is no cart) |
| `share.php` | POST | Same text as the Results page's "Copy" button |

Errors come back as `{"error": {"code": "...", "message": "..."}}` with
a matching HTTP status; `message` is written for the end user.

## Security

- **Authentication** is the website's own session cookie plus its
  30-day "remember me" token (rotated on use, revoked on sign-out).
- **Cross-site requests** are blocked: every endpoint requires the
  header `X-Requested-With: DiamondApp`, which a browser can't send to
  another site without a CORS preflight that these endpoints never allow.
- **Input** is size-limited JSON; filter values are reduced to strings
  or lists of strings before reaching `build_maindata_search_where()`,
  which already validates column names and binds every value.
- Guest browsing (`setup.loginscrn = 'no'`) and user levels work exactly
  as on the website; the admin and superadmin modules are not exposed.

## Behaviour worth knowing

- A search from the app also updates the website's "Back to Search"
  memory for that session, as a web search does.
- `share.php` records to `self_short_urls` and updates `mkprice` /
  `mkamt` / `mkdis`, exactly like the website's Copy button.
