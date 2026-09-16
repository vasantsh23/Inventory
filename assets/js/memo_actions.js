/**
 * memo_actions.js
 * Handles the memo action bar on the Results page: selecting rows,
 * picking (or adding) a customer, and validating before generating
 * a Memo-1 or Memo-3 printout.
 */
(function () {
    var selectAll = document.getElementById('memoSelectAll');
    var customerSelect = document.getElementById('memoCustomerSelect');
    var errorBox = document.getElementById('memoError');
    var memoForm = document.getElementById('memoForm');
    var memo1Btn = document.getElementById('memo1Btn');
    var memo3Btn = document.getElementById('memo3Btn');

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            document.querySelectorAll('.memo-row-select').forEach(function (cb) {
                cb.checked = selectAll.checked;
            });
        });
    }

    // "Clear Selection" — unchecks every row checkbox and the
    // "select all" checkbox, without touching what's actually in the
    // cart (that's what "Clear Cart" is for).
    var clearSelectionBtn = document.getElementById('clearSelectionBtn');
    if (clearSelectionBtn) {
        clearSelectionBtn.addEventListener('click', function () {
            document.querySelectorAll('.memo-row-select').forEach(function (cb) {
                cb.checked = false;
            });
            if (selectAll) {
                selectAll.checked = false;
            }
        });
    }

    if (customerSelect) {
        customerSelect.addEventListener('change', function () {
            if (customerSelect.value === '__new__') {
                window.location.href = customerSelect.getAttribute('data-add-url');
            }
        });
    }

    function showError(message) {
        if (!errorBox) {
            return;
        }
        errorBox.textContent = message;
        errorBox.hidden = false;
        errorBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function clearError() {
        if (errorBox) {
            errorBox.hidden = true;
        }
    }

    function getSelectedIds() {
        return Array.from(document.querySelectorAll('.memo-row-select:checked')).map(function (cb) {
            return cb.value;
        });
    }

    function handleMemoClick(copyopt) {
        clearError();
        var ids = getSelectedIds();
        var customerId = customerSelect ? customerSelect.value : '';

        if (ids.length === 0) {
            showError('Please select at least one row to include on the memo.');
            return;
        }
        if (customerId === '' || customerId === '__new__') {
            showError('Please select a customer, or add a new one, before generating a memo.');
            return;
        }
        if (copyopt === 3 && ids.length > 6) {
            showError('Memo-3 (3 copies per page) is limited to 6 rows — you have selected ' + ids.length + '. Please deselect some rows, or use Memo-1 instead.');
            return;
        }

        document.getElementById('memoFormCustomerId').value = customerId;
        document.getElementById('memoFormCopyopt').value = String(copyopt);
        document.getElementById('memoFormIds').value = ids.join(',');
        memoForm.submit();
    }

    if (memo1Btn) {
        memo1Btn.addEventListener('click', function () { handleMemoClick(1); });
    }
    if (memo3Btn) {
        memo3Btn.addEventListener('click', function () { handleMemoClick(3); });
    }

    // "Copy" — available to every user (not just memo levels): sends
    // the selected rows to the server to build the formatted text
    // block, then copies that text to the clipboard.
    var copyBtn = document.getElementById('copyBtn');
    var copyForm = document.getElementById('copyForm');
    var copyStatus = document.getElementById('copyStatus');

    function showCopyStatus(message, isError) {
        if (!copyStatus) {
            return;
        }
        copyStatus.textContent = message;
        copyStatus.hidden = false;
        copyStatus.classList.toggle('copy-status-error', !!isError);
        copyStatus.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    // "Export to Excel" (View Cart page) — only exports the checked
    // rows, same selection the Copy/Markup Copy buttons use.
    var exportExcelBtn = document.getElementById('exportExcelBtn');
    if (exportExcelBtn) {
        exportExcelBtn.addEventListener('click', function (e) {
            e.preventDefault();
            var ids = getSelectedIds();
            if (ids.length === 0) {
                showCopyStatus('Please select at least one row to export.', true);
                return;
            }
            window.location.href = '?export=xlsx&ids=' + encodeURIComponent(ids.join(','));
        });
    }

    // "Excel-select" (Results page) — exports only the checked rows,
    // ignoring the current search filters entirely.
    var excelSelectBtn = document.getElementById('excelSelectBtn');
    if (excelSelectBtn) {
        excelSelectBtn.addEventListener('click', function () {
            var ids = getSelectedIds();
            if (ids.length === 0) {
                showCopyStatus('Please select at least one row to export.', true);
                return;
            }
            window.location.href = '?export=xlsx_selected&ids=' + encodeURIComponent(ids.join(','));
        });
    }

    if (copyBtn && copyForm) {
        copyBtn.addEventListener('click', function () {
            var ids = getSelectedIds();
            if (ids.length === 0) {
                showCopyStatus('Please select at least one row to copy.', true);
                return;
            }

            var formData = new FormData(copyForm);
            formData.set('ids', ids.join(','));

            copyBtn.disabled = true;
            fetch(copyForm.getAttribute('action'), { method: 'POST', body: formData })
                .then(function (resp) {
                    if (!resp.ok) {
                        throw new Error('Server returned an error.');
                    }
                    return resp.text();
                })
                .then(function (text) {
                    return copyTextToClipboard(text);
                })
                .then(function () {
                    showCopyStatus('Copied ' + ids.length + ' record' + (ids.length === 1 ? '' : 's') + ' to the clipboard.', false);
                })
                .catch(function () {
                    showCopyStatus('Could not copy to the clipboard — please try again.', true);
                })
                .finally(function () {
                    copyBtn.disabled = false;
                });
        });
    }

    /** Modern Clipboard API first; falls back to the older
     * execCommand('copy') approach (via a temporary textarea) for
     * browsers/contexts that block navigator.clipboard. */
    function copyTextToClipboard(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(text).catch(function () {
                return legacyCopy(text);
            });
        }
        return legacyCopy(text);
    }

    function legacyCopy(text) {
        return new Promise(function (resolve, reject) {
            var textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.focus();
            textarea.select();
            var ok = false;
            try {
                ok = document.execCommand('copy');
            } catch (e) {
                ok = false;
            }
            document.body.removeChild(textarea);
            if (ok) {
                resolve();
            } else {
                reject(new Error('Copy command failed.'));
            }
        });
    }

    // "Markup Copy" (View Cart page) — available to every user: reveals
    // a markup % field, then copies the whole cart's details (using the
    // same copy_generate.php endpoint and clipboard logic as the plain
    // Copy button) with that markup applied.
    var markupCopyBtn = document.getElementById('markupCopyBtn');
    var markupCopyRow = document.getElementById('markupCopyRow');
    var markupCopyInput = document.getElementById('markupCopyInput');
    var markupCopyConfirmBtn = document.getElementById('markupCopyConfirmBtn');

    if (markupCopyBtn && markupCopyRow) {
        markupCopyBtn.addEventListener('click', function () {
            markupCopyRow.hidden = !markupCopyRow.hidden;
            if (!markupCopyRow.hidden && markupCopyInput) {
                markupCopyInput.focus();
            }
        });
    }

    if (markupCopyConfirmBtn && copyForm) {
        markupCopyConfirmBtn.addEventListener('click', function () {
            var markupVal = markupCopyInput ? markupCopyInput.value.trim() : '';
            if (markupVal === '') {
                showCopyStatus('Please enter a markup percentage.', true);
                return;
            }
            var ids = getSelectedIds();
            if (ids.length === 0) {
                showCopyStatus('Please select at least one row to copy.', true);
                return;
            }

            var formData = new FormData(copyForm);
            formData.set('ids', ids.join(','));
            formData.set('mk', 'markup');
            formData.set('mv', markupVal);

            markupCopyConfirmBtn.disabled = true;
            fetch(copyForm.getAttribute('action'), { method: 'POST', body: formData })
                .then(function (resp) {
                    if (!resp.ok) {
                        throw new Error('Server returned an error.');
                    }
                    return resp.text();
                })
                .then(function (text) {
                    return copyTextToClipboard(text);
                })
                .then(function () {
                    showCopyStatus('Copied ' + ids.length + ' record' + (ids.length === 1 ? '' : 's') + ' (with markup) to the clipboard.', false);
                })
                .catch(function () {
                    showCopyStatus('Could not copy to the clipboard — please try again.', true);
                })
                .finally(function () {
                    markupCopyConfirmBtn.disabled = false;
                });
        });
    }

    // "Add to Cart" — available to every user: sends the selected
    // rows to the server, which resolves them to Stock Nos and saves
    // them against the logged-in user's email in the `selection` table.
    var addToCartBtn = document.getElementById('addToCartBtn');
    var cartForm = document.getElementById('cartForm');
    var cartStatus = document.getElementById('cartStatus');

    function showCartStatus(message, isError) {
        if (!cartStatus) {
            return;
        }
        cartStatus.textContent = message;
        cartStatus.hidden = false;
        cartStatus.classList.toggle('copy-status-error', !!isError);
        cartStatus.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    if (addToCartBtn && cartForm) {
        addToCartBtn.addEventListener('click', function () {
            var ids = getSelectedIds();
            if (ids.length === 0) {
                showCartStatus('Please select at least one row to add to the cart.', true);
                return;
            }

            var formData = new FormData(cartForm);
            formData.set('ids', ids.join(','));

            addToCartBtn.disabled = true;
            fetch(cartForm.getAttribute('action'), { method: 'POST', body: formData })
                .then(function (resp) { return resp.json().then(function (data) { return { ok: resp.ok, data: data }; }); })
                .then(function (result) {
                    if (!result.ok) {
                        throw new Error(result.data.error || 'Server returned an error.');
                    }
                    var added = result.data.added;
                    var total = result.data.total;
                    var cartCountLabel = document.getElementById('cartCountLabel');
                    if (cartCountLabel && typeof result.data.cartTotal === 'number') {
                        cartCountLabel.textContent = String(result.data.cartTotal);
                    }
                    if (added === 0) {
                        showCartStatus('Those ' + total + ' item' + (total === 1 ? ' was' : 's were') + ' already in your cart.', false);
                    } else {
                        showCartStatus('Added ' + added + ' item' + (added === 1 ? '' : 's') + ' to your cart' + (added < total ? ' (' + (total - added) + ' already there)' : '') + '.', false);
                    }
                })
                .catch(function (err) {
                    showCartStatus(err.message || 'Could not add to cart — please try again.', true);
                })
                .finally(function () {
                    addToCartBtn.disabled = false;
                });
        });
    }
})();
