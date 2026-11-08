<?php
/**
 * Admin → Data → Media Link Check
 * For one stock number (or certificate number): shows the exact
 * certificate / video / 360 / hand-video links Results would use, asks
 * each server right now whether the file can be opened (HTTP status and
 * content type), and explains why an icon is hidden. Re-checking also
 * refreshes the cached verdict, so a fixed link shows on Results at once.
 * "Clear media check cache" forgets every cached verdict.
 */
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/theme_ui.php';
require_once __DIR__ . '/../../includes/diamond_search_query.php';
require_once __DIR__ . '/../../includes/media_check.php';

require_module_access('admin');

$self = 'media_link_check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'clear_cache') {
    theme_csrf_check();
    $n = 0;
    $dir = media_check_cache_dir();
    if ($dir !== null) {
        foreach (glob($dir . '/*.txt') ?: [] as $f) {
            if (@unlink($f)) {
                $n++;
            }
        }
    }
    theme_flash('success', "Media check cache cleared ($n saved results removed). Results will re-check every link on the next page view.");
    theme_redirect($self);
}

$query = trim((string)($_GET['stock'] ?? ''));
$row = null;
$checks = [];
if ($query !== '') {
    $st = get_db()->prepare('SELECT * FROM maindata WHERE StockNo = :q OR CertificateNo = :q2 ORDER BY id LIMIT 1');
    $st->execute([':q' => $query, ':q2' => $query]);
    $row = $st->fetch() ?: null;
}

/** Live check of one URL, mirroring media_urls_reachable(): HEAD, then a 1-byte GET if HEAD is refused. */
function mlc_probe(string $url): array
{
    if (!function_exists('curl_multi_init')) {
        return ['ok' => true, 'code' => null, 'type' => null, 'method' => '—', 'why' => 'curl is not available on this server, so links are not checked (any valid link shows an icon).'];
    }
    $r = media_check_round([$url => true], false)[$url] ?? ['code' => 0, 'type' => null];
    $method = 'HEAD';
    if (in_array($r['code'], [400, 401, 403, 405, 406, 416, 501], true)) {
        $r = media_check_round([$url => true], true)[$url] ?? ['code' => 0, 'type' => null];
        $method = 'GET (HEAD refused)';
    }
    $typeOk = media_check_type_ok($url, $r['type']);
    $ok = $r['code'] >= 200 && $r['code'] < 300 && $typeOk;
    if ($ok) {
        $why = 'Opens correctly — the icon shows.';
    } elseif ($r['code'] === 0) {
        $why = 'No answer from the server (wrong domain, server down, SSL certificate problem, or this server cannot reach it).';
    } elseif ($r['code'] === 404) {
        $why = 'File not found at this address — check the folder and the file name (upper/lower case matters on most servers).';
    } elseif ($r['code'] === 403 || $r['code'] === 406) {
        $why = 'The server refused the request (permissions or a firewall such as ModSecurity blocking automated checks).';
    } elseif ($r['code'] >= 200 && $r['code'] < 300 && !$typeOk) {
        $why = 'The address answers, but with "' . $r['type'] . '" instead of the expected file type — usually an HTML "not found" page.';
    } else {
        $why = 'Unexpected HTTP status ' . $r['code'] . '.';
    }
    return ['ok' => $ok, 'code' => $r['code'], 'type' => $r['type'], 'method' => $method, 'why' => $why];
}

if ($row !== null) {
    $links = build_result_media_links($row['StockNo'] ?? null, $row['Lab'] ?? null, $row['CertificateNo'] ?? null, $row);
    $names = ['image' => 'Image', 'cert' => 'Certificate (icon + Cert no. link)', 'infovideo' => 'Video', 'video' => '360', 'handvideo' => 'Hand video'];
    $sources = [
        'image' => media_path_override('image') !== null ? 'File Paths row "image"' : 'Built-in address (no "image" row)',
        'cert' => media_path_override('cert') !== null ? 'File Paths row "cert"' : 'Built-in rule by Lab (no "cert" row in File Paths)',
        'infovideo' => media_path_override('video') !== null ? 'File Paths row "video"' : 'Built-in address (no "video" row)',
        'video' => media_path_override('360view') !== null ? 'File Paths row "360view"' : 'Built-in address (no "360view" row)',
        'handvideo' => media_path_override('handvideo') !== null ? 'File Paths row "handvideo"' : 'Built-in address (no "handvideo" row)',
    ];
    foreach ($names as $key => $label) {
        $url = $links[$key] ?? null;
        $item = ['label' => $label, 'source' => $sources[$key], 'url' => $url, 'cached' => null, 'live' => null];
        if ($url !== null) {
            $item['cached'] = media_check_cache_get($url);
            $item['live'] = mlc_probe($url);
            media_check_cache_put($url, $item['live']['ok']); // Results uses the fresh verdict straight away
        }
        $checks[] = $item;
    }
}

$pageTitle = 'Media Link Check';
$pageSubtitle = 'See exactly which certificate and video links a stone gets on Results, and why an icon is missing.';
$activeNav = 'media_link_check';
require_once __DIR__ . '/../../includes/admin_header.php';
?>
<style>
    .mlc-table { width: 100%; border-collapse: collapse; }
    .mlc-table th, .mlc-table td { border: 1px solid rgba(128,128,128,.35); padding: 8px 10px; text-align: left; vertical-align: top; }
    .mlc-url { word-break: break-all; font-family: Consolas, monospace; font-size: 13px; }
    .mlc-ok { color: #1e8e3e; font-weight: 700; }
    .mlc-bad { color: #d93025; font-weight: 700; }
    .mlc-form { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
</style>

<?= theme_render_flashes() ?>

<section class="panel">
    <form method="get" action="<?= e($self) ?>" class="mlc-form">
        <label for="mlcStock"><strong>Stock number or certificate number</strong></label>
        <input type="text" id="mlcStock" name="stock" value="<?= e($query) ?>" placeholder="e.g. CS12931 or 250000258049" required>
        <button type="submit" class="btn btn-accent">Check links</button>
    </form>
</section>

<?php if ($query !== '' && $row === null): ?>
    <div class="panel"><p class="panel-desc">No stone found with stock number or certificate number “<?= e($query) ?>”.</p></div>
<?php elseif ($row !== null): ?>
    <section class="panel">
        <h2 class="panel-title">Stock <?= e((string)$row['StockNo']) ?> — Lab <?= e((string)($row['Lab'] ?? '')) ?>, Certificate no. <?= e((string)($row['CertificateNo'] ?? '')) ?></h2>
        <div style="overflow-x:auto;">
            <table class="mlc-table">
                <thead>
                    <tr><th>Item</th><th>Link built from</th><th>Address checked</th><th>Server answer</th><th>Result</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($checks as $c): ?>
                        <tr>
                            <td><?= e($c['label']) ?></td>
                            <td><?= e($c['source']) ?></td>
                            <td class="mlc-url">
                                <?php if ($c['url'] === null): ?>
                                    — no link —
                                <?php else: ?>
                                    <a href="<?= e($c['url']) ?>" target="_blank" rel="noopener"><?= e($c['url']) ?></a>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($c['live'] !== null): ?>
                                    HTTP <?= e((string)($c['live']['code'] ?? '—')) ?> via <?= e($c['live']['method']) ?><br>
                                    Type: <?= e((string)($c['live']['type'] ?? '—')) ?>
                                    <?php if ($c['cached'] !== null): ?><br><small>Previously saved result: <?= $c['cached'] ? 'OK' : 'failed' ?></small><?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($c['url'] === null): ?>
                                    <span class="mlc-bad">No icon</span><br>
                                    <?php if (str_starts_with($c['source'], 'Built-in rule')): ?>
                                        The built-in rule only links HRD and IGI (by certificate no.) and GIA. Add a "cert" row in File Paths.
                                    <?php else: ?>
                                        The File Paths row is inactive or its path is blank, or the stone has no stock number.
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="<?= $c['live']['ok'] ? 'mlc-ok' : 'mlc-bad' ?>"><?= $c['live']['ok'] ? 'Icon shows' : 'Icon hidden' ?></span><br>
                                    <?= e($c['live']['why']) ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="panel-desc" style="margin-top:10px;">This check also updated the saved result for these links, so Results reflects it straight away.</p>
    </section>
<?php endif; ?>

<section class="panel">
    <h2 class="panel-title">Saved check results</h2>
    <p class="panel-desc">Results remembers each link’s check — working links for 12 hours, failed links for 30 minutes — so a file uploaded just now can stay hidden for up to 30 minutes. Clear the saved results to re-check everything immediately.</p>
    <form method="post" action="<?= e($self) ?>">
        <?= theme_csrf_field() ?>
        <input type="hidden" name="action" value="clear_cache">
        <button type="submit" class="btn">Clear media check cache</button>
    </form>
</section>
<?php
require_once __DIR__ . '/../../includes/admin_footer.php';
