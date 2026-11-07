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

    // Color: choosing "Fancy" is mutually exclusive with every other
    // Color option (Others included) — every other pill in that
    // section gets visibly disabled while Fancy stays checked, so it
    // can't be combined with a specific color grade. Every other
    // filter SECTION (Shape, Clarity, Cut, etc.) is unaffected.
    function applyColorFancyExclusivity(section) {
        var fancyBox = section.querySelector('input[data-color-fancy]');
        if (!fancyBox) {
            return;
        }
        var isFancy = fancyBox.checked;
        section.querySelectorAll('input[type=checkbox]').forEach(function (cb) {
            if (cb === fancyBox) {
                return;
            }
            if (isFancy) {
                if (cb.checked) {
                    cb.checked = false;
                    setSelectedClass(cb);
                }
                cb.disabled = true;
            } else {
                cb.disabled = false;
            }
            var label = cb.closest('.ds-pill');
            if (label) {
                label.classList.toggle('ds-pill-disabled', isFancy);
            }
        });
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

        if (input.checked && section.getAttribute('data-range') === '1') {
            fillRange(section);
        }
        if (section.getAttribute('data-fldname') === 'Color') {
            applyColorFancyExclusivity(section);
        }
        handlePresetRules(input, section);
    });

    // Range = yes (Diamond Search Fields / Advanced Filter table): when an
    // option is ticked, every option between the first and last ticked
    // ones is ticked too, in the order the pills are shown — e.g. Color
    // D and G also ticks E and F. "All", "Others", "Fancy" and other
    // catch-all pills are never part of a range. Unticking one in the
    // middle afterwards is allowed (only ticking re-fills).
    function isRangeOption(cb) {
        if (cb.value === '__ALL__' || cb.hasAttribute('data-color-fancy') || cb.disabled) {
            return false;
        }
        var label = cb.closest('.ds-pill, .ds-shape-btn');
        var text = (label ? label.textContent : cb.value).replace(/\s+/g, ' ').trim().toUpperCase();
        return text !== 'OTHERS' && text !== 'OTHER' && text !== 'ALL';
    }

    function fillRange(section) {
        var options = Array.prototype.filter.call(section.querySelectorAll('input[type=checkbox]'), isRangeOption);
        var first = -1, last = -1;
        options.forEach(function (cb, i) {
            if (cb.checked) {
                if (first < 0) {
                    first = i;
                }
                last = i;
            }
        });
        if (first < 0 || last - first < 2) {
            return;
        }
        for (var i = first + 1; i < last; i++) {
            if (!options[i].checked) {
                options[i].checked = true;
                setSelectedClass(options[i]);
            }
        }
    }

    // 3 EX / 3 VG / 3EX-VG (row under Symmetry, section "CPS3"): one at
    // a time. Choosing one highlights the matching grade(s) in Cut,
    // Polish and Symmetry (3 EX -> EX, 3 VG -> VG, 3EX-VG -> EX and VG);
    // turning it off puts those three back on "All". Changing Cut,
    // Polish or Symmetry by hand turns the shortcut off and keeps the
    // hand-picked grades. (The server still applies the shortcut's own
    // rule — all three EX, or all three VG, on the same stone — so the
    // highlighted pills never widen the result.)
    var CPS_FIELDS = ['CutGrade', 'Polish', 'Symmetry'];
    var PRESET_GRADES = { EX: ['EX'], VG: ['VG'], EXVG: ['EX', 'VG'] };
    var GRADE_WORDS = { EX: ['EX', 'EXCELLENT'], VG: ['VG', 'VERY GOOD'] };
    var presetSection = form.querySelector('.ds-section[data-fldname="CPS3"]');

    function norm(text) {
        return String(text || '').replace(/\s+/g, ' ').trim().toUpperCase();
    }

    function optionGrade(cb) {
        var label = cb.closest('.ds-pill');
        var texts = [norm(cb.value), norm(label ? label.textContent : '')];
        var found = null;
        Object.keys(GRADE_WORDS).forEach(function (grade) {
            if (texts.some(function (t) { return GRADE_WORDS[grade].indexOf(t) >= 0; })) {
                found = grade;
            }
        });
        return found;
    }

    function setCpsSections(grades) {
        CPS_FIELDS.forEach(function (fld) {
            var section = form.querySelector('.ds-section[data-fldname="' + fld + '"]');
            if (!section) {
                return;
            }
            var boxes = section.querySelectorAll('input[type=checkbox]');
            var matched = 0;
            boxes.forEach(function (cb) {
                var on = grades !== null && cb.value !== '__ALL__' && grades.indexOf(optionGrade(cb)) >= 0;
                cb.checked = on;
                if (on) {
                    matched++;
                }
            });
            // No matching grade in this section (or shortcut cleared): fall back to "All".
            if (matched === 0) {
                boxes.forEach(function (cb) { cb.checked = (cb.value === '__ALL__'); });
            }
            boxes.forEach(setSelectedClass);
        });
    }

    function clearPresets(except) {
        if (!presetSection) {
            return;
        }
        presetSection.querySelectorAll('input[type=checkbox]').forEach(function (cb) {
            if (cb !== except && cb.checked) {
                cb.checked = false;
                setSelectedClass(cb);
            }
        });
    }

    function handlePresetRules(input, section) {
        var fld = section.getAttribute('data-fldname');
        if (fld === 'CPS3') {
            if (input.checked) {
                clearPresets(input);
                setCpsSections(PRESET_GRADES[input.value] || []);
            } else {
                setCpsSections(null); // shortcut switched off -> Cut/Polish/Sym back to All
            }
        } else if (CPS_FIELDS.indexOf(fld) >= 0) {
            clearPresets(null); // any hand change to Cut/Polish/Sym ends the shortcut
        }
    }

    // Apply the Fancy/Others exclusivity immediately on page load too
    // — e.g. after "Back to Search" restores a prior search that had
    // Fancy checked, the other Color pills must start disabled, not
    // just after the next click.
    var colorSection = form.querySelector('.ds-section[data-fldname="Color"]');
    if (colorSection) {
        applyColorFancyExclusivity(colorSection);
    }

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
            form.querySelectorAll('input[type=number], input[type=text]').forEach(function (inp) {
                inp.value = '';
            });
            if (colorSection) {
                applyColorFancyExclusivity(colorSection);
            }
            if (advancedPanel) {
                advancedPanel.setAttribute('hidden', '');
            }
            if (advancedToggleBtn) {
                advancedToggleBtn.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // Per-section "Reset" button (currently just Carat/Weight) —
    // clears only that section's pill selections and manual From/To
    // inputs, leaving every other filter on the page untouched.
    document.querySelectorAll('.ds-section-reset-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var section = btn.closest('.ds-section');
            if (!section) {
                return;
            }
            section.querySelectorAll('input[type=checkbox]').forEach(function (cb) {
                cb.checked = false;
                setSelectedClass(cb);
            });
            section.querySelectorAll('input[type=number]').forEach(function (inp) {
                inp.value = '';
            });
        });
    });

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

    // One-screen fit (desktop/laptop): if the page is still taller than
    // the window after the compact CSS, scale the search block down
    // (CSS zoom, which reflows the layout) just enough to remove the
    // vertical scrollbar — never below 70%, and never on phones, where
    // normal scrolling is kinder than tiny buttons.
    var fitTarget = document.querySelector('.ds-page');
    var MIN_ZOOM = 0.7;
    function pageOverflows() {
        return document.documentElement.scrollHeight > window.innerHeight;
    }
    function fitToWindow() {
        if (!fitTarget) {
            return;
        }
        fitTarget.style.zoom = '';
        if (window.innerWidth <= 720 || !pageOverflows()) {
            return;
        }
        var lo = MIN_ZOOM, hi = 1;
        fitTarget.style.zoom = String(lo);
        if (pageOverflows()) {
            return; // even the minimum doesn't fit; keep it readable and let it scroll
        }
        for (var i = 0; i < 7; i++) {   // binary search for the largest zoom that fits
            var mid = (lo + hi) / 2;
            fitTarget.style.zoom = String(mid);
            if (pageOverflows()) {
                hi = mid;
            } else {
                lo = mid;
            }
        }
        fitTarget.style.zoom = String(Math.floor(lo * 100) / 100);
    }
    var fitTimer = null;
    function scheduleFit() {
        clearTimeout(fitTimer);
        fitTimer = setTimeout(fitToWindow, 100);
    }
    window.addEventListener('resize', scheduleFit);
    window.addEventListener('load', fitToWindow);   // after shape images load
    if (advancedToggleBtn) {
        advancedToggleBtn.addEventListener('click', fitToWindow);
    }
    if (resetBtn) {
        resetBtn.addEventListener('click', function () { setTimeout(fitToWindow, 0); });
    }
    fitToWindow();

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
