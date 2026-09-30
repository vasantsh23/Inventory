/* Website behaviour: mobile menu, sticky-header state, reviews carousel,
   focus on form messages. No dependencies; everything degrades without JS. */
(function () {
    'use strict';
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // Mobile menu
    document.querySelectorAll('[data-menu-toggle]').forEach(function (btn) {
        var header = btn.closest('[data-header]');
        btn.addEventListener('click', function () {
            var open = btn.getAttribute('aria-expanded') !== 'true';
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (header) header.classList.toggle('is-open', open);
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && btn.getAttribute('aria-expanded') === 'true') {
                btn.click(); btn.focus();
            }
        });
    });

    // Header state once the page is scrolled
    var header = document.querySelector('[data-header]');
    if (header) {
        var onScroll = function () { header.classList.toggle('is-scrolled', window.scrollY > 8); };
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
    }

    // Reviews carousel
    document.querySelectorAll('[data-carousel]').forEach(function (root) {
        var slides = Array.prototype.slice.call(root.querySelectorAll('[data-slide]'));
        if (slides.length < 2) return;
        var dots = root.querySelector('[data-dots]');
        var i = 0, timer = null;
        slides.forEach(function (s, n) {
            var d = document.createElement('button');
            d.type = 'button';
            d.setAttribute('aria-label', 'Show review ' + (n + 1));
            d.addEventListener('click', function () { go(n); restart(); });
            dots.appendChild(d);
        });
        function go(n) {
            i = (n + slides.length) % slides.length;
            slides.forEach(function (s, k) {
                s.classList.toggle('is-active', k === i);
                s.setAttribute('aria-hidden', k === i ? 'false' : 'true');
            });
            Array.prototype.forEach.call(dots.children, function (d, k) { d.setAttribute('aria-current', k === i ? 'true' : 'false'); });
        }
        function restart() {
            if (reduce) return;
            clearInterval(timer);
            timer = setInterval(function () { go(i + 1); }, 7000);
        }
        root.querySelector('[data-prev]').addEventListener('click', function () { go(i - 1); restart(); });
        root.querySelector('[data-next]').addEventListener('click', function () { go(i + 1); restart(); });
        root.addEventListener('mouseenter', function () { clearInterval(timer); });
        root.addEventListener('mouseleave', restart);
        root.addEventListener('focusin', function () { clearInterval(timer); });
        root.classList.add('is-ready');
        go(0); restart();
    });

    // Bring form messages into view after sending
    var notice = document.querySelector('[data-focus]');
    if (notice) {
        notice.scrollIntoView({ block: 'center', behavior: reduce ? 'auto' : 'smooth' });
        notice.focus({ preventScroll: true });
    }
})();
