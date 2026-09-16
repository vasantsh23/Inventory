/**
 * color_picker_sync.js
 * Pairs each native <input type="color"> swatch with a plain text
 * hex input. The swatch is purely a visual picker — it has no
 * name="" attribute and is never submitted — so a field the admin
 * never touches stays genuinely empty instead of silently saving
 * whatever default color the browser's color input happens to show.
 */
(function () {
    var swatches = document.querySelectorAll('.color-swatch[data-target]');
    swatches.forEach(function (swatch) {
        var textInput = document.getElementById(swatch.getAttribute('data-target'));
        if (!textInput) {
            return;
        }

        // Picking a color updates the visible hex text field.
        swatch.addEventListener('input', function () {
            textInput.value = swatch.value;
        });

        // Typing a valid hex code updates the swatch to match.
        textInput.addEventListener('input', function () {
            var val = textInput.value.trim();
            if (/^#[0-9a-fA-F]{6}$/.test(val)) {
                swatch.value = val;
            }
        });
    });
})();
