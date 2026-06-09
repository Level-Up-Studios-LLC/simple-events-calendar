/**
 * Simple Events Calendar — settings page.
 *
 * Toggles the custom date-format field when "Custom" is selected and keeps the
 * live preview roughly in sync. No build step; plain DOM APIs.
 */
(() => {
    'use strict';

    function ready(fn) {
        if (document.readyState !== 'loading') {
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    }

    ready(() => {
        const radios = document.querySelectorAll('input[name$="[date_format_preset]"]');
        const custom = document.getElementById('sec-date-format-custom');
        const customRadio = document.getElementById('sec-date-format-custom-radio');
        const preview = document.getElementById('sec-date-format-preview');

        if (!radios.length || !custom) {
            return;
        }

        function activeFormat() {
            if (customRadio?.checked) {
                return custom.value;
            }
            const checked = document.querySelector('input[name$="[date_format_preset]"]:checked');
            return checked ? checked.value : '';
        }

        // Lightweight client-side preview for the common single-letter tokens.
        // Escape sequences and less-common tokens are not fully handled; the
        // server renders the authoritative preview on save — this is just a hint.
        function previewFor(fmt) {
            const d = new Date();
            const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
            const pad = (n) => `${n < 10 ? '0' : ''}${n}`;
            const map = {
                l: days[d.getDay()],
                D: days[d.getDay()].slice(0, 3),
                F: months[d.getMonth()],
                M: months[d.getMonth()].slice(0, 3),
                j: d.getDate(),
                d: pad(d.getDate()),
                n: d.getMonth() + 1,
                m: pad(d.getMonth() + 1),
                Y: d.getFullYear(),
                y: String(d.getFullYear()).slice(-2),
            };
            return String(fmt).replace(/\\?([a-zA-Z])/g, (match, ch) => {
                if (match.charAt(0) === '\\') { return ch; }
                return Object.hasOwn(map, ch) ? map[ch] : ch;
            });
        }

        function sync() {
            const isCustom = customRadio?.checked ?? false;
            custom.disabled = !isCustom;
            if (preview) {
                preview.textContent = previewFor(activeFormat());
            }
        }

        for (const radio of radios) {
            radio.addEventListener('change', () => {
                if (customRadio && radio === customRadio) {
                    custom.focus();
                }
                sync();
            });
        }

        custom.addEventListener('input', sync);
        sync();
    });
})();
