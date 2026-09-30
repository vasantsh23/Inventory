/* Website admin screens: submit the template picker on change, preview picked images. */
(function () {
    'use strict';
    document.querySelectorAll('[data-autosubmit]').forEach(function (el) {
        el.addEventListener('change', function () { el.form.submit(); });
    });
    var base = document.body.getAttribute('data-base') || '';
    document.querySelectorAll('.sc-image').forEach(function (box) {
        var sel = box.querySelector('select'), pv = box.querySelector('.sc-image__pv'), file = box.querySelector('input[type=file]');
        function show(src) {
            if (!src) { var s = document.createElement('span'); s.className = 'sc-image__pv sc-image__pv--none'; s.textContent = 'No image'; pv.replaceWith(s); pv = s; return; }
            if (pv.tagName !== 'IMG') { var i = document.createElement('img'); i.className = 'sc-image__pv'; i.alt = ''; pv.replaceWith(i); pv = i; }
            pv.src = src;
        }
        sel.addEventListener('change', function () { show(sel.value ? (sel.getAttribute('data-root') || '') + sel.value : ''); });
        file.addEventListener('change', function () { if (file.files && file.files[0]) show(URL.createObjectURL(file.files[0])); });
    });
})();
