/**
 * login.js
 * Password show/hide toggle for the login screen.
 * Loaded as an external file (not inline) because the site's
 * Content-Security-Policy uses script-src 'self' with no
 * 'unsafe-inline' — inline <script> blocks are silently blocked by
 * the browser under that policy, so this logic has to live here.
 */
(function () {
    var pwd = document.getElementById('password');
    var btn = document.getElementById('togglePassword');
    if (!pwd || !btn) {
        return;
    }
    var eyeIcon = document.getElementById('eyeIcon');
    var eyeOffIcon = document.getElementById('eyeOffIcon');

    btn.addEventListener('click', function () {
        var show = pwd.type === 'password';
        pwd.type = show ? 'text' : 'password';
        if (eyeIcon && eyeOffIcon) {
            eyeIcon.style.display = show ? 'none' : 'block';
            eyeOffIcon.style.display = show ? 'block' : 'none';
        }
        btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    });
})();
