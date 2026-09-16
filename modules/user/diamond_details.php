<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/diamond_search_query.php';

require_module_access('user');

$id = (string)($_GET['id'] ?? '');
if ($id === '' && isset($_GET['aid'])) {
    // Encoded share-link format (from the Copy feature): aid is the
    // diamond's own id, base64-encoded.
    $decoded = base64_decode((string)$_GET['aid'], true);
    if ($decoded !== false && ctype_digit($decoded)) {
        $id = $decoded;
    }
}
$diamond = null;

if ($id !== '' && ctype_digit($id)) {
    $diSections = get_diamond_details_sections('DI');
    $piSections = get_diamond_details_sections('PI');
    $miSections = get_diamond_details_sections('MI');

    $validCols = get_maindata_columns();
    $wantedFields = array_unique(array_merge(
        array_column($diSections, 'field'),
        array_column($piSections, 'field'),
        array_column($miSections, 'field'),
        array_intersect(['StockNo', 'imglink', 'avail', 'Lab', 'CertificateNo'], $validCols)
    ));

    if ($wantedFields !== []) {
        $fieldList = implode(', ', array_map(fn($f) => "`$f`", $wantedFields));
        $stmt = get_db()->prepare("SELECT id, $fieldList FROM maindata WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $diamond = $stmt->fetch() ?: null;
    }
}

// Certificate PDF link, built from Lab — HRD and IGI use a path on
// this site's own domain (keyed by CertificateNo); GIA uses an
// external, fixed domain (keyed by StockNo instead). Any other lab
// simply doesn't show this section.
$certUrl = null;
if ($diamond !== null) {
    $certUrl = build_certificate_url(
        $diamond['Lab'] ?? null,
        $diamond['CertificateNo'] ?? null,
        $diamond['StockNo'] ?? null
    );
}

/**
 * Display value for one Diamond Info/Price Info/Measurement field —
 * applies the same blank-instead-of-zero formatting used on
 * Results/View Cart for CertificateNo and Measurements, falling back
 * to this page's usual "—" placeholder for any genuinely empty value.
 */
function dd_field_display_value(array $diamond, string $field): string
{
    $raw = (string)($diamond[$field] ?? '');
    if ($field === 'CertificateNo') {
        $raw = format_certificate_no_display($raw);
    } elseif ($field === 'Measurements') {
        $raw = format_measurements_display($raw);
    }
    return $raw !== '' ? $raw : '—';
}

$pageTitle = 'Diamond Details';
$pageSubtitle = '';
$activeNav = 'diamond_search';
$applyPublicTheme = true;

require_once __DIR__ . '/../../includes/header.php';
?>
    <section class="ds-page">
        <?php if ($diamond === null): ?>
            <div class="ds-hero">
                <h1 class="ds-title">Diamond Details</h1>
                <div class="ds-actions">
                    <a class="btn" href="<?= e(asset_url('/modules/user/results.php')) ?>">&larr; Back to Results</a>
                </div>
            </div>
            <div class="panel">
                <p class="panel-desc">That diamond couldn't be found — it may have been removed, or the link is invalid.</p>
            </div>
        <?php else: ?>
            <div class="ds-hero">
                <h1 class="ds-title">Diamond Details<?= !empty($diamond['StockNo']) ? ' (' . e($diamond['StockNo']) . ')' : '' ?></h1>
                <div class="ds-actions">
                    <a class="btn" href="<?= e(asset_url('/modules/user/results.php')) ?>">&larr; Back to Results</a>
                </div>
            </div>

            <div class="dd-layout">
                <?php if (!empty($diamond['StockNo'])):
                    $stockNoEnc = urlencode((string)$diamond['StockNo']);
                    $stillUrl = 'https://v3601425.v360.in/imaged/' . $stockNoEnc . '/still.jpg';
                    $videoUrl = 'https://v3601425.v360.in/vision360.html?d=' . $stockNoEnc;
                    $video2Url = 'https://onlinemediafiles.com/info-videos/' . $stockNoEnc . '.mp4';
                    $handVideoUrl = 'https://onlinemediafiles.com/hvideos/' . $stockNoEnc . '.mp4';
                ?>
                    <div class="dd-media-panel">
                        <div class="dd-media-thumbs">
                            <button type="button" class="dd-thumb-btn is-active" id="ddThumbImage" title="Image">
                                <img src="<?= e($stillUrl) ?>" alt="">
                            </button>
                            <button type="button" class="dd-thumb-btn" id="ddThumbVideo" title="360° View" data-media-url="<?= e($videoUrl) ?>">
                                <span class="dd-play-icon">&#9654;</span>
                                <span class="dd-play-label">360</span>
                            </button>
                            <button type="button" class="dd-thumb-btn" id="ddThumbVideo2" title="Video" data-media-url="<?= e($video2Url) ?>">
                                <span class="dd-play-icon">&#9654;</span>
                                <span class="dd-play-label">Video</span>
                            </button>
                            <button type="button" class="dd-thumb-btn" id="ddThumbHandVideo" title="Hand Video" data-media-url="<?= e($handVideoUrl) ?>">
                                <span class="dd-play-icon">&#9654;</span>
                                <span class="dd-play-label">Hand</span>
                            </button>
                        </div>
                        <div class="dd-media-box">
                            <img src="<?= e($stillUrl) ?>" alt="" class="dd-media-content" id="ddMediaImage">
                            <iframe class="dd-media-content" id="ddMediaFrame" hidden allowfullscreen></iframe>
                            <video class="dd-media-content" id="ddMediaVideo2" hidden controls></video>
                            <video class="dd-media-content" id="ddMediaHandVideo" hidden controls></video>
                        </div>
                    </div>
                <?php elseif (!empty($diamond['imglink'])): ?>
                    <div class="dd-image-panel">
                        <img src="<?= e($diamond['imglink']) ?>" alt="" class="dd-image">
                    </div>
                <?php endif; ?>

                <div class="dd-details-grid">
                    <div class="dd-section">
                        <div class="dd-section-header">
                            <h2>Diamond Info</h2>
                            <?php if (!empty($diamond['avail'])): ?>
                                <span class="dd-avail-badge"><span class="dd-avail-dot"></span><?= e($diamond['avail']) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($diSections === []): ?>
                            <p class="ds-empty-note">No fields configured for Diamond Info.</p>
                        <?php else: ?>
                            <dl class="dd-field-list">
                                <?php foreach ($diSections as $f): ?>
                                    <div class="dd-field-row">
                                        <dt><?= e($f['label']) ?></dt>
                                        <dd><?= e(dd_field_display_value($diamond, $f['field'])) ?></dd>
                                    </div>
                                <?php endforeach; ?>
                            </dl>
                        <?php endif; ?>
                    </div>

                    <div class="dd-side-sections">
                        <div class="dd-section">
                            <div class="dd-section-header"><h2>Price Info</h2></div>
                            <?php if ($piSections === []): ?>
                                <p class="ds-empty-note">No fields configured for Price Info.</p>
                            <?php else: ?>
                                <dl class="dd-field-list">
                                    <?php foreach ($piSections as $f): ?>
                                        <div class="dd-field-row">
                                            <dt><?= e($f['label']) ?></dt>
                                            <dd><?= e(dd_field_display_value($diamond, $f['field'])) ?></dd>
                                        </div>
                                    <?php endforeach; ?>
                                </dl>
                            <?php endif; ?>
                        </div>

                        <div class="dd-section">
                            <div class="dd-section-header"><h2>Measurement</h2></div>
                            <?php if ($miSections === []): ?>
                                <p class="ds-empty-note">No fields configured for Measurement.</p>
                            <?php else: ?>
                                <dl class="dd-field-list">
                                    <?php foreach ($miSections as $f): ?>
                                        <div class="dd-field-row">
                                            <dt><?= e($f['label']) ?></dt>
                                            <dd><?= e(dd_field_display_value($diamond, $f['field'])) ?></dd>
                                        </div>
                                    <?php endforeach; ?>
                                </dl>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($certUrl !== null): ?>
                <div class="dd-cert-panel">
                    <div class="dd-section-header"><h2>Certificate</h2></div>
                    <div class="dd-cert-frame-wrap">
                        <iframe src="<?= e($certUrl) ?>" class="dd-cert-frame" title="Diamond certificate"></iframe>
                    </div>
                    <p class="dd-cert-fallback-link"><a href="<?= e($certUrl) ?>" target="_blank" rel="noopener">Open certificate in a new tab &rarr;</a></p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </section>
    <?php if ($diamond !== null && !empty($diamond['StockNo'])): ?>
        <script src="<?= e(asset_url_versioned('/assets/js/diamond_media.js')) ?>"></script>
    <?php endif; ?>
<?php
require_once __DIR__ . '/../../includes/footer.php';
