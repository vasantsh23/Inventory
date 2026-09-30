/* Public website behaviour shared by every template:
   mobile menu, header state on scroll, and the hero slider. */
(function () {
    'use strict';

    // ---- Mobile menu --------------------------------------------------
    document.querySelectorAll('[data-nav]').forEach(function (header) {
        var toggle = header.querySelector('.nav-toggle');
        if (!toggle) { return; }
        function setOpen(open) {
            if (open) { header.setAttribute('data-nav-open', ''); } else { header.removeAttribute('data-nav-open'); }
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
        }
        toggle.addEventListener('click', function () { setOpen(!header.hasAttribute('data-nav-open')); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { setOpen(false); } });
        window.matchMedia('(min-width: 1101px)').addEventListener('change', function (m) { if (m.matches) { setOpen(false); } });
    });

    // ---- Header gets .is-scrolled once the page moves --------------------
    var sticky = document.querySelector('[data-sticky-header]');
    if (sticky) {
        var onScroll = function () { sticky.classList.toggle('is-scrolled', window.scrollY > 24); };
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
    }

    // ---- Slider --------------------------------------------------------------
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    document.querySelectorAll('[data-slider]').forEach(function (slider) {
        var slides = Array.prototype.slice.call(slider.querySelectorAll('[data-slide]'));
        var dots = Array.prototype.slice.call(slider.querySelectorAll('[data-slide-dot]'));
        if (slides.length < 2) { return; }
        var current = 0, timer = null, delay = parseInt(slider.getAttribute('data-interval') || '7000', 10);

        function show(i) {
            current = (i + slides.length) % slides.length;
            slides.forEach(function (s, n) {
                var on = n === current;
                s.classList.toggle('is-current', on);
                s.setAttribute('aria-hidden', on ? 'false' : 'true');
                s.querySelectorAll('a, button').forEach(function (el) { el.tabIndex = on ? 0 : -1; });
            });
            dots.forEach(function (d, n) {
                d.classList.toggle('is-current', n === current);
                d.setAttribute('aria-current', n === current ? 'true' : 'false');
            });
        }
        function start() { if (!reduce) { stop(); timer = window.setInterval(function () { show(current + 1); }, delay); } }
        function stop() { if (timer) { window.clearInterval(timer); timer = null; } }

        dots.forEach(function (d, n) { d.addEventListener('click', function () { show(n); start(); }); });
        var prev = slider.querySelector('[data-slide-prev]'), next = slider.querySelector('[data-slide-next]');
        if (prev) { prev.addEventListener('click', function () { show(current - 1); start(); }); }
        if (next) { next.addEventListener('click', function () { show(current + 1); start(); }); }
        slider.addEventListener('mouseenter', stop);
        slider.addEventListener('mouseleave', start);
        slider.addEventListener('focusin', stop);
        show(0);
        start();
    });
})();
