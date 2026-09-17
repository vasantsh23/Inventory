/**
 * diamond_data_upload.js
 * Confirms before submitting the Diamond Data Upload form, with a
 * stronger warning when "Replace data" is selected since that
 * deletes every existing maindata row before importing.
 */
(function () {
    var form = document.getElementById('diamondUploadForm');
    var replaceRadio = document.getElementById('uploadModeReplace');
    if (!form) {
        return;
    }

    form.addEventListener('submit', function (e) {
        var message = (replaceRadio && replaceRadio.checked)
            ? 'Replace data will permanently DELETE every existing diamond record before importing this file. This cannot be undone. Continue?'
            : 'Add this file\'s records to the existing diamond data?';
        if (!window.confirm(message)) {
            e.preventDefault();
        }
    });
})();
