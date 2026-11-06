/**
 * results_toolbar.js — Results and View Cart / View (Selected).
 *  - "Selected | Total Weight | Total Amount" in the summary line, from
 *    the ticked rows on this page (the same rows Copy, Excel-Select,
 *    Add to cart and Memo act on).
 *  - "Search Customer" narrows the Select Customer list as you type.
 * Row selection itself is handled by memo_actions.js (loaded first).
 */
(function () {
    'use strict';

    var countEl = document.getElementById('rsSelCount');
    var weightEl = document.getElementById('rsSelWeight');
    var amountEl = document.getElementById('rsSelAmount');

    function fmt(n) {
        return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function updateTotals() {
        if (!countEl) {
            return;
        }
        var count = 0, weight = 0, amount = 0;
        document.querySelectorAll('.memo-row-select:checked').forEach(function (cb) {
            count++;
            weight += parseFloat(cb.getAttribute('data-weight')) || 0;
            amount += parseFloat(cb.getAttribute('data-amount')) || 0;
        });
        countEl.textContent = String(count);
        weightEl.textContent = fmt(weight);
        amountEl.textContent = fmt(amount);
    }

    document.addEventListener('change', function (e) {
        if (e.target.matches('.memo-row-select, #memoSelectAll')) {
            updateTotals();
        }
    });
    var clearBtn = document.getElementById('clearSelectionBtn');
    if (clearBtn) {
        clearBtn.addEventListener('click', function () { setTimeout(updateTotals, 0); });
    }
    updateTotals(); // rows remembered from earlier visits start ticked

    // ---- Search Customer -> filters the Select Customer options ----
    var search = document.getElementById('memoCustomerSearch');
    var select = document.getElementById('memoCustomerSelect');
    if (search && select) {
        search.addEventListener('input', function () {
            var q = search.value.trim().toLowerCase();
            var firstMatch = null;
            Array.prototype.forEach.call(select.options, function (opt) {
                var keep = opt.value === '' || opt.value === '__new__' || q === ''
                    || opt.text.toLowerCase().indexOf(q) !== -1;
                opt.hidden = !keep;
                opt.disabled = !keep;
                if (keep && !firstMatch && opt.value !== '' && opt.value !== '__new__') {
                    firstMatch = opt;
                }
            });
            // Pick the first match automatically so Memo-1 / Memo-3 use it.
            if (q !== '' && firstMatch) {
                select.value = firstMatch.value;
            } else if (q !== '' && !firstMatch) {
                select.value = '';
            }
        });
    }
})();
