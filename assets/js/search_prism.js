/*
 * Prism search template — live summary in the bottom bar:
 * "3 filters selected: Shape, Color, Carat" (or "No filters selected").
 * Counts sections with a ticked option (other than "All") or a typed
 * from/to value. Pill behaviour itself is in diamond_search.js.
 */
(function () {
    'use strict';
    var form = document.getElementById('dsForm');
    var out = document.getElementById('pzFilterCount');
    if (!form || !out) { return; }

    function sectionName(section) {
        var title = section.querySelector('.pz-card-title');
        return title ? title.textContent.replace(/\s+/g, ' ').trim() : '';
    }

    function update() {
        var names = [];
        form.querySelectorAll('.ds-section').forEach(function (section) {
            var used = false;
            section.querySelectorAll('input[type=checkbox]:checked').forEach(function (cb) {
                if (cb.value !== '__ALL__' && !cb.hasAttribute('data-flag')) { used = true; }
            });
            section.querySelectorAll('input[type=number], input[type=text]').forEach(function (inp) {
                if (inp.value.trim() !== '') { used = true; }
            });
            section.classList.toggle('pz-card--active', used);
            if (used) { names.push(sectionName(section)); }
        });
        if (names.length === 0) {
            out.textContent = 'No filters selected — showing all stock';
        } else {
            out.textContent = names.length + (names.length === 1 ? ' filter' : ' filters') + ' selected: ' + names.join(', ');
        }
    }

    form.addEventListener('change', function () { setTimeout(update, 0); });
    form.addEventListener('input', update);
    form.addEventListener('click', function () { setTimeout(update, 0); });
    update();
})();
