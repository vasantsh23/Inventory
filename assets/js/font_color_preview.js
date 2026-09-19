/**
 * font_color_preview.js
 * Powers the Admin > Font & Color Preview sandbox. Purely client-side
 * — nothing here ever submits a form or writes to the server.
 */
(function () {
    // Typing in a Font-N box live-previews that text in the slot's font/size.
    document.querySelectorAll('.fc-font-input').forEach(function (input) {
        var targetId = input.getAttribute('data-preview-target');
        var target = document.getElementById(targetId);
        if (!target) {
            return;
        }
        input.addEventListener('input', function () {
            target.textContent = input.value !== '' ? input.value : 'Sample text preview';
        });
    });

    // Clicking a forecolor/backcolor swatch updates the page mockup below.
    var activeButtons = { fg: null, bg: null };
    var currentHex = { fg: null, bg: null };

    // ---- WCAG contrast ratio check -----------------------------------
    // Same formula the WCAG 2.x spec uses: relative luminance of each
    // color, then (lighter + 0.05) / (darker + 0.05). Understands
    // #rgb / #rrggbb and falls back gracefully (returns null) for
    // anything else (a CSS name like "skyblue", "transparent", etc.)
    // — the check simply doesn't run rather than showing a wrong number.
    function hexToRgb(hex) {
        if (!hex) { return null; }
        var m = String(hex).trim().replace(/^#/, '');
        if (m.length === 3) {
            m = m.split('').map(function (c) { return c + c; }).join('');
        }
        if (!/^[0-9a-fA-F]{6}$/.test(m)) {
            return null;
        }
        return {
            r: parseInt(m.substr(0, 2), 16),
            g: parseInt(m.substr(2, 2), 16),
            b: parseInt(m.substr(4, 2), 16)
        };
    }

    function relativeLuminance(rgb) {
        var channels = [rgb.r, rgb.g, rgb.b].map(function (c) {
            var s = c / 255;
            return s <= 0.03928 ? s / 12.92 : Math.pow((s + 0.055) / 1.055, 2.4);
        });
        return 0.2126 * channels[0] + 0.7152 * channels[1] + 0.0722 * channels[2];
    }

    function contrastRatio(hexA, hexB) {
        var a = hexToRgb(hexA);
        var b = hexToRgb(hexB);
        if (!a || !b) {
            return null;
        }
        var la = relativeLuminance(a);
        var lb = relativeLuminance(b);
        var lighter = Math.max(la, lb);
        var darker = Math.min(la, lb);
        return (lighter + 0.05) / (darker + 0.05);
    }

    function updateContrastCheck() {
        var sample = document.getElementById('fc-page-mockup-content');
        var result = document.getElementById('fc-contrast-result');
        if (!sample || !result) {
            return;
        }

        // The mockup's link and "Add to Cart" button both use
        // color: inherit / border: currentColor in CSS, so setting
        // just these two properties on the content wrapper updates
        // the heading, paragraph, link AND button in one go — the
        // same way --content-fg does double duty as text color and
        // button border color on the real site (see the write-up).
        if (currentHex.fg) { sample.style.color = currentHex.fg; }
        if (currentHex.bg) { sample.style.backgroundColor = currentHex.bg; }

        if (!currentHex.fg || !currentHex.bg) {
            result.innerHTML = '<p class="hint" style="margin:10px 0 0;">Pick a Forecolor and a Backcolor above to see the contrast ratio here.</p>';
            return;
        }

        var ratio = contrastRatio(currentHex.fg, currentHex.bg);
        if (ratio === null) {
            result.innerHTML = '<p class="hint" style="margin:10px 0 0;">Contrast ratio can\'t be calculated for one of these values (only hex colors like <code>#2be0a0</code> are supported) — check readability by eye in the sample above.</p>';
            return;
        }

        var normalAA = ratio >= 4.5;
        var largeAA = ratio >= 3;
        var normalAAA = ratio >= 7;
        var verdictClass = normalAA ? 'alert-success' : (largeAA ? 'alert-warning' : 'alert-error');
        var verdictText = normalAA
            ? 'Passes WCAG AA for normal text — safe to use.'
            : (largeAA
                ? 'Only passes WCAG AA for large/bold text (18px+) — regular body text will be hard to read.'
                : 'Fails WCAG AA — this text will likely be difficult or impossible to read. Pick a lighter/darker pairing.');

        result.innerHTML =
            '<div class="alert ' + verdictClass + '" style="margin:10px 0 0;">' +
            '<strong>Contrast ratio: ' + ratio.toFixed(2) + ':1</strong> — ' + verdictText +
            ' (AA normal text needs 4.5:1, AA large text needs 3:1, AAA normal text needs 7:1.)' +
            '</div>';
    }

    document.querySelectorAll('.fc-swatch-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var hex = btn.getAttribute('data-hex');
            var target = btn.getAttribute('data-target');
            if (!hex) {
                return;
            }
            currentHex[target] = hex;
            updateContrastCheck();

            if (activeButtons[target]) {
                activeButtons[target].classList.remove('fc-swatch-active');
            }
            btn.classList.add('fc-swatch-active');
            activeButtons[target] = btn;
        });
    });
})();
