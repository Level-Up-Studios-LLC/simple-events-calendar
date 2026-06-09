/**
 * Simple Events Calendar — admin edit screen.
 *
 * Conditional show/hide of the recurrence fields, plus a live plain-English
 * summary of the recurrence rule. No build step; plain DOM APIs.
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
        const box = document.querySelector('.simple-events-meta-box');
        if (!box) {
            return;
        }

        const repeats = box.querySelector('[data-sec-toggle="recur"]');
        const group = box.querySelector('[data-sec-recur-group]');
        const endType = box.querySelector('[data-sec-toggle="end-type"]');
        const countRow = box.querySelector('[data-sec-end="count"]');
        const untilRow = box.querySelector('[data-sec-end="until"]');
        const summary = box.querySelector('[data-sec-summary]');
        const interval = box.querySelector('#sec_event_repeat_interval');
        const frequency = box.querySelector('#sec_event_repeat_frequency');
        const count = box.querySelector('#sec_event_repeat_count');
        const until = box.querySelector('#sec_event_repeat_until');
        const bydayRow = box.querySelector('[data-sec-byday]');
        const dayBoxes = box.querySelectorAll('input[name="sec_event_repeat_byday[]"]');
        const presets = box.querySelectorAll('[data-sec-preset]');
        const L = typeof secMetaBox !== 'undefined' ? secMetaBox : null;

        function show(el, visible) {
            if (el) {
                el.style.display = visible ? '' : 'none';
            }
        }

        function fmt(str, val) {
            return String(str).replace('%d', val).replace('%s', val);
        }

        const PRESETS = { weekdays: [1, 2, 3, 4, 5], weekend: [0, 6], all: [0, 1, 2, 3, 4, 5, 6] };

        function applyPreset(name) {
            const want = PRESETS[name] || [];
            dayBoxes.forEach((cb) => {
                cb.checked = want.includes(Number.parseInt(cb.value, 10));
            });
        }

        function selectedDays() {
            // Preserve DOM order — the checkboxes are rendered in the site's
            // start_of_week order, so the summary reads e.g. "Sat, Sun" on a
            // Monday-start site rather than re-sorting to "Sun, Sat".
            const days = [];
            dayBoxes.forEach((cb) => {
                if (cb.checked) { days.push(Number.parseInt(cb.value, 10)); }
            });
            return days;
        }

        function buildSummary() {
            if (!summary || !L) {
                return;
            }
            const parts = [];
            const n = Math.max(1, Number.parseInt(interval?.value, 10) || 1);
            const freqKey = frequency?.value ?? 'weekly';
            const unit = L.units?.[freqKey] || freqKey;
            parts.push(`${L.every} ${n > 1 ? `${n} ` : ''}${unit}`);

            if (freqKey === 'weekly' && L.onDays && L.dayNames) {
                const days = selectedDays();
                if (days.length) {
                    const names = days.map((d) => L.dayNames[d] || d);
                    parts[parts.length - 1] += ` ${L.onDays.replace('%s', names.join(', '))}`;
                }
            }

            const et = endType?.value ?? 'count';
            if (et === 'count') {
                const c = Math.max(1, Number.parseInt(count?.value, 10) || 1);
                parts.push(fmt(c === 1 ? L.countOne : L.countMany, c));
            } else if (et === 'until' && until?.value) {
                parts.push(fmt(L.until, until.value));
            } else if (et === 'never') {
                parts.push(L.never);
            }

            summary.textContent = parts.join(L.sep || ' · ');
        }

        function sync() {
            const on = repeats?.checked ?? false;
            show(group, on);

            const weekly = frequency?.value === 'weekly';
            show(bydayRow, on && weekly);

            if (on && endType) {
                const value = endType.value;
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
        if (frequency) { frequency.addEventListener('change', sync); }
        box.querySelectorAll('[data-sec-summary-input]').forEach((el) => {
            el.addEventListener('input', buildSummary);
            el.addEventListener('change', buildSummary);
        });

        presets.forEach((btn) => {
            btn.addEventListener('click', () => {
                applyPreset(btn.getAttribute('data-sec-preset'));
                buildSummary();
            });
        });

        sync();
    });
})();
