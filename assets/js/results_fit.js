/*
 * Results / View Cart — show every column inside the window, with no
 * sideways scrolling. Steps, each tried only if the previous one still
 * leaves the table too wide:
 *   1. shrink the font, down to 10px;
 *   2. let headers and long cells wrap onto two lines (media icons 2 × 2),
 *      and shrink the font further, down to 9px;
 *   3. keep rows on one line and narrow only the widest columns (Remark,
 *      Measurement, …) until the table fits; their text is cut with "…"
 *      and shown in full on hover. Short values are never cut.
 * Phones (≤ 720px) use the stacked card layout instead.
 */
(function () {
    'use strict';
    var wrap = document.querySelector('.results-table-wrap');
    var table = wrap && wrap.querySelector('table.results-table');
    if (!table) { return; }

    var FIRST_MIN = 10;
    var FINAL_MIN = 9;
    var STEP = 0.5;

    function tooWide() {
        return table.scrollWidth > wrap.clientWidth + 1;
    }

    function shrinkTo(min) {
        var size = parseFloat(getComputedStyle(table).fontSize) || 12;
        while (tooWide() && size > min) {
            size = Math.max(min, size - STEP);
            table.style.fontSize = size + 'px';
        }
    }

    function headCells() {
        var row = table.tHead && table.tHead.rows[0];
        return row ? Array.prototype.slice.call(row.cells) : [];
    }

    function reset() {
        table.classList.remove('results-table--wrap', 'results-table--headwrap', 'results-table--measure', 'results-table--fixed');
        table.style.fontSize = '';
        headCells().forEach(function (th) { th.style.width = ''; });
        Array.prototype.forEach.call(table.querySelectorAll('td[data-fit-title]'), function (td) {
            td.removeAttribute('title');
            td.removeAttribute('data-fit-title');
        });
    }

    // Last resort: keep every row on one line, give each column its
    // natural width, then cap only the WIDEST columns (Remark,
    // Measurement, …) at the same limit until the table fits — so short
    // values such as Size or Price are never cut. A capped cell ends
    // in "…" and shows its full text on hover.
    function fixColumns() {
        var ths = headCells();
        if (!ths.length) { return; }
        table.classList.remove('results-table--wrap');
        table.classList.add('results-table--headwrap', 'results-table--measure');
        // Width each column's DATA needs (headers can't widen it while
        // --measure is on), then allow a header word up to ~3.5em.
        var ctx = document.createElement('canvas').getContext('2d');
        var natural = ths.map(function (th) {
            var data = Math.ceil(th.getBoundingClientRect().width);
            var cs = getComputedStyle(th);
            ctx.font = cs.fontWeight + ' ' + cs.fontSize + ' ' + cs.fontFamily;
            var word = 0;
            (th.textContent || '').split(/\s+/).forEach(function (w) { word = Math.max(word, ctx.measureText(w).width); });
            var pad = parseFloat(cs.paddingLeft) + parseFloat(cs.paddingRight);
            var head = Math.ceil(Math.min(word, 3.5 * parseFloat(cs.fontSize)) + pad);
            return Math.max(data, head);
        });
        table.classList.remove('results-table--measure');
        var avail = wrap.clientWidth - 2;
        var sorted = natural.slice().sort(function (a, b) { return a - b; });
        var cap = sorted[sorted.length - 1];
        var below = 0;                               // total of columns narrower than the cap
        for (var i = 0; i < sorted.length; i++) {
            var left = sorted.length - i;            // columns that would be capped
            var c = Math.floor((avail - below) / left);
            if (c <= sorted[i]) { cap = Math.max(c, 16); break; }
            below += sorted[i];
        }
        ths.forEach(function (th, i) { th.style.width = Math.min(natural[i], cap) + 'px'; });
        table.classList.add('results-table--fixed');
        Array.prototype.forEach.call(table.tBodies[0] ? table.tBodies[0].rows : [], function (tr) {
            Array.prototype.forEach.call(tr.cells, function (td) {
                if (td.scrollWidth > td.clientWidth + 1 && !td.hasAttribute('title')) {
                    var text = (td.textContent || '').trim();
                    if (text) {
                        td.setAttribute('title', text);
                        td.setAttribute('data-fit-title', '1');
                    }
                }
            });
        });
    }

    function fit() {
        reset();
        wrap.style.overflowX = 'hidden';
        if (window.innerWidth <= 720) {              // stacked card layout on phones
            wrap.style.overflowX = '';
            return;
        }
        shrinkTo(FIRST_MIN);
        if (!tooWide()) { return; }
        table.classList.add('results-table--wrap');
        shrinkTo(FINAL_MIN);
        if (!tooWide()) { return; }
        fixColumns();
    }

    var timer = null;
    function schedule() {
        clearTimeout(timer);
        timer = setTimeout(fit, 120);
    }
    window.addEventListener('resize', schedule);
    window.addEventListener('load', fit);           // after flag images / fonts
    if (document.fonts && document.fonts.ready) { document.fonts.ready.then(fit); }
    fit();
})();
