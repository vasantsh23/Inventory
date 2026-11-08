/**
 * Admin → Search Templates: keeps each colour picker and its hex box in
 * step. The hex box is the field that is saved, so picking a colour must
 * copy it there (an inline oninput would be blocked by the site's
 * Content-Security-Policy, which only allows script files).
 */
(function () {
    'use strict';
    var HEX = /^#[0-9a-fA-F]{6}$/;

    document.addEventListener('input', function (e) {
        var t = e.target;
        var wrap = t.closest('.stp-color');
        if (!wrap) {
            return;
        }
        var picker = wrap.querySelector('[data-stp-picker]');
        var hex = wrap.querySelector('[data-stp-hex]');
        if (!picker || !hex) {
            return;
        }
        if (t === picker) {
            hex.value = picker.value;
        } else if (t === hex && HEX.test(hex.value.trim())) {
            picker.value = hex.value.trim().toLowerCase();
        }
    });
    // Some browsers only fire "change" when the picker dialog closes.
    document.addEventListener('change', function (e) {
        var t = e.target;
        if (t.matches && t.matches('[data-stp-picker]')) {
            var hex = t.closest('.stp-color').querySelector('[data-stp-hex]');
            if (hex) {
                hex.value = t.value;
            }
        }
    });
})();
