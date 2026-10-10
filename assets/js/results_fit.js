/*
 * Results / View Cart — make the table fit the window width.
 * CSS already tightens padding; if the columns still don't fit
 * (many columns on a small screen), step the font size down until
 * they do, to a readable minimum of 9px.
 */
(function () {
    'use strict';
    var wrap = document.querySelector('.results-table-wrap');
    var table = wrap && wrap.querySelector('table.results-table');
    if (!table) { return; }

    function fit() {
        table.style.fontSize = '';
        if (window.innerWidth <= 720) { return; }       // mobile uses the stacked card layout
        var size = parseFloat(getComputedStyle(table).fontSize) || 12;
        var min = 9;
        while (table.scrollWidth > wrap.clientWidth + 1 && size > min) {
            size -= 0.5;
            table.style.fontSize = size + 'px';
        }
        if (table.scrollWidth > wrap.clientWidth + 1) {
            wrap.style.overflowX = 'auto';               // last resort: still reachable by scrolling
        } else {
            wrap.style.overflowX = '';
        }
    }

    var timer = null;
    window.addEventListener('resize', function () {
        clearTimeout(timer);
        timer = setTimeout(fit, 120);
    });
    if (document.fonts && document.fonts.ready) { document.fonts.ready.then(fit); }
    fit();
})();
