<?php
/**
 * deploy_check.php — confirms the theme-settings update fully reached this server.
 * Compares each changed file with the version it shipped with, checks the
 * one line that must be edited by hand in config/config.php (git-ignored),
 * and checks that sql/migration_theme_settings.sql has been run.
 * Admin only. Safe to delete once everything shows OK.
 */
declare(strict_types=1);

/*
 * Works even when a half-finished deploy has broken the rest of the site
 * (so you may not be able to log in): open it either as a logged-in admin,
 * or with ?key=DEPLOY_CHECK_KEY. It only reports file/database status.
 * Delete this file once everything shows OK.
 */
const DEPLOY_CHECK_KEY = 'e55848dcaae316f011df9f6e';

$allowed = hash_equals(DEPLOY_CHECK_KEY, (string) ($_GET['key'] ?? ''));
if (!$allowed) {
    try {
        require_once __DIR__ . '/../../includes/auth.php';
        $u = current_user();
        $allowed = $u !== null && (int) ($u['level'] ?? 0) >= 8;
    } catch (Throwable $e) {
        $allowed = false; // auth itself broken by the partial deploy
    }
}
if (!$allowed) {
    http_response_code(403);
    exit('Log in as an admin first, or open this page with ?key=<your deploy-check key>.');
}
require_once __DIR__ . '/../../config/db.php';

const EXPECTED = [
    'assets/js/theme_admin.js' => '20e123b75772795cd2c79dd5afd9179b',
    'includes/ThemeSettings.php' => '74b6d859fc6a76ddcb11f287b238fc3d',
    'includes/fonts.php' => '6c4b07ddefa503f33971c410121457e7',
    'includes/theme.php' => 'af1899c6c189cfb482f8b26f5c0255d7',
    'includes/theme_ui.php' => 'c2347d1dc96e5c4e9ef08d67c81ee6f7',
    'modules/admin/theme_actions.php' => 'f817d4c24f26847226a7ff460dca91c3',
    'modules/admin/theme_sections.php' => '40cbfbf2658ec2481cdd3bf6b75af4f6',
    'modules/admin/theme_setting_form.php' => '00c0c0813b52c4d728665a447e2cc14f',
    'modules/admin/theme_settings.php' => '26bd3d06f9a69b26afe0510fa66f1dc2',
    'sql/migration_theme_settings.sql' => '547e44a40e42ba985ba015c3969e5d51',
    'theme.css.php' => '9faa157256ca7fb19babe4987933e022',
    'about.php' => '4ff1a0158e04c10f18790eafa6062ed0',
    'assets/css/style.css' => '9b8fce69e53d8862a6ceb88ac03d60ee',
    'contact.php' => '5573d24493da915ce665e8d84f674e4d',
    'includes/admin_header.php' => '05819ec204134503a26c660e0a51dde1',
    'includes/crud_config.php' => 'd258d8480d736cdfb1952fe7d8aa65d4',
    'includes/diamond_search_query.php' => '9f72aa44cc6aa5bdcfa47454fec9dbf2',
    'includes/functions.php' => 'c3bc5eb88360b93bedb8da997bf25d39',
    'includes/header.php' => '86b1d6627f2ecb0cde791bd4dfece440',
    'index.php' => '07835b16c40d9d1d2cd36ac6d419cd15',
    'modules/admin/backup.php' => 'eb8429b7a8caeeb35ef380b706a98e1a',
    'modules/admin/diamond_data_upload.php' => '4c223f67436aa5b05d8e161a87d040f7',
    'modules/admin/maindata_hold.php' => '1df86d744070040291b9a60ada664368',
    'modules/admin/table_form.php' => 'c819ef2abe4209f1446063ebf675302b',
    'modules/admin/table_import.php' => '40af5aaafc8f585aa5f254b38f2b9f85',
    'modules/admin/table_view.php' => '12a6f28c8436a3fac93a4bc8d903c454',
    'modules/superadmin/index.php' => 'c6291cafff5cfc670170da43a679748f',
    'modules/user/customer_form.php' => '1221c146b30a77c07a1a00f918ed48ca',
    'modules/user/customer_list.php' => '17f5d4c9fabb88bf9caaa626aa26fd26',
    'modules/user/diamond_details.php' => 'ab760040460fe574182dbcee0053bba4',
    'modules/user/diamond_search.php' => '4ecf45fca55f5080494304ee3c7ed21f',
    'modules/user/memo_print.php' => 'e2c30f1019d1a3c3881535d58edbf072',
    'modules/user/results.php' => 'c61ca307c668b0988a9ca6f368ee06cf',
    'modules/user/view_results.php' => 'd6c91fccf2a546e42d6986f7ed499116',
    'sql/schema.sql' => 'bdb6bc5496ddc888a9b02cb64fd558c2',
];
const SHOULD_BE_GONE = [
    'assets/js/font_color_preview.js',
    'assets/js/font_combo.js',
    'modules/admin/font_color_preview.php',
];

$root = dirname(__DIR__, 2);
$rows = [];
foreach (EXPECTED as $file => $md5) {
    $path = $root . '/' . $file;
    if (!is_file($path)) {
        $rows[] = [$file, 'MISSING', 'Not on the server — commit/push this file (it may be untracked in git).'];
        continue;
    }
    // Normalise Windows line endings so a CRLF checkout still counts as current
    $body = str_replace("\r\n", "\n", (string) file_get_contents($path));
    $ok = md5($body) === $md5 || md5_file($path) === $md5;
    $rows[] = [$file, $ok ? 'OK' : 'OLD', $ok ? '' : 'Server has a different version — this file was not updated.'];
}

$cfg = (string) @file_get_contents($root . '/config/config.php');
$cspOk = str_contains($cfg, 'https://fonts.googleapis.com') && str_contains($cfg, 'font-src');

$leftover = array_values(array_filter(SHOULD_BE_GONE, fn($f) => is_file($root . '/' . $f)));

$db = ['theme' => null, 'old' => null, 'rsetup' => null];
try {
    $db['theme']  = (int) get_db()->query('SELECT COUNT(*) FROM theme_settings')->fetchColumn();
} catch (Throwable $e) { $db['theme'] = -1; }
try {
    $db['old']    = (bool) get_db()->query("SHOW TABLES LIKE 'font_and_color'")->fetch();
    $db['rsetup'] = (bool) get_db()->query("SHOW COLUMNS FROM rsetup LIKE 'fontype'")->fetch();
} catch (Throwable $e) { /* reported as unknown below */ }

$bad = count(array_filter($rows, fn($r) => $r[1] !== 'OK'));
header('Content-Type: text/html; charset=utf-8');
$h = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
?><!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><title>Deploy check</title>
<style>
body{font:14px/1.5 system-ui,Arial,sans-serif;margin:24px;color:#111;background:#fff}
table{border-collapse:collapse;width:100%;max-width:1100px}td,th{border:1px solid #ccc;padding:6px 10px;text-align:left;vertical-align:top}
.OK{color:#0a7a3b;font-weight:700}.OLD,.MISSING,.FAIL{color:#b00020;font-weight:700}.WARN{color:#a15c00;font-weight:700}
code{background:#f3f3f3;padding:1px 4px}h2{margin-top:28px}
</style></head><body>
<h1>Theme settings update — deploy check</h1>
<p><strong><?= $bad === 0 ? 'All ' . count($rows) . ' files are up to date.' : $bad . ' of ' . count($rows) . ' files are not up to date on this server.' ?></strong></p>

<h2>1. Database migration</h2>
<table>
<tr><td>theme_settings table</td><td class="<?= $db['theme'] > 0 ? 'OK' : 'FAIL' ?>"><?= $db['theme'] > 0 ? 'OK — ' . $db['theme'] . ' settings' : 'FAIL' ?></td>
<td><?= $db['theme'] > 0 ? '' : 'Run <code>sql/migration_theme_settings.sql</code> in phpMyAdmin (back up first). Without it every page loses its colours.' ?></td></tr>
<tr><td>old font_and_color table</td><td class="<?= $db['old'] ? 'WARN' : 'OK' ?>"><?= $db['old'] ? 'still present' : 'OK — removed' ?></td><td><?= $db['old'] ? 'The migration has not been run yet.' : '' ?></td></tr>
<tr><td>old rsetup.fontype column</td><td class="<?= $db['rsetup'] ? 'WARN' : 'OK' ?>"><?= $db['rsetup'] ? 'still present' : 'OK — removed' ?></td><td></td></tr>
</table>

<h2>2. config/config.php (git-ignored — edit on the server by hand)</h2>
<table><tr><td>Google Fonts allowed in Content-Security-Policy</td>
<td class="<?= $cspOk ? 'OK' : 'FAIL' ?>"><?= $cspOk ? 'OK' : 'NOT UPDATED' ?></td>
<td><?= $cspOk ? '' : 'In <code>config/config.php</code>, in the <code>Content-Security-Policy</code> line, change <code>style-src \'self\' \'unsafe-inline\';</code> to <code>style-src \'self\' \'unsafe-inline\' https://fonts.googleapis.com; font-src \'self\' https://fonts.gstatic.com;</code>. The site works without it, but fonts chosen in Theme Settings will not download.' ?></td></tr></table>

<h2>3. Files</h2>
<table><tr><th>File</th><th>Status</th><th>What to do</th></tr>
<?php foreach ($rows as [$f, $s, $note]): ?>
<tr><td><code><?= $h($f) ?></code></td><td class="<?= $s ?>"><?= $s ?></td><td><?= $h($note) ?></td></tr>
<?php endforeach; ?>
</table>

<h2>4. Old files (optional clean-up)</h2>
<?php if ($leftover): ?>
<p class="WARN">These were removed in the update but are still on the server (cPanel's <code>cp -R</code> never deletes). They are no longer linked anywhere; delete them in File Manager when convenient:</p>
<ul><?php foreach ($leftover as $f): ?><li><code><?= $h($f) ?></code></li><?php endforeach; ?></ul>
<?php else: ?><p class="OK">None left.</p><?php endif; ?>
</body></html>
