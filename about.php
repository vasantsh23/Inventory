<?php
declare(strict_types=1);
$applyPublicTheme = true; // this page's content should reflect the Fonts & Colors selection
require_once __DIR__ . '/includes/header.php';
?>
    <section class="page-content">
        <h1>About Us</h1>
        <p>
            <?= e($setup['Page Desc'] ?? ($setup['company'] ?? APP_NAME) . ' is committed to reliable, secure inventory management.') ?>
        </p>
    </section>
<?php
require_once __DIR__ . '/includes/footer.php';
