<?php
/**
 * site_chrome.php
 * The new home page's header (with the mobile menu drawer) and
 * footer, as reusable pieces for the other parts of the sample site:
 *   - sample/diamond-filter  (Inventory)
 *   - sample/cat             (Diamond Jewellery)
 * so every page shares the home page's design, colours and fonts.
 *
 * Usage, inside <head>:      <?php os_head_assets(); ?>
 * right after <body>:        <?php os_render_header('jewellery'); ?>
 * just before </body>:       <?php os_render_footer(); ?>
 *
 * Styles: sample/assets/site.css (all classes are prefixed "os-" so
 * they never clash with Bootstrap or either app's own stylesheet).
 */

declare(strict_types=1);

require_once __DIR__ . '/sample_auth.php';

function os_h(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

/** Version string from a file's modification time, for cache-busting. */
function os_asset(string $path): string
{
    $file = dirname(__DIR__) . '/' . ltrim($path, '/');
    $v = @filemtime($file);
    return sample_url($path) . ($v ? '?v=' . $v : '');
}

/** Fonts + shared stylesheet. Call inside <head>, before page-specific CSS overrides. */
function os_head_assets(): void
{
    ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Newsreader:ital,opsz,wght@0,6..72,300;0,6..72,400;0,6..72,600;0,6..72,700;1,6..72,400&family=Red+Hat+Display:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= os_h(os_asset('/assets/site.css')) ?>">
    <?php
}

/** The links used in the header, drawer and footer. */
function os_links(): array
{
    $user = sample_current_user();
    return [
        'home'        => sample_url('/home.php'),
        'certified'   => sample_url('/home.php#certified-diamonds'),
        'melees'      => sample_url('/home.php#calibrated-melees'),
        'about'       => sample_url('/home.php#about'),
        'contact'     => sample_url('/home.php#contact'),
        'jewellery'   => sample_member_link('/cat/filter.php'),
        // Inventory, cart, login and logout belong to the main inventory app
        // (web root), not the old copy in sample-module/diamond-filter/, which
        // has its own empty SQLite database. The main app asks for a login
        // itself when needed and then routes Admins to the Admin module.
        'inventory'   => main_app_url('/inventory.php'),
        'cart'        => main_app_url('/modules/user/view_results.php'),
        'login'       => main_app_url('/login.php'),
        'logout'      => main_app_url('/logout.php'),
        'user'        => $user,
    ];
}

/**
 * Header + mobile drawer.
 * $active: 'jewellery' | 'inventory' | '' — which menu item is marked current.
 */
function os_render_header(string $active = ''): void
{
    $l = os_links();
    $user = $l['user'];
    $cur = static fn (string $key): string => $key === $active ? ' aria-current="page"' : '';
    $logo = sample_url('/assets/logo-main.png');
    $menu = [
        ['home',      'Home'],
        ['certified', 'Certified Diamonds'],
        ['melees',    'Calibrated Melees'],
        ['jewellery', 'Diamond Jewellery'],
        ['about',     'About Us'],
    ];
    ?>
<header class="os-header">
  <div class="os-container os-header-inner">
    <div>
      <button type="button" class="os-icon-btn os-menu-toggle" aria-label="Open menu" aria-controls="os-drawer" aria-expanded="false">
        <img src="<?= os_h(sample_url('/assets/logo-header-drawer-mobile.svg')) ?>" alt="" width="14" height="20">
      </button>
      <nav class="os-main-nav" aria-label="Main">
        <ul>
          <?php foreach ($menu as [$key, $label]): ?>
            <li><a href="<?= os_h($l[$key]) ?>"<?= $cur($key) ?>><?= os_h($label) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </nav>
    </div>
    <a class="os-brand" href="<?= os_h($l['home']) ?>" aria-label="Orion Snow — home">
      <img src="<?= os_h($logo) ?>" alt="Orion Snow" width="464" height="88">
    </a>
    <div class="os-header-actions">
      <a class="os-header-link os-nav-contact" href="<?= os_h($l['contact']) ?>">Contact</a>
      <a class="os-header-link" href="<?= os_h($l['inventory']) ?>"<?= $cur('inventory') ?>>Inventory</a>
      <a class="os-icon-btn" href="<?= os_h($l['cart']) ?>" aria-label="Selected diamonds">
        <img src="<?= os_h(sample_url('/assets/logo-cart.svg')) ?>" alt="" width="22" height="22">
      </a>
      <?php if ($user !== null): ?>
        <span class="os-header-user">Signed in as <?= os_h((string)($user['username'] ?? '')) ?></span>
        <a class="os-header-login is-signed-in" href="<?= os_h($l['logout']) ?>">Log out</a>
      <?php else: ?>
        <a class="os-header-login" href="<?= os_h($l['login']) ?>">Login</a>
      <?php endif; ?>
    </div>
  </div>
</header>

<div class="os-drawer" id="os-drawer" aria-hidden="true">
  <div class="os-drawer-backdrop" data-os-close></div>
  <div class="os-drawer-panel" role="dialog" aria-modal="true" aria-label="Menu">
    <div class="os-drawer-head">
      <img src="<?= os_h($logo) ?>" alt="Orion Snow" width="464" height="88">
      <button type="button" class="os-icon-btn" data-os-close aria-label="Close menu">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="#0F2A4B" stroke-width="1.5" stroke-linecap="round"><path d="M4 4l12 12M16 4L4 16"/></svg>
      </button>
    </div>
    <nav aria-label="Mobile">
      <ul>
        <?php foreach ($menu as [$key, $label]): ?>
          <li><a href="<?= os_h($l[$key]) ?>"<?= $cur($key) ?>><?= os_h($label) ?></a></li>
        <?php endforeach; ?>
        <li><a href="<?= os_h($l['contact']) ?>">Contact</a></li>
        <li><a href="<?= os_h($l['inventory']) ?>"<?= $cur('inventory') ?>>Inventory</a></li>
        <?php if ($user !== null): ?>
          <li><a href="<?= os_h($l['logout']) ?>">Log out (<?= os_h((string)($user['username'] ?? '')) ?>)</a></li>
        <?php else: ?>
          <li><a href="<?= os_h($l['login']) ?>">Login</a></li>
        <?php endif; ?>
      </ul>
    </nav>
  </div>
</div>
    <?php
}

/** Footer + the shared script (drawer, year). */
function os_render_footer(): void
{
    $l = os_links();
    $a = static fn (string $file): string => os_h(sample_url('/assets/' . $file));
    ?>
<footer class="os-footer">
  <div class="os-container os-footer-main">
    <div class="os-footer-logo">
      <img src="<?= $a('logo-main.png') ?>" alt="Orion Snow" width="464" height="88" loading="lazy">
    </div>
    <div class="os-footer-col">
      <h2>Useful Links</h2>
      <ul>
        <li><a href="<?= os_h($l['certified']) ?>">Certified Diamonds</a></li>
        <li><a href="<?= os_h($l['melees']) ?>">Calibrated Melees</a></li>
        <li><a href="<?= os_h($l['jewellery']) ?>">Diamond Jewellery</a></li>
        <li><a href="<?= os_h($l['about']) ?>">About Us</a></li>
        <li><a href="<?= os_h($l['contact']) ?>">Contact</a></li>
        <li><a href="<?= os_h($l['inventory']) ?>">Inventory</a></li>
      </ul>
    </div>
    <div class="os-footer-col">
      <h2>Contact</h2>
      <div class="os-contact-block">
        <div><a class="os-contact-mail" href="mailto:info@orionsnow.com">info@orionsnow.com</a></div>
        <div><a href="tel:+32487508727">+32 487 50 87 27</a></div>
        <div>Hoveniersstraat 9, 2018 Antwerp, Belgium</div>
      </div>
    </div>
    <div class="os-footer-col">
      <h2>Orion Snow</h2>
      <p>Your Trusted Partner for<br>Lab-Grown Diamonds.</p>
      <p>Certified lab-grown diamonds, precision-calibrated melees and diamond jewellery for professional jewellers — with fast, reliable delivery and no minimum order.</p>
      <img class="os-igi" src="<?= $a('logo-igi.png') ?>" alt="IGI certified" width="140" height="58" loading="lazy">
    </div>
  </div>
  <div class="os-container">
    <div class="os-footer-bottom">
      <span>© <?= date('Y') ?> <a href="<?= os_h($l['home']) ?>">Orion Snow</a></span>
      <div class="os-socials">
        <a href="#" aria-label="Facebook"><img src="<?= $a('logo-facebook.svg') ?>" alt="" width="24" height="24"></a>
        <a href="#" aria-label="Instagram"><img src="<?= $a('logo-instagram.svg') ?>" alt="" width="24" height="24"></a>
      </div>
    </div>
  </div>
</footer>
<script src="<?= os_h(os_asset('/assets/site.js')) ?>" defer></script>
    <?php
}
