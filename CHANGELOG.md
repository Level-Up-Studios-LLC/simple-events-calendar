# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [v4.4.0] (2026-05-28)

### Added

* **Recurring events**: events can now repeat every N days, weeks, months, or years from the event edit screen. Recurrence settings live in the existing **Event Details** ACF field group (`event_repeats`, `event_repeat_frequency`, `event_repeat_interval`, `event_repeat_end_type`, `event_repeat_count`, `event_repeat_until`) and use ACF conditional logic to show/hide.
* End conditions: **after a number of occurrences**, **on a specific date**, or **never** (with a rolling horizon refilled daily via WP-Cron, capped at 60 months out per series).
* New `Simple_Events_Recurrence` class manages generation, edit-scope propagation, cascade hooks, and the horizon cron. Wired into `Simple_Events_Calendar::load_components()` and exposed at `simple_events_calendar()->recurrence`.
* Per-occurrence editing: when editing a child event, a sidebar **Series Edit Scope** metabox offers *only this occurrence*, *this and future occurrences*, or *entire series*. Edits are tracked per-field via `_sec_field_overrides` so a series-wide change of (e.g.) start time doesn't blow away a previously-customized title.
* New admin **Series** column on the events list table indicates parents (`Weekly series (+N)`) and children (`Occurrence #N (parent)`).
* New helper functions in `includes/functions.php`: `simple_events_is_series_parent()`, `simple_events_is_series_child()`, `simple_events_get_series_parent_id()`.
* New public filters (the plugin's first extensibility hooks): `sec_recur_max_occurrences` (1000), `sec_recur_max_horizon_months` (60), `sec_recur_sync_batch_size` (50), `sec_recur_horizon_refill_threshold_months` (6), `sec_recur_horizon_extend_months` (18), `sec_recur_copyable_field_keys`.

### Storage model

Each occurrence is a real `simple-events` post with its own `event_date`, so the shortcode, archive, AJAX load-more, and admin filters require no changes. The parent post is occurrence #0; children carry `_sec_series_parent`, `_sec_series_occurrence_index`, and `_sec_field_overrides`. The rule itself is stored as `_sec_recur_*` post meta on the parent.

### Behavioral notes

* **Disabling recurrence** on a saved series only force-deletes FUTURE unmodified live children. Past, edited (per-occurrence override), or trashed children get detached (series meta cleared) and survive as standalone events, so history isn't destroyed. An admin notice surfaces the counts on the next edit-screen load.
* **Trashing a parent** cascade-trashes its unmodified children and detaches modified ones; **restoring a parent from trash** restores those cascade-trashed children.
* **Force-deleting a parent** cascade-deletes unmodified children (including any sitting in trash) and detaches modified ones. **Force-deleting a child** records its index in `_sec_recur_skipped_indexes` on the parent so the next regeneration won't recreate it.
* **Large series** (more than `sec_recur_sync_batch_size`) are generated in the foreground up to the limit, then continued in 5-second-spaced batches via `wp_schedule_single_event('sec_recur_continue_generation')`. An admin notice on the parent edit screen reports progress.
* **DST and month-end math**: date arithmetic uses `DateTimeImmutable` + `wp_timezone()` so daily/weekly intervals don't drift across DST transitions. Monthly recurrences are anchored to the parent's day-of-month and clamp to the last day of the target month when the original day doesn't exist there (Jan 31 → Feb 28 → Mar 31). Feb 29 yearly recurrences clamp to Feb 28 in non-leap years.
* **Concurrency**: a per-parent **atomic option-row lock** (`sec_recur_lock_<parent_id>` acquired via `add_option(..., '', 'no')` so the options-table INSERT IGNORE provides atomicity across concurrent requests) plus a `$GLOBALS['sec_generating_series']` recursion guard prevent concurrent regenerations from double-creating occurrences and prevent each cascaded child's save / delete from re-entering the parent's handler. A stale-lock check (older than 60 s) clears and re-acquires once to recover from processes that died mid-flight.

### Changed

* Bumped minimum tested WordPress version metadata to track the new release.

## [v4.3.1] (2026-04-22)

### Security

* Converted AJAX `load_more_events` endpoint to `wp_send_json_success` / `wp_send_json_error` with a `{ html, has_more }` payload; JS updated in lockstep to consume the JSON shape and surface server error messages
* Added `wp_unslash` + `sanitize_text_field` to nonce and admin `$_GET` reads (`event_status`, `simple-events-cat`)
* Whitelist-validated the admin `event_status` filter value before it reaches the query
* Escaped all `<option>` output in admin filter dropdowns

### Fixed

* Fixed asset cache-busting: `$version` was hardcoded to `3.0.0` while `PLUGIN_VERSION` was `4.3.0`, so browsers served stale CSS/JS after upgrades
* Fixed `modify_archive_query` meta_query merge: existing `relation` keys and nested clauses from other plugins are now preserved by nesting under a fresh `AND` wrapper instead of flattening with `array_merge`
* Guarded `init()` against double-execution (it hooks both `plugins_loaded` and `acf/init`) to prevent duplicate hook registrations
* Moved `load_plugin_textdomain` to the `init` hook to silence the WordPress 6.7+ `_doing_it_wrong` notice
* AJAX-loaded event cards now render the footer "Learn More" link to match the shortcode output

### Changed

* Shortcode transient cache key now mixes in `PLUGIN_VERSION` and `is_user_logged_in()` so caches auto-invalidate on upgrade and admin/anon variations stay isolated
* Introduced `SIMPLE_EVENTS_NONCE_ACTION` constant to replace the hardcoded nonce string across call sites (existing nonce value preserved for upgrade safety)
* Extracted duplicated fallback event-card markup into `simple_events_render_fallback_card()` shared between the shortcode and AJAX handlers

### Removed

* Removed the empty `render_load_more_hint()` method stub

## [v4.3.0] (2024-09-22)

### Added

* **Multilingual Support**: Added complete translation support for Spanish (es_ES) and French (fr_FR)
* Added professional-quality translations for all plugin strings
* Added .po and .mo files for Spanish and French languages
* Added internationalization documentation and setup guide

### Changed

* Updated .pot file with current version and creation date
* Enhanced plugin description to highlight multilingual capabilities
* Updated documentation to include translation information

## [v4.2.4] (2024-09-22)

### Added

* Added SCSS build system with Sass compiler and stylelint
* Added development and production CSS build scripts (`npm run build:css`, `npm run build:css:dev`)
* Added file watching capabilities (`npm run watch`, `npm run watch:css`)
* Added CSS linting with stylelint and standard SCSS configuration
* Added automatic version synchronization between plugin header, constants, and package.json

### Changed

* **BREAKING**: Updated shortcode from `[simple_events_calendar]` to `[sec_events]` for consistency
* Improved responsive design with better media query organization
* Enhanced theme color inheritance for better integration with WordPress themes
* Refactored CSS build process from simple file copying to proper SCSS compilation
* Updated build system to generate both compressed (production) and expanded (development) CSS
* Improved accessibility features and reduced motion support
* Updated package.json version management and build scripts

### Fixed

* Fixed plugin description to clearly specify ACF® requirement
* Improved WordPress version compatibility requirements
* Enhanced color definitions and margin adjustments for better layout consistency
* Fixed media query structure for improved readability and maintainability

### Development

* Added source maps for development builds
* Added proper CSS minification for production builds
* Added automated distribution and zip creation process
* Enhanced .claude-instructions with comprehensive version management guidelines

## [v4.1.1] (2024-09-22)

### Removed

* Removed "Showing current and upcoming events only." message from shortcode display
* Removed scroll hint bar "📜 Scroll down to see more events..." from after events
* Events now load automatically on scroll without instructional messages

## [v4.1.0] (2024-09-22)

### Improved

* Enhanced ACF dependency error message for better user experience
* Error message now shows "Simple Events Calendar requires Advanced Custom Fields (ACF) plugin to be installed and activated"
* Added direct download link button "Download ACF Free Plugin" to WordPress plugin installer
* Cleaner, more actionable error messages for missing dependencies

## [v4.0.3] (2024-09-22)

### Fixed

* Fixed duplicate content appearing in admin columns (thumbnail, date, time, location)
* Added static tracking to prevent `fill_columns` method from processing same column/post multiple times
* Eliminated stacked duplicate content within individual admin columns

## [v4.0.2] (2024-09-22)

### Fixed

* Fixed duplicate admin columns by completely overriding WordPress default columns
* Prevented WordPress from showing automatic thumbnail, content, and excerpt columns
* Admin columns now display only custom event-specific columns without duplicates

## [v4.0.1] (2024-09-22)

### Fixed

* Fixed duplicate thumbnail display in admin columns by removing default WordPress thumbnail column
* Admin now shows only custom event thumbnail without WordPress default thumbnail

## [v4.0.0] (2024-09-22)

### Added

* Updated WordPress minimum requirement to 6.2+ for better block editor support
* Updated PHP minimum requirement to 8.0+ (PHP 7.4 is end-of-life)
* Added build system with `npm run dist` and `npm run zip` commands
* Added compressed file exclusions to .gitignore
* Added automated semantic versioning instructions

### Changed

* **BREAKING**: Increased minimum WordPress and PHP requirements
* Refactored plugin architecture with proper class-based structure
* Improved admin columns functionality with better duplicate prevention

## [v3.0.0] (2024-08-20)

### Added

* Responsive grid layout with device-specific columns (3 cols desktop, 2 cols tablet, 1 col mobile)
* Custom gap spacing for different screen sizes (80px large displays, 40px laptops, 30px tablets/mobile)
* 4:3 aspect ratio for featured images with responsive design
* Event status filtering in admin (All Events, Upcoming, Today's Events, Past Events)
* Location field with visual indicators and proper styling
* Comprehensive ACF dependency checking with detailed error messages
* Scroll hints and loading animations for better user experience
* Friendly "no more events" message with encouraging text and emojis
* Enhanced event card design with hover effects and modern styling
* Meta information display with styled date badges and time indicators

### Changed

* Updated initial event display from 9 to 6 events with 6-event loading increments
* Improved infinite scroll with better error handling and user feedback
* Enhanced admin interface with better column management and sorting (always ASC by date)
* Event cards now use flexbox layout for consistent heights across grid
* Date format standardized to 'Ymd' for reliable database comparisons
* Archive pages now automatically filter out past events (frontend only)
* ACF field registration moved to programmatic creation via PHP
* Improved responsive design with device-specific adjustments
* Better accessibility with focus states and reduced motion support

### Fixed

* Infinite scroll error handling - now shows proper "no more events" message instead of server errors
* Past events filtering - properly hides past events on frontend while keeping them accessible in admin
* Date comparison issues by using WordPress timezone (`current_time()`) instead of server time
* ACF Pro detection reliability using `acf_get_setting()` function
* Event ordering consistency - all queries now use ASC order by event date
* Template fallback handling when event card template is missing

### Removed

* All debugging code and console logging from production files
* Dependency on JSON field group files (now uses PHP registration)
* Unnecessary caching that could interfere with real-time event filtering
* Legacy ACF bundling code and restrictive activation logic

## [v2.1.2] (2024-05-15)

### Changed

- Updated the meta WP query from the shortcode and ajax file to display future events by just their date and not including their end time.

## [v2.1.1] (2024-05-15)

### Fixed

- Fixed an issue with the TIME meta query from the shortcode ajax files that caused the events not to display.
- Removed a var_dump PHP function that was forgotten on the shortcode file.

## [v2.1.0] (2024-05-06)

### Added

- Added CHANGELOG.md

### Changed

- Updated the WP_QUERY arguments to display events by future date and before their end time, if provided.
- Made some adjustments to the core files.
- Updated the required PHP version to 7.4 from 8.1
- Updated the required WordPress version to 6.0 from 6.2

### Removed

- Removed transient cache code.

## v2.0.0 (2024-05-02)

### Added

- Added LICENSE
- Added README.md
- Added .gitignore

### Changed

- Updated CSS file
- Updated the "No more events" message from the `simple-events-shortcode.php` file.

[v4.4.0]: https://github.com/Level-Up-Studios-LLC/simple-events-calendar/compare/v4.3.1...v4.4.0
[v4.3.1]: https://github.com/Level-Up-Studios-LLC/simple-events-calendar/compare/v4.3.0...v4.3.1
[v4.3.0]: https://github.com/Level-Up-Studios-LLC/simple-events-calendar/compare/v4.2.4...v4.3.0
[v4.2.4]: https://github.com/Level-Up-Studios-LLC/simple-events-calendar/compare/v4.1.1...v4.2.4
[v4.1.1]: https://github.com/Level-Up-Studios-LLC/simple-events-calendar/compare/v4.1.0...v4.1.1
[v4.1.0]: https://github.com/Level-Up-Studios-LLC/simple-events-calendar/compare/v4.0.3...v4.1.0
[v4.0.3]: https://github.com/Level-Up-Studios-LLC/simple-events-calendar/compare/v4.0.2...v4.0.3
[v4.0.2]: https://github.com/Level-Up-Studios-LLC/simple-events-calendar/compare/v4.0.1...v4.0.2
[v4.0.1]: https://github.com/Level-Up-Studios-LLC/simple-events-calendar/compare/v4.0.0...v4.0.1
[v4.0.0]: https://github.com/Level-Up-Studios-LLC/simple-events-calendar/compare/v3.0.0...v4.0.0
[v3.0.0]: https://github.com/Level-Up-Studios-LLC/simple-events-calendar/compare/v2.1.2...v3.0.0
[v2.1.2]: https://github.com/Level-Up-Studios-LLC/simple-events-calendar/compare/v2.1.1...v2.1.2
[v2.1.1]: https://github.com/Level-Up-Studios-LLC/simple-events-calendar/compare/v2.1.0...v2.1.1
[v2.1.0]: https://github.com/Level-Up-Studios-LLC/simple-events-calendar/releases/tag/v2.1.0
