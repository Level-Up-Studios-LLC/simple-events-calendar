/**
 * Simple Events Calendar — admin edit screen.
 *
 * Reproduces the conditional show/hide behavior of the recurrence fields that
 * ACF previously provided. No build step; plain DOM APIs.
 */
(function () {
    'use strict';

    function ready(fn) {
        if (document.readyState !== 'loading') {
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    }

    ready(function () {
        var box = document.querySelector('.simple-events-meta-box');
        if (!box) {
            return;
        }

        var repeats = box.querySelector('[data-sec-toggle="recur"]');
        var group = box.querySelector('[data-sec-recur-group]');
        var endType = box.querySelector('[data-sec-toggle="end-type"]');
        var countRow = box.querySelector('[data-sec-end="count"]');
        var untilRow = box.querySelector('[data-sec-end="until"]');

        function show(el, visible) {
            if (el) {
                el.style.display = visible ? '' : 'none';
            }
        }

        function sync() {
            var on = repeats && repeats.checked;
            show(group, on);

            if (on && endType) {
                var value = endType.value;
                show(countRow, value === 'count');
                show(untilRow, value === 'until');
            }
        }

        if (repeats) {
            repeats.addEventListener('change', sync);
        }
        if (endType) {
            endType.addEventListener('change', sync);
        }

        sync();
    });
})();
