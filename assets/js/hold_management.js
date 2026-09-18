/**
 * hold_management.js
 * "Clear Selection" unchecks every visible checkbox (does not submit
 * anything — Save is a separate, explicit action). The search box
 * filters which rows are shown on the page by hiding/showing <tr>
 * elements based on a plain text match — it never changes what gets
 * submitted on Save, since hidden rows' checkboxes/values are still
 * present in the form.
 */
(function () {
    var table = document.getElementById('holdManagementTable');
    var searchBox = document.getElementById('holdSearchBox');
    var clearBtn = document.getElementById('holdClearSelectionBtn');

    if (searchBox && table) {
        searchBox.addEventListener('input', function () {
            var term = searchBox.value.trim().toLowerCase();
            var rows = table.querySelectorAll('tbody tr');
            rows.forEach(function (row) {
                var text = row.textContent.toLowerCase();
                row.style.display = (term === '' || text.indexOf(term) !== -1) ? '' : 'none';
            });
        });
    }

    if (clearBtn && table) {
        clearBtn.addEventListener('click', function () {
            table.querySelectorAll('input[type=checkbox]').forEach(function (cb) {
                cb.checked = false;
            });
        });
    }
})();
