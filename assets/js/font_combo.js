/**
 * font_combo.js
 * A lightweight combobox: clicking/focusing the input always opens
 * the panel showing the FULL option list (unlike a native
 * <input list="..."> datalist, which filters by whatever text is
 * already in the field). Typing then filters the open panel live.
 * Selecting an option sets the field's value and closes the panel.
 */
(function () {
    var sourceCache = {}; // combo source key -> string[] of options (parsed once)

    function getOptions(sourceKey) {
        if (sourceCache[sourceKey]) {
            return sourceCache[sourceKey];
        }
        var el = document.getElementById(sourceKey);
        if (!el) {
            return [];
        }
        try {
            sourceCache[sourceKey] = JSON.parse(el.textContent);
        } catch (e) {
            sourceCache[sourceKey] = [];
        }
        return sourceCache[sourceKey];
    }

    function closePanel(panel) {
        panel.hidden = true;
        panel.innerHTML = '';
    }

    function openPanel(wrap, input, panel, sourceKey) {
        var options = getOptions(sourceKey);
        // Opening always shows the FULL list — irrespective of whatever value
        // is already in the field — matching a click-to-browse dropdown rather
        // than a filtered suggestion box.
        renderList(panel, options, input, true);
        panel.hidden = false;
    }

    function renderList(panel, options, input, forceAll) {
        panel.innerHTML = '';
        var frag = document.createDocumentFragment();
        var query = forceAll ? '' : input.value.trim().toLowerCase();
        var shown = 0;
        var limit = 400; // render cap for performance; filtering narrows this fast

        for (var i = 0; i < options.length && shown < limit; i++) {
            var opt = options[i];
            if (query !== '' && opt.toLowerCase().indexOf(query) === -1) {
                continue;
            }
            var item = document.createElement('div');
            item.className = 'combo-option';
            item.textContent = opt;
            item.setAttribute('data-value', opt);
            frag.appendChild(item);
            shown++;
        }

        if (shown === 0) {
            var empty = document.createElement('div');
            empty.className = 'combo-empty';
            empty.textContent = 'No matches — you can still type a custom name.';
            frag.appendChild(empty);
        }

        panel.appendChild(frag);
    }

    document.querySelectorAll('.combo-wrap').forEach(function (wrap) {
        var input = wrap.querySelector('.combo-input');
        var panel = wrap.querySelector('.combo-panel');
        var sourceKey = wrap.getAttribute('data-combo-source');
        if (!input || !panel || !sourceKey) {
            return;
        }

        // Opening always shows the full list, regardless of current field content.
        input.addEventListener('focus', function () {
            openPanel(wrap, input, panel, sourceKey);
            input.select(); // typing immediately after opening replaces the old value
        });
        input.addEventListener('click', function () {
            if (panel.hidden) {
                openPanel(wrap, input, panel, sourceKey);
            }
        });

        // Typing filters the already-open panel.
        input.addEventListener('input', function () {
            if (panel.hidden) {
                openPanel(wrap, input, panel, sourceKey);
                return;
            }
            renderList(panel, getOptions(sourceKey), input, false);
        });

        panel.addEventListener('mousedown', function (e) {
            var item = e.target.closest('.combo-option');
            if (!item) {
                return;
            }
            e.preventDefault(); // keep focus from leaving the input before click registers
            input.value = item.getAttribute('data-value');
            closePanel(panel);
            input.dispatchEvent(new Event('change', { bubbles: true }));
        });

        input.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closePanel(panel);
            }
        });

        document.addEventListener('click', function (e) {
            if (!wrap.contains(e.target)) {
                closePanel(panel);
            }
        });
    });
})();
