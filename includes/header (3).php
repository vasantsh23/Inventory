<?php
declare(strict_types=1);
// $setup is already populated by header.php, which is always included first.
$setup = $setup ?? (function_exists('get_setup') ? (get_setup() ?? []) : []);
// Optional: a page can set $footerExtraRight (pre-escaped HTML) before
// requiring this file to add a right-aligned block inside the footer
// — currently only Diamond Search uses this, for its "data last
// updated" info (see includes/functions.php's get_latest_upload_log()).
// Every other page renders exactly as before, unchanged.
$footerExtraRight = $footerExtraRight ?? '';
?>
</main>
<?php require __DIR__ . '/partials/footer_bar.php'; ?>
</body>
</html>
