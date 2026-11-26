/*
 * Spectrum template — Table / Grid switch on Results and View Cart.
 * Buttons [data-results-view="table|grid"] set body.sp-grid, which
 * search_spectrum.css uses to show each row as a card. Remembered in
 * this browser (localStorage, if allowed). After a switch the page fires
 * a resize so results_fit.js re-fits the table.
 */
(function () {
    'use strict';
    var buttons = document.querySelectorAll('[data-results-view]');
    if (!buttons.length) { return; }
    var KEY = 'resultsView';

    function apply(view) {
        document.body.classList.toggle('sp-grid', view === 'grid');
        buttons.forEach(function (b) {
            b.setAttribute('aria-pressed', b.getAttribute('data-results-view') === view ? 'true' : 'false');
        });
        window.dispatchEvent(new Event('resize'));
    }

    var saved = 'table';
    try { saved = localStorage.getItem(KEY) || 'table'; } catch (e) { /* storage blocked */ }
    apply(saved === 'grid' ? 'grid' : 'table');

    buttons.forEach(function (b) {
        b.addEventListener('click', function () {
            var view = b.getAttribute('data-results-view');
            try { localStorage.setItem(KEY, view); } catch (e) { /* storage blocked */ }
            apply(view);
        });
    });
})();
