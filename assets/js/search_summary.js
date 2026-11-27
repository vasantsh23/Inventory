/*
 * Live summary of the chosen filters on the Diamond Search page
 * (Atelier Luxe and Spectrum templates). Optional targets:
 *   #dsFilterSummary   text: "3 filters selected" / "No filters selected — showing all stock"
 *   #dsFilterList      list: one <li> per filter, e.g. "Shape — Oval, Pear"
 *   a[data-section-link="<section id>"]   gets .is-used when that filter is set
 * Each .ds-section carries data-title (its heading). Sections in use get
 * .is-used. Pill behaviour itself is in diamond_search.js.
 */
(function () {
    'use strict';
    var form = document.getElementById('dsForm');
    if (!form) { return; }
    var summary = document.getElementById('dsFilterSummary');
    var list = document.getElementById('dsFilterList');

    function textOf(cb) {
        var label = cb.closest('label');
        var t = label ? label.textContent : cb.value;
        return t.replace(/\s+/g, ' ').trim();
    }

    function chosen(section) {
        var values = [];
        section.querySelectorAll('input[type=checkbox]:checked').forEach(function (cb) {
            if (cb.value !== '__ALL__' && !cb.hasAttribute('data-flag')) { values.push(textOf(cb)); }
            else if (cb.value === '__ALL__' && section.getAttribute('data-fldname') === 'Color') { values.push('All (D to M)'); }
        });
        var nums = section.querySelectorAll('input[type=number]');
        for (var i = 0; i + 1 < nums.length; i += 2) {
            var a = nums[i].value.trim(), b = nums[i + 1].value.trim();
            if (a !== '' || b !== '') { values.push((a || '0') + ' – ' + (b || 'any')); }
        }
        section.querySelectorAll('input[type=text]').forEach(function (inp) {
            if (inp.value.trim() !== '') { values.push('"' + inp.value.trim() + '"'); }
        });
        return values;
    }

    function update() {
        var used = [];
        form.querySelectorAll('.ds-section').forEach(function (section) {
            var values = chosen(section);
            var on = values.length > 0;
            section.classList.toggle('is-used', on);
            if (section.id) {
                document.querySelectorAll('[data-section-link="' + section.id + '"]').forEach(function (a) {
                    a.classList.toggle('is-used', on);
                });
            }
            if (on) { used.push({ title: section.getAttribute('data-title') || '', values: values }); }
        });
        if (summary) {
            summary.textContent = used.length === 0
                ? 'No filters selected — showing all stock'
                : used.length + (used.length === 1 ? ' filter selected' : ' filters selected');
        }
        if (list) {
            list.innerHTML = '';
            used.forEach(function (u) {
                var li = document.createElement('li');
                var b = document.createElement('span');
                b.className = 'ds-sum-title';
                b.textContent = u.title;
                var v = document.createElement('span');
                v.className = 'ds-sum-values';
                v.textContent = u.values.join(', ');
                li.appendChild(b);
                li.appendChild(v);
                list.appendChild(li);
            });
            list.hidden = used.length === 0;
        }
    }

    form.addEventListener('change', function () { setTimeout(update, 0); });
    form.addEventListener('input', update);
    form.addEventListener('click', function () { setTimeout(update, 0); });
    update();
})();
