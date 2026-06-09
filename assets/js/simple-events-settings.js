/**
 * Simple Events Calendar — settings page.
 *
 * Toggles the custom date-format field when "Custom" is selected. No build step;
 * plain DOM APIs.
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

        if (!radios.length || !custom) {
            return;
        }

        function sync() {
            custom.disabled = !(customRadio?.checked ?? false);
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
