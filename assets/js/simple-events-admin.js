/**
 * Simple Events Calendar — admin edit screen.
 *
 * Conditional show/hide of the recurrence fields, plus a live plain-English
 * summary of the recurrence rule. No build step; plain DOM APIs.
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
        var summary = box.querySelector('[data-sec-summary]');
        var interval = box.querySelector('#sec_event_repeat_interval');
        var frequency = box.querySelector('#sec_event_repeat_frequency');
        var count = box.querySelector('#sec_event_repeat_count');
        var until = box.querySelector('#sec_event_repeat_until');
        var L = (typeof secMetaBox !== 'undefined') ? secMetaBox : null;

        function show(el, visible) {
            if (el) {
                el.style.display = visible ? '' : 'none';
            }
        }

        function fmt(str, val) {
            return String(str).replace('%d', val).replace('%s', val);
        }

        function buildSummary() {
            if (!summary || !L) {
                return;
            }
            var parts = [];
            var n = Math.max(1, parseInt(interval && interval.value, 10) || 1);
            var freqKey = frequency ? frequency.value : 'weekly';
            var unit = (L.units && L.units[freqKey]) ? L.units[freqKey] : freqKey;
            parts.push(L.every + ' ' + (n > 1 ? n + ' ' : '') + unit);

            var et = endType ? endType.value : 'count';
            if (et === 'count') {
                var c = Math.max(1, parseInt(count && count.value, 10) || 1);
                parts.push(fmt(c === 1 ? L.countOne : L.countMany, c));
            } else if (et === 'until' && until && until.value) {
                parts.push(fmt(L.until, until.value));
            } else if (et === 'never') {
                parts.push(L.never);
            }

            summary.textContent = parts.join(L.sep || ' · ');
        }

        function sync() {
            var on = repeats && repeats.checked;
            show(group, on);

            if (on && endType) {
                var value = endType.value;
                show(countRow, value === 'count');
                show(untilRow, value === 'until');
            }
            if (on) {
                buildSummary();
            } else if (summary) {
                summary.textContent = '';
            }
        }

        if (repeats) { repeats.addEventListener('change', sync); }
        if (endType) { endType.addEventListener('change', sync); }
        box.querySelectorAll('[data-sec-summary-input]').forEach(function (el) {
            el.addEventListener('input', buildSummary);
            el.addEventListener('change', buildSummary);
        });

        sync();
    });
})();
