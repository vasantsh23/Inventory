/**
 * select_search.js
 * Type-to-filter for a long <select>. Put a text box before the select:
 *
 *   <input type="search" data-select-search="custid" ...>
 *   <small data-select-search-count="custid"></small>   (optional)
 *   <select id="custid">...</select>
 *
 * Typing hides options whose text doesn't contain every word typed
 * (any order, any case), e.g. "kgk dmcc" or "gmail". The first option
 * with an empty value (the "— Select —" prompt) always stays. When
 * exactly one option matches, it's selected automatically. Enter in the
 * box selects the first match instead of submitting the form.
 *
 * The select is shown as an open list (data-select-search-size rows,
 * default 8) right under the box, instead of a pop-up that would cover
 * the search box while open.
 *
 * Options are removed and re-added rather than just hidden, because
 * Safari ignores `hidden` on <option>. Without JavaScript the plain
 * select still works. A separate file because the Content-Security-
 * Policy (script-src 'self') blocks inline scripts.
 */
(function () {
    'use strict';

    function norm(s) {
        return (s || '').toLowerCase().replace(/\s+/g, ' ').trim();
    }

    document.querySelectorAll('input[data-select-search]').forEach(function (box) {
        var id = box.getAttribute('data-select-search');
        var select = document.getElementById(id);
        if (!select) {
            return;
        }
        var counter = document.querySelector('[data-select-search-count="' + id + '"]');
        var rows = parseInt(box.getAttribute('data-select-search-size') || '8', 10);
        var all = Array.prototype.slice.call(select.options);
        var prompt = all.length && all[0].value === '' ? all[0] : null;
        var items = all.filter(function (o) { return o !== prompt; });
        var texts = items.map(function (o) { return norm(o.textContent); });

        function apply() {
            var words = norm(box.value).split(' ').filter(Boolean);
            var keep = select.value;
            var matches = items.filter(function (o, i) {
                return words.every(function (w) { return texts[i].indexOf(w) !== -1; });
            });

            select.innerHTML = '';
            if (prompt) {
                select.appendChild(prompt);
            }
            matches.forEach(function (o) { select.appendChild(o); });

            if (matches.length === 1) {
                select.value = matches[0].value;
            } else if (matches.some(function (o) { return o.value === keep; })) {
                select.value = keep;
            } else if (prompt) {
                select.value = '';
            }

            // Open list, sized to the matches (2 rows minimum so it never
            // turns back into a pop-up).
            select.size = Math.max(2, Math.min(rows, select.options.length));

            if (counter) {
                counter.textContent = words.length
                    ? (matches.length === 0 ? 'No customers match “' + box.value.trim() + '”.'
                        : matches.length + ' of ' + items.length + ' shown')
                    : items.length + ' customers';
            }
        }

        box.addEventListener('input', apply);
        box.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault(); // don't submit the form from the search box
                var first = Array.prototype.find.call(select.options, function (o) { return o.value !== ''; });
                if (first) {
                    select.value = first.value;
                    select.focus();
                }
            }
        });
        apply();
    });
})();
