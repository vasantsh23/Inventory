/**
 * admin_sidebar.js
 * On mobile, the admin sidebar is hidden by default (via CSS) so
 * page content is immediately visible rather than buried below a
 * long list of nav links. This file wires up the hamburger button
 * to slide it in/out as an off-canvas menu.
 */
(function () {
    var toggleBtn = document.getElementById('sidebarToggleBtn');
    var shell = document.getElementById('dashShell');
    var backdrop = document.getElementById('sidebarBackdrop');
    if (!toggleBtn || !shell) {
        return;
    }

    function setOpen(open) {
        shell.classList.toggle('sidebar-open', open);
        toggleBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    toggleBtn.addEventListener('click', function () {
        setOpen(!shell.classList.contains('sidebar-open'));
    });

    if (backdrop) {
        backdrop.addEventListener('click', function () {
            setOpen(false);
        });
    }

    // Closing on link click keeps the menu from staying open after
    // navigating (relevant since this is a full page reload each
    // time, but avoids a jarring "still open" flash on slow loads).
    var sidebar = document.getElementById('dashSidebar');
    if (sidebar) {
        sidebar.addEventListener('click', function (e) {
            if (e.target.tagName === 'A') {
                setOpen(false);
            }
        });
    }
})();
