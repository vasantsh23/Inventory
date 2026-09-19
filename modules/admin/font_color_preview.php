<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

require_module_access('admin');

// Read-only: this page never writes anything. Pull the current
// font/color slot values straight from the table for display.
$row = get_db()->query('SELECT * FROM font_and_color ORDER BY id ASC LIMIT 1')->fetch() ?: [];

function fc_val(array $row, string $key): string
{
    return trim((string)($row[$key] ?? ''));
}

$pageTitle = 'Font & Color Preview';
$pageSubtitle = 'A live sandbox for the 5 font slots and 10 color slots — nothing here is saved.';
$activeNav = 'font_color_preview';

require_once __DIR__ . '/../../includes/admin_header.php';
?>
    <div class="alert alert-warning" style="margin-bottom:20px;">
        Preview only — anything you type or click on this page is never saved.
    </div>

    <div class="fc-preview-grid">
        <div class="panel">
            <h2>Font Slots</h2>
            <p class="panel-desc">Type sample text into a box to preview it using that slot's current font and size.</p>

            <?php for ($n = 1; $n <= 5; $n++):
                $fontType = fc_val($row, "font type-$n");
                $fontSize = fc_val($row, "font-size-$n");
                $cssFontSize = $fontSize !== '' ? normalize_css_length($fontSize) : '16px';
                $cssFontFamily = $fontType !== '' ? "'" . str_replace("'", "", $fontType) . "', var(--font)" : 'var(--font)';
                $isTinySize = preg_match('/^\d+(\.\d+)?$/', $fontSize) && (float)$fontSize < 12;
            ?>
                <div class="fc-font-group">
                    <h3>Font-<?= $n ?></h3>
                    <div class="fc-font-meta">
                        <span>Font type: <strong><?= e($fontType !== '' ? $fontType : 'not set') ?></strong></span>
                        <span>Size: <strong><?= e($fontSize !== '' ? $fontSize : 'not set') ?></strong></span>
                    </div>
                    <input type="text" class="fc-font-input" placeholder="Type sample text…"
                           style="font-family: <?= e($cssFontFamily) ?>; font-size: max(<?= e($cssFontSize) ?>, 12px);"
                           data-preview-target="fc-preview-<?= $n ?>">
                    <?php if ($isTinySize): ?>
                        <p class="hint" style="margin:-6px 0 10px;">Typing box shown at a minimum readable size — the preview below shows the true configured size (<?= e($cssFontSize) ?>).</p>
                    <?php endif; ?>
                    <div class="fc-font-preview" id="fc-preview-<?= $n ?>"
                         style="font-family: <?= e($cssFontFamily) ?>; font-size: <?= e($cssFontSize) ?>;">
                        Sample text preview
                    </div>
                </div>
            <?php endfor; ?>
        </div>

        <div class="panel">
            <h2>Colors</h2>
            <p class="panel-desc">Click a swatch to apply it to the preview image's outline or background.</p>

            <div class="fc-color-list">
                <?php for ($n = 1; $n <= 5; $n++):
                    $fore = fc_val($row, "forecolor-$n");
                    $back = fc_val($row, "backcolor-$n");
                ?>
                    <div class="fc-color-row">
                        <span class="fc-color-label">Forecolor-<?= $n ?></span>
                        <button type="button" class="fc-swatch-btn" data-target="fg" data-hex="<?= e($fore) ?>"
                                <?= $fore === '' ? 'disabled' : '' ?>>
                            <span class="fc-swatch" style="background: <?= e($fore !== '' ? $fore : 'transparent') ?>;"></span>
                            <span class="fc-hex"><?= e($fore !== '' ? $fore : 'not set') ?></span>
                        </button>
                    </div>
                    <div class="fc-color-row">
                        <span class="fc-color-label">Backcolor-<?= $n ?></span>
                        <button type="button" class="fc-swatch-btn" data-target="bg" data-hex="<?= e($back) ?>"
                                <?= $back === '' ? 'disabled' : '' ?>>
                            <span class="fc-swatch" style="background: <?= e($back !== '' ? $back : 'transparent') ?>;"></span>
                            <span class="fc-hex"><?= e($back !== '' ? $back : 'not set') ?></span>
                        </button>
                    </div>
                <?php endfor; ?>
            </div>
        </div>

        <div class="panel fc-image-panel fc-page-panel">
            <h2>Page Preview</h2>
            <p class="panel-desc">
                Click a Forecolor and a Backcolor at left to see how they will actually look together on a
                page. The top bar below is deliberately <strong>not</strong> affected by your picks &mdash;
                on the live site the header/nav/footer always keep their fixed default look; only the
                content area underneath it (like this one) follows the active theme.
            </p>

            <div class="fc-page-mockup" id="fc-page-mockup">
                <div class="fc-page-mockup-chrome" aria-hidden="true">
                    <span class="fc-page-mockup-logo"></span>
                    <span class="fc-page-mockup-navline"></span>
                    <span class="fc-page-mockup-navline"></span>
                    <span class="fc-page-mockup-navline" style="margin-left:auto;"></span>
                </div>
                <div class="fc-page-mockup-content" id="fc-page-mockup-content">
                    <h3>Diamond Search Results</h3>
                    <p>5.01 ct Round &middot; VS2 &middot; G Color &middot; IGI &mdash; this paragraph is
                        ordinary page text, styled with the Forecolor and Backcolor you pick at left, exactly
                        the way Results, View Cart and Diamond Details render their text.</p>
                    <a href="#fc-page-mockup-content">View details &rarr;</a>
                    <button type="button" class="fc-page-mockup-btn" id="fc-page-mockup-btn">Add to Cart</button>
                </div>
                <p class="fc-page-mockup-caption">Top bar = fixed site chrome (never themed). Content area below = follows your Forecolor/Backcolor pick.</p>
            </div>

            <div class="fc-contrast-result" id="fc-contrast-result">
                <p class="hint" style="margin:10px 0 0;">Pick a Forecolor and a Backcolor above to see the contrast ratio here.</p>
            </div>
        </div>
    </div>

    <script src="<?= e(asset_url_versioned('/assets/js/font_color_preview.js')) ?>"></script>
<?php
require_once __DIR__ . '/../../includes/admin_footer.php';
