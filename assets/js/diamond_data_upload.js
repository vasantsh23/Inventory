/**
 * diamond_data_upload.js
 * Confirms before submitting the Diamond Data Upload form, with a
 * stronger warning when "Replace data" is selected since that
 * deletes every existing maindata row before importing.
 *
 * Uses a live query (input[name="upload_mode"]:checked) rather than
 * a single cached element reference, and shows the currently
 * selected mode directly on the page — both to remove any ambiguity
 * about which option is active, and so a mismatch here (indicator
 * says one thing, actual behavior does another) would clearly point
 * to a stale cached copy of this file rather than a logic bug.
 *
 * Filename display is also handled here explicitly rather than
 * relying on the browser's native file-input text, which renders
 * inconsistently in appearance and color across browsers.
 */
(function () {
    var form = document.getElementById('diamondUploadForm');
    var modeIndicator = document.getElementById('uploadModeIndicator');
    var fileInput = document.getElementById('csv_file');
    var fileNameDisplay = document.getElementById('csvFileNameDisplay');

    function getSelectedMode() {
        var checked = document.querySelector('input[name="upload_mode"]:checked');
        return checked ? checked.value : null;
    }

    function updateModeIndicator() {
        if (!modeIndicator) {
            return;
        }
        var mode = getSelectedMode();
        modeIndicator.textContent = mode === 'replace'
            ? 'Selected: Replace data — existing records will be deleted first.'
            : 'Selected: Add data — records will be appended.';
    }

    document.querySelectorAll('input[name="upload_mode"]').forEach(function (radio) {
        radio.addEventListener('change', updateModeIndicator);
    });
    updateModeIndicator();

    if (fileInput && fileNameDisplay) {
        fileInput.addEventListener('change', function () {
            fileNameDisplay.textContent = fileInput.files.length > 0
                ? fileInput.files[0].name
                : 'No file chosen';
        });
    }

    if (!form) {
        return;
    }

    form.addEventListener('submit', function (e) {
        var mode = getSelectedMode();
        var message = (mode === 'replace')
            ? 'Replace data will permanently DELETE every existing diamond record before importing this file. This cannot be undone. Continue?'
            : 'Add this file\'s records to the existing diamond data?';
        if (!window.confirm(message)) {
            e.preventDefault();
        }
    });
})();
