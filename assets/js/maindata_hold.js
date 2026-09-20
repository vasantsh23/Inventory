/**
 * maindata_hold.js
 * Admin > Hold Selection. "Clear Selection" just unchecks every box
 * on screen (nothing is saved until the Save button is clicked); the
 * "Rows per page" select submits its own tiny form on change. Both
 * are wired up here, in an external file, rather than as inline
 * onchange/onclick attributes — the site's Content-Security-Policy is
 * script-src 'self' with no 'unsafe-inline', which silently blocks
 * inline event-handler attributes in CSP-compliant browsers.
 */
(function () {
    var holdForm = document.getElementById('holdForm');

    function clearSelection() {
        if (!holdForm) {
            return;
        }
        holdForm.querySelectorAll('input[type=checkbox][name="hold_ids[]"]').forEach(function (cb) {
            cb.checked = false;
        });
    }

    ['holdClearBtn', 'holdClearBtnBottom'].forEach(function (id) {
        var btn = document.getElementById(id);
        if (btn) {
            btn.addEventListener('click', clearSelection);
        }
    });

    var perPageSelect = document.getElementById('perPageSelect');
    if (perPageSelect) {
        perPageSelect.addEventListener('change', function () {
            perPageSelect.form.submit();
        });
    }
})();
