/**
 * confirm_submit.js
 * Generic "are you sure?" guard for destructive forms (delete /
 * overwrite / clear actions). Add data-confirm="Your question?" to
 * a <form> instead of an inline onsubmit="return confirm(...)"
 * attribute — the site's Content-Security-Policy is script-src
 * 'self' with no 'unsafe-inline', which silently blocks inline
 * event-handler attributes in CSP-compliant browsers, so an inline
 * onsubmit confirm() never actually runs and the form submits
 * immediately with no confirmation at all.
 */
(function () {
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!window.confirm(form.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });
    });
})();
