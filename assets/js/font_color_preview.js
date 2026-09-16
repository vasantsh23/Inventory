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

    // Clicking a forecolor/backcolor swatch updates the dummy preview image.
    var dummyImage = document.getElementById('fc-dummy-image');
    var activeButtons = { fg: null, bg: null };

    document.querySelectorAll('.fc-swatch-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var hex = btn.getAttribute('data-hex');
            var target = btn.getAttribute('data-target');
            if (!hex || !dummyImage) {
                return;
            }
            if (target === 'fg') {
                dummyImage.style.color = hex;
            } else if (target === 'bg') {
                dummyImage.style.backgroundColor = hex;
            }

            if (activeButtons[target]) {
                activeButtons[target].classList.remove('fc-swatch-active');
            }
            btn.classList.add('fc-swatch-active');
            activeButtons[target] = btn;
        });
    });
})();
