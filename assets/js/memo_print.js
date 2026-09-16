/**
 * memo_print.js
 * Wires up the Print button on the memo output page. This has to be
 * an external file, not an inline onclick="" attribute — the app's
 * Content-Security-Policy (script-src 'self', no unsafe-inline)
 * silently blocks inline event handlers.
 */
(function () {
    var btn = document.getElementById('memoPrintBtn');
    if (btn) {
        btn.addEventListener('click', function () {
            window.print();
        });
    }
})();
