/**
 * diamond_search.js
 * The pills/shapes are real <input type="checkbox"> elements (inside
 * <label> so clicking the pill toggles it) — this file just adds the
 * visual "selected" state and a couple of UX niceties on top of a
 * form that submits normally.
 */
(function () {
    var form = document.getElementById('dsForm');
    if (!form) {
        return;
    }

    function setSelectedClass(input) {
        var label = input.closest('.ds-pill, .ds-shape-btn');
        if (label) {
            label.classList.toggle('ds-selected', input.checked);
        }
    }

    form.addEventListener('change', function (e) {
        var input = e.target;
        if (!(input.matches('.ds-pill input[type=checkbox], .ds-shape-btn input[type=checkbox]'))) {
            return;
        }
        setSelectedClass(input);

        // If the checked box is "All" (__ALL__), uncheck every other
        // box in the same section — and vice versa, checking any
        // specific option clears "All" — so the visible state never
        // contradicts itself (the backend already treats "All" as
        // taking priority regardless, this is purely for clarity).
        var section = input.closest('.ds-section');
        if (!section) {
            return;
        }
        var allBoxesInSection = section.querySelectorAll('input[type=checkbox]');
        if (input.value === '__ALL__' && input.checked) {
            allBoxesInSection.forEach(function (other) {
                if (other !== input && other.checked) {
                    other.checked = false;
                    setSelectedClass(other);
                }
            });
        } else if (input.checked) {
            allBoxesInSection.forEach(function (other) {
                if (other !== input && other.value === '__ALL__' && other.checked) {
                    other.checked = false;
                    setSelectedClass(other);
                }
            });
        }
    });

    // Reset means "back to the true initial state" — every "All"
    // option checked, everything else cleared — regardless of
    // whatever the page happened to load with (which, after using
    // "Back to Search", could be a restored PREVIOUS search rather
    // than the defaults). A native <button type="reset"> would only
    // revert to that loaded state, not necessarily "All" — so this
    // is handled explicitly instead.
    var resetBtn = document.getElementById('dsResetBtn');
    if (resetBtn) {
        resetBtn.addEventListener('click', function () {
            form.querySelectorAll('input[type=checkbox]').forEach(function (cb) {
                cb.checked = (cb.value === '__ALL__');
                setSelectedClass(cb);
            });
            form.querySelectorAll('input[type=number]').forEach(function (inp) {
                inp.value = '';
            });
            if (advancedPanel) {
                advancedPanel.setAttribute('hidden', '');
            }
            if (advancedToggleBtn) {
                advancedToggleBtn.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // "Advanced Filter" reveals the extra sections sourced from
    // adv_filter (hidden by default, unless they started open because
    // a prior search had one of them selected).
    var advancedToggleBtn = document.getElementById('dsAdvancedToggleBtn');
    var advancedPanel = document.getElementById('dsAdvancedPanel');
    if (advancedToggleBtn && advancedPanel) {
        advancedToggleBtn.addEventListener('click', function () {
            var willShow = advancedPanel.hasAttribute('hidden');
            if (willShow) {
                advancedPanel.removeAttribute('hidden');
            } else {
                advancedPanel.setAttribute('hidden', '');
            }
            advancedToggleBtn.setAttribute('aria-expanded', willShow ? 'true' : 'false');
        });
    }
    // Stepper +/- buttons next to range inputs (Price, Amount, Carat,
    // etc.) — nudge the adjacent number input up/down by its step.
    form.addEventListener('click', function (e) {
        var btn = e.target.closest('.ds-stepper-btn');
        if (!btn) {
            return;
        }
        var input = btn.parentElement.querySelector('input[type=number]');
        if (!input) {
            return;
        }
        var step = parseFloat(btn.getAttribute('data-step')) || 1;
        var current = parseFloat(input.value);
        if (isNaN(current)) {
            current = 0;
        }
        var next = current + step;
        if (next < 0) {
            next = 0;
        }
        // Avoid floating-point artifacts like 0.1 + 0.01 = 0.11000000000000001
        input.value = Math.round(next * 100) / 100;
    });

})();
