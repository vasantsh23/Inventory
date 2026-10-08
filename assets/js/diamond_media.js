/**
 * diamond_media.js
 * Four thumbnails (still image / 360° / video / hand video) control
 * what shows in the main media box on the Diamond Details page.
 * Video/iframe sources are only set the first time they're actually
 * requested, so a visitor who never clicks one never loads it.
 *
 * If any media item's URL turns out to be invalid (image fails to
 * load, video has no playable source, etc.), that item is hidden
 * entirely rather than showing the browser's default broken-media
 * icon/alt text.
 */
(function () {
    // .jpg / .jpeg retry: any <img> with a data-alt-src gets one second
    // try with the other extension before it counts as broken. Runs
    // before the hide-on-error handlers below and stops the first error
    // reaching them. Also catches images that already failed before
    // this script ran.
    function isBroken(img) {
        return img.complete && img.naturalWidth === 0 && !!img.getAttribute('src');
    }
    function tryAltSrc(img) {
        var alt = img.getAttribute('data-alt-src');
        if (!alt) {
            return false;
        }
        img.removeAttribute('data-alt-src'); // only one retry
        img.setAttribute('src', alt);
        return true;
    }
    Array.prototype.forEach.call(document.querySelectorAll('img[data-alt-src]'), function (img) {
        img.addEventListener('error', function (ev) {
            if (tryAltSrc(img)) {
                ev.stopImmediatePropagation();
            }
        }, true); // capture, so it runs before the hide handler below
        if (isBroken(img)) {
            tryAltSrc(img);
        }
    });

    var items = [
        { thumb: 'ddThumbImage', content: 'ddMediaImage', kind: 'img' },
        { thumb: 'ddThumbVideo', content: 'ddMediaFrame', kind: 'iframe' },
        { thumb: 'ddThumbVideo2', content: 'ddMediaVideo2', kind: 'video' },
        { thumb: 'ddThumbHandVideo', content: 'ddMediaHandVideo', kind: 'video' }
    ].map(function (item) {
        return {
            thumb: document.getElementById(item.thumb),
            content: document.getElementById(item.content),
            kind: item.kind
        };
    }).filter(function (item) {
        return item.thumb && item.content;
    });

    if (items.length === 0) {
        return;
    }

    function showItem(target) {
        items.forEach(function (item) {
            var isTarget = item === target;
            item.content.hidden = !isTarget;
            item.thumb.classList.toggle('is-active', isTarget);
            var url = item.thumb.getAttribute('data-media-url');
            // No data-media-url = "No media found" placeholder: just show it.
            if (isTarget && url && item.kind !== 'img' && !item.content.getAttribute('src')) {
                // Load the source only after this item is visible and has
                // been given a layout pass — some embedded players/videos
                // measure their container once on load and never recover
                // if that happened while display:none (0x0 size).
                requestAnimationFrame(function () {
                    requestAnimationFrame(function () {
                        item.content.setAttribute('src', url);
                        if (item.kind === 'video') {
                            item.content.load();
                        }
                    });
                });
            }
        });
    }

    items.forEach(function (item) {
        item.thumb.addEventListener('click', function () {
            showItem(item);
        });

        // Broken URL handling: hide the item (thumbnail + content)
        // instead of showing a broken-image icon or "no video" state.
        var errorTarget = item.kind === 'iframe' ? null : item.content;
        if (errorTarget) {
            errorTarget.addEventListener('error', function () {
                item.thumb.hidden = true;
                item.content.hidden = true;
            }, true);
            // The still image may have failed (both .jpg and .jpeg)
            // before this script ran, so its error event was missed.
            if (item.kind === 'img' && isBroken(item.content) && !item.content.getAttribute('data-alt-src')) {
                item.thumb.hidden = true;
                item.content.hidden = true;
            }
        }
    });
})();
