<?php
declare(strict_types=1);
$applyPublicTheme = true; // this page's content should reflect the Fonts & Colors selection
require_once __DIR__ . '/includes/header.php';
?>
    <section class="hero">
        <h1>Welcome to <?= e($setup['company'] ?? APP_NAME) ?></h1>
        <p>Manage your stock, orders and suppliers from one secure place.</p>
        <a class="cta-button" href="<?= e(asset_url('/inventory.php')) ?>">Go to Inventory</a>
    </section>

    <section class="home-highlights">
        <div class="card">
            <h2>Home</h2>
            <p>Company overview and quick links.</p>
        </div>
        <div class="card">
            <h2>About Us</h2>
            <p>Learn more about <?= e($setup['company'] ?? 'us') ?>.</p>
        </div>
        <div class="card">
            <h2>Inventory</h2>
            <p>Sign in to manage stock and view reports.</p>
        </div>
        <div class="card">
            <h2>Contact Us</h2>
            <p>Reach out with any questions.</p>
        </div>
    </section>

    <?php $socialLinks = get_social_media_links($setup); ?>
    <?php if ($socialLinks !== []): ?>
        <section class="social-section">
            <h2 class="section-subheading">Follow Us</h2>
            <div class="social-icons">
                <?php foreach ($socialLinks as $link):
                    $imageSrc = preg_match('#^https?://#i', $link['image']) ? $link['image'] : asset_url($link['image']);
                    $href = $link['url'] !== '' ? $link['url'] : '#';
                ?>
                    <a class="social-icon" href="<?= e($href) ?>" title="<?= e($link['title']) ?>"
                       target="_blank" rel="noopener noreferrer">
                        <img src="<?= e($imageSrc) ?>" alt="<?= e($link['title']) ?>">
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if (strtolower(trim((string)($setup['loginscrn'] ?? 'yes'))) === 'no'): ?>
        <p class="home-admin-login-link"><a href="<?= e(asset_url('/login.php')) ?>">Admin Login</a></p>
    <?php endif; ?>
<?php
require_once __DIR__ . '/includes/footer.php';
