/**
 * Simple Events Calendar — settings page.
 *
 * Toggles the custom date-format field when "Custom…" is selected and keeps the
 * live preview roughly in sync. No build step; plain DOM APIs.
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
        var preset = document.getElementById('sec-date-format-preset');
        var wrap = document.getElementById('sec-date-format-custom-wrap');
        var custom = document.getElementById('sec-date-format-custom');
        var preview = document.getElementById('sec-date-format-preview');
        if (!preset || !wrap) {
            return;
        }

        function activeFormat() {
            if (preset.value === 'custom') {
                return custom ? custom.value : '';
            }
            return preset.value;
        }

        // Lightweight client-side preview for the common single-letter tokens.
        // Escape sequences and less-common tokens are not fully handled; the
        // server renders the authoritative preview on save — this is just a hint.
        function previewFor(fmt) {
            var d = new Date();
            var days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            var months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
            var pad = function (n) { return (n < 10 ? '0' : '') + n; };
            var map = {
                l: days[d.getDay()],
                D: days[d.getDay()].slice(0, 3),
                F: months[d.getMonth()],
                M: months[d.getMonth()].slice(0, 3),
                j: d.getDate(),
                d: pad(d.getDate()),
                n: d.getMonth() + 1,
                m: pad(d.getMonth() + 1),
                Y: d.getFullYear(),
                y: String(d.getFullYear()).slice(-2)
            };
            return String(fmt).replace(/\\?([a-zA-Z])/g, function (match, ch) {
                if (match.charAt(0) === '\\') { return ch; }
                return Object.prototype.hasOwnProperty.call(map, ch) ? map[ch] : ch;
            });
        }

        function sync() {
            var isCustom = preset.value === 'custom';
            wrap.style.display = isCustom ? '' : 'none';
            if (preview) {
                preview.textContent = previewFor(activeFormat());
            }
        }

        preset.addEventListener('change', sync);
        if (custom) {
            custom.addEventListener('input', sync);
        }
        sync();
    });
})();
