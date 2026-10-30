/**
 * auto_submit.js
 * Submits the enclosing form when a <select data-auto-submit> changes —
 * used by the "show only…" filter dropdowns on admin table lists. A
 * separate file because the Content-Security-Policy (script-src 'self')
 * blocks inline onchange="" attributes.
 */
(function () {
    'use strict';
    document.querySelectorAll('select[data-auto-submit]').forEach(function (sel) {
        sel.addEventListener('change', function () {
            if (sel.form) {
                sel.form.submit();
            }
        });
    });
})();
