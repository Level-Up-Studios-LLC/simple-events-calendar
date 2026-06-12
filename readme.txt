=== Simple Events Calendar ===
Contributors: levelupstudios
Donate link: https://www.levelupstudios.com/
Tags: events, calendar, shortcode, responsive, elementor
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 5.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A simple, responsive events calendar for WordPress. Create one-time or recurring events and display them with shortcodes or Elementor.

== Description ==

Simple Events Calendar is a lightweight, user-friendly plugin that creates beautiful, responsive event displays on your WordPress site. Easily create one-time or recurring events and display them anywhere with shortcodes, Elementor widgets, and one-click Add to Calendar — all managed through a simple, built-in Event Details editor.

= Key Features =

* **Responsive Design**: Automatically adapts to different screen sizes (3 columns on desktop, 2 on tablet, 1 on mobile)
* **No external dependencies**: Native event fields — ACF is no longer required
* **Settings page**: Choose the front-end date/time format, display defaults, cache lifetime, and more
* **Default templates**: Built-in single, archive, and category templates (theme-, Elementor-, and block-theme-overridable)
* **Single Event shortcode**: `[sec_event id="123"]` — display one event anywhere in card or image-left list layout
* **Modular element shortcodes**: `[sec_event_title]`, `[sec_event_image]`, `[sec_event_date]`, and more for custom layouts
* **Elementor display widgets**: Events Grid (grid or list, configurable columns, optional infinite scroll) and Single Event widgets usable on any page; per-element widgets for event templates and archives
* **Weekly by-day recurrence**: Recurring events can repeat on specific weekdays, weekdays only, or weekends only — with quick presets and a live plain-English summary
* **Infinite Scroll Loading**: Events load smoothly as users scroll down
* **Flexible Display Options**: Control what event information to show (time, excerpt, location, etc.)
* **Event Status Filtering**: Filter events by upcoming, today's events, or past events in admin
* **SEO**: schema.org Event structured data on cards and single event pages
* **In-plugin Documentation**: Events → Documentation page listing all shortcodes and Elementor widgets
* **Opt-in data management**: Choose whether to keep or delete event data when the plugin is uninstalled (default: keep)
* **Accessibility Ready**: Built with accessibility best practices and reduced motion support

= Shortcode Usage =

Display events anywhere on your site with the simple shortcode:

`[sec_events]`

= Shortcode Parameters =

* `posts_per_page` - Number of events to load initially (default: from Settings, 6)
* `category` - Filter by event-category slug (default: none)
* `show_past` - Include past events (default: no)
* `order` - Sort direction ASC/DESC (default: ASC)
* `show_time` - Display event time (default: true)
* `show_excerpt` - Display event excerpt (default: true)
* `show_location` - Display event location (default: true)
* `show_footer` - Display event footer (default: true)

Defaults for the options above come from the Events → Settings page; shortcode attributes override them per instance.

Example: `[sec_events posts_per_page="9" show_time="false"]`

= Element Shortcodes =

For building custom layouts (including inside page builders), each event element has its own shortcode. They default to the current event and accept an `id` attribute to target a specific event:

`[sec_event_title]`, `[sec_event_image]`, `[sec_event_date]`, `[sec_event_time]`, `[sec_event_location]`, `[sec_event_excerpt]`, `[sec_event_content]`, `[sec_event_categories]`, `[sec_event_button]`

= Single Event Shortcode =

Display one specific event anywhere on your site:

`[sec_event id="123"]`

* `id` — (required) the post ID of the event to display
* `layout` — `card` (default) or `list` (featured image on the left, details on the right)
* `show_time`, `show_excerpt`, `show_location`, `show_footer` — same as `[sec_events]`, defaults from Settings

Example: `[sec_event id="42" layout="list" show_excerpt="no"]`

= Elementor =

When Elementor is active, a "Simple Events" widget category (listed just below "Basic") provides:

* **Events Grid** — display a configurable grid or image-left list of events on any page. Controls include layout, column count, number of events, category filter, sort order, show-past toggle, and show_time / show_excerpt / show_location / show_footer toggles. An optional "Load more on scroll" toggle enables infinite scroll.
* **Single Event** — render one event (chosen from a searchable list) anywhere on your site; supports card or list layout.
* **Per-element widgets** (Event Title, Image, Date, Time, Location, Excerpt, Content, Categories, Button) — intended for use inside a single-event template, event archive template, or Elementor Loop Grid bound to events. They preview a sample event in the Elementor editor but render nothing on the front end outside an event context.
* **Dynamic Tags** — bind native Elementor widgets to event fields.

To sort an Elementor **Loop Grid** of events by their event date (instead of the post date), set the Loop Grid's Query ID to "sec_events_by_date" (follows the global "Show past events" setting) or "sec_events_by_date_all" (always includes past). No code snippet required.

= No Required Plugins =

This plugin is fully self-contained. Previous versions required Advanced Custom Fields; version 5.0.0 removed that dependency. Existing events created with earlier versions continue to work unchanged — their data is preserved and no migration is needed. ACF can be safely deactivated.

= Developer Features =

* SCSS build system with Sass compiler
* CSS linting with stylelint
* File watching for development
* Automated distribution creation
* Semantic versioning support

= Multilingual Support =

The plugin includes translations for:
* English (default)
* Spanish (es_ES)
* French (fr_FR)

Additional languages can be added using standard WordPress translation methods.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/simple-events-calendar` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the Simple Events Calendar plugin through the 'Plugins' screen in WordPress.
3. Start creating events in your WordPress admin under "Events" — event date, time, and location are entered in the "Event Details" box.
4. Optionally adjust formatting and defaults under Events → Settings.
5. Use the `[sec_events]` shortcode to display events on any page or post.

== Frequently Asked Questions ==

= Do I need Advanced Custom Fields Pro? =

No. Advanced Custom Fields is not required and has not been required since v5.0.0. The plugin is fully self-contained — event date, time, location, and recurrence are all managed through a built-in "Event Details" meta box. Existing events created with earlier ACF-based versions are preserved with no migration needed; ACF can be safely deactivated or removed.

= Can I customize the event display? =

Yes, the plugin includes several shortcode parameters to control what information is displayed. You can also customize the styling through your theme's CSS.

= How many events are displayed by default? =

The plugin displays 6 events initially and loads 6 more events each time the user scrolls to the bottom (infinite scroll).

= Can I show past events? =

Past events are hidden on the frontend by default but remain accessible in the WordPress admin. The plugin automatically filters to show only current and upcoming events to site visitors.

= Is the plugin responsive? =

Yes, the plugin is fully responsive and adapts to different screen sizes automatically.

== Screenshots ==

1. Event display with responsive grid layout
2. WordPress admin events list with custom columns
3. Event creation form with ACF fields
4. Shortcode parameters and usage examples

== Changelog ==

= 5.2.0 (2026-06-11) =

**Added**
* A **Pro upsell area** in the admin: a dismissible "Upgrade to Pro" banner on the Events → Settings and Events → Documentation pages, an "Available in Pro" preview of upcoming premium features on the Settings page, and an **Upgrade to Pro** link under the Events menu.

**Notes**
* This update only adds in-admin information about the upcoming Pro version — it makes no changes to your events, settings, or anything visitors see on the front end. The banner can be dismissed per user.

= 5.1.0 (2026-06-08) =

**Added**
* Single-event shortcode `[sec_event id="123" layout="card|list"]` — display one event anywhere on your site in card or image-left list layout
* Elementor **Events Grid** widget — grid or image-left list of events usable on any page; configurable column count, category/order/show-past filters, and optional "Load more on scroll" infinite scroll
* Elementor **Single Event** widget — render one event (chosen from a searchable list) anywhere; card or list layout
* **Weekly by-day recurrence** — recurring weekly events can now target specific weekdays (S–M–T–W–T–F–S picker) with **Weekdays**, **Weekend**, and **Every day** presets and a live plain-English recurrence summary
* **Documentation page** at Events → Documentation listing all shortcodes and Elementor widgets
* **Opt-in data deletion on uninstall** (Events → Settings → Data): default keeps all events, categories, and settings on uninstall; only an explicit choice deletes them — deletion never happens on deactivation
* **Redesigned single event page** with a sticky "Event Details" card (date, time, location, categories) and a working **Add to Calendar** button that downloads a universal .ics file (Apple Calendar, Outlook, Google Calendar import)
* **Elementor Loop Grid sorting by event date** — set a Loop Grid's Query ID to "sec_events_by_date" (follows the global Show past events setting) or "sec_events_by_date_all" (always includes past) to order events by their event date instead of the post date; no code snippet required

**Changed**
* Event cards restyled: 5 px corner radius, softer drop shadow, light-gray border, 22 px event title; grid thumbnails changed from 4:3 to **3:2** aspect ratio; new image-left **list layout**
* Event Details meta box redesigned with sectioned layout, field icons, "Recurring" pill, and a live recurrence summary
* Date format setting now uses preset radio buttons + a Custom field (matching WordPress's General Settings style); default sort order setting now uses radio buttons
* Empty-state "no events" message is now a built-in translatable string; the editable empty-state settings fields were removed
* Per-element Elementor widgets (Title/Image/Date/Time/Location etc.) are now gated to event-loop contexts — they preview a sample event in the editor but render nothing on an ordinary page on the front end
* Front-end stylesheet and script are now registered for on-demand loading so the display widgets and `[sec_event]` shortcode work on any page
* Single event and archive views now use your Elementor Pro Theme Builder template when one is assigned to events (the plugin's default template is the fallback when no Elementor template exists)
* Front-end and admin JavaScript modernized to ES2023 syntax (no build step; runs natively in current browsers). No change in behavior.

**Fixed**
* AJAX "load more" event cards were missing time, excerpt, location, and "Learn More" link — fixed a show-flag type mismatch between the AJAX path and the card template
* Infinite scroll could fail to trigger when scrolling straight to the bottom and stopping — now re-checks position on the trailing edge
* Several code-review fixes: Elementor event-ID fallback guarded against term-ID collisions on taxonomy archives; weekday picker checkboxes given full names for screen readers; Events Grid columns stack correctly to 2/1 on tablet/mobile

**Compatibility**
* Events from much older versions (stored under the `events` post type / `events-cat` taxonomy) are automatically migrated to `simple-events` / `simple-events-cat` on upgrade, so they keep working. Times stored in 24-hour `H:i:s` form are now read and edited correctly too. **Back up your database and test on a staging site before upgrading production.**

= 5.0.0 (2026-05-29) =

**Removed**
* Advanced Custom Fields dependency — the plugin is now fully self-contained

**Added**
* Native "Event Details" meta box for editing date, time, location, and recurrence (replaces the ACF UI)
* Settings page (Events → Settings): front-end date/time format, display defaults, empty-state copy, cache lifetime + clear button, load-more batch size, recurrence limits, schema.org toggle
* Element shortcodes for custom layouts: [sec_event_title], [sec_event_image], [sec_event_date], [sec_event_time], [sec_event_location], [sec_event_excerpt], [sec_event_content], [sec_event_categories], [sec_event_button]
* Default single, archive, and category templates (theme-, Elementor-, and block-theme-overridable)
* Elementor "Simple Events" widgets and Dynamic Tags (when Elementor is active)
* schema.org Event structured data on single event pages

**Compatibility**
* Existing events are preserved with no migration; ACF can be safely deactivated

= 4.4.0 (2026-05-28) =

**Added**
* **Recurring events**: events can now repeat every N days / weeks / months / years from the event edit screen
* End options: after a number of occurrences, on a specific date, or never (with a rolling 24-month horizon refilled daily by WP-Cron, capped at 60 months)
* Per-occurrence editing with a sidebar "Series Edit Scope" metabox: changes can apply to this occurrence only, this and future occurrences, or the entire series; per-field overrides keep individually-edited fields safe from series-wide updates
* Each occurrence is stored as a real `simple-events` post, so the shortcode, archive, AJAX loader, and admin filters work with recurring events out of the box
* New "Series" admin column on the events list table
* First public extensibility filters: `sec_recur_max_occurrences`, `sec_recur_max_horizon_months`, `sec_recur_sync_batch_size`, `sec_recur_horizon_refill_threshold_months`, `sec_recur_horizon_extend_months`, `sec_recur_copyable_field_keys`

**Behavioral notes**
* Disabling recurrence on a saved series only force-deletes FUTURE unmodified occurrences; past, edited, or trashed occurrences are detached and kept as standalone events so history isn't destroyed
* Trashing or restoring a parent cascades to its children
* Large series generate up to 50 occurrences synchronously and finish in the background; an admin notice on the parent edit screen reports progress
* DST-safe and month-end-safe date arithmetic (Jan 31 → Feb 28 → Mar 31; Feb 29 → Feb 28 in non-leap years)

= 4.3.1 (2026-04-22) =

**Security**
* Converted AJAX `load_more_events` endpoint to JSON responses (`wp_send_json_success` / `wp_send_json_error`); frontend JS updated in lockstep
* Sanitized nonce and admin `$_GET` reads; whitelist-validated the admin event status filter; escaped all admin filter dropdown output

**Fixed**
* Fixed asset cache-busting (plugin version was hardcoded to an older value, causing browsers to serve stale CSS/JS after upgrades)
* Fixed archive query meta_query merging so existing relation keys from other plugins are preserved
* Prevented duplicate `init()` execution caused by hooking both `plugins_loaded` and `acf/init`
* Moved translation loading to the `init` hook to silence the WordPress 6.7+ notice
* AJAX-loaded event cards now render the "Learn More" footer link, matching shortcode output

**Changed**
* Shortcode transient cache keys now include plugin version and login state so caches auto-invalidate on upgrade
* Extracted duplicated fallback event-card markup into a shared helper
* Introduced `SIMPLE_EVENTS_NONCE_ACTION` constant for the AJAX nonce action

= 4.3.0 (2024-09-22) =

**Added**
* **Multilingual Support**: Added complete translation support for Spanish (es_ES) and French (fr_FR)
* Added professional-quality translations for all plugin strings
* Added .po and .mo files for Spanish and French languages
* Added internationalization documentation and setup guide

**Changed**
* Updated .pot file with current version and creation date
* Enhanced plugin description to highlight multilingual capabilities
* Updated documentation to include translation information

= 4.2.4 (2024-09-22) =

**Added**
* Added SCSS build system with Sass compiler and stylelint
* Added development and production CSS build scripts
* Added file watching capabilities for development
* Added CSS linting with stylelint and standard SCSS configuration
* Added automatic version synchronization between plugin header, constants, and package.json

**Changed**
* **BREAKING**: Updated shortcode from `[simple_events_calendar]` to `[sec_events]` for consistency
* Improved responsive design with better media query organization
* Enhanced theme color inheritance for better integration with WordPress themes
* Refactored CSS build process from simple file copying to proper SCSS compilation
* Updated build system to generate both compressed (production) and expanded (development) CSS
* Improved accessibility features and reduced motion support

**Fixed**
* Fixed plugin description to clearly specify ACF® requirement
* Improved WordPress version compatibility requirements
* Enhanced color definitions and margin adjustments for better layout consistency
* Fixed media query structure for improved readability and maintainability

**Development**
* Added source maps for development builds
* Added proper CSS minification for production builds
* Added automated distribution and zip creation process

= 4.1.1 (2024-09-22) =

**Removed**
* Removed "Showing current and upcoming events only." message from shortcode display
* Removed scroll hint bar from after events
* Events now load automatically on scroll without instructional messages

= 4.1.0 (2024-09-22) =

**Improved**
* Enhanced ACF dependency error message for better user experience
* Added direct download link button "Download ACF Free Plugin" to WordPress plugin installer
* Cleaner, more actionable error messages for missing dependencies

= 4.0.3 (2024-09-22) =

**Fixed**
* Fixed duplicate content appearing in admin columns
* Added static tracking to prevent duplicate processing
* Eliminated stacked duplicate content within individual admin columns

= 4.0.0 (2024-09-22) =

**Added**
* Updated WordPress minimum requirement to 6.0+ for better compatibility
* Updated PHP minimum requirement to 7.4+
* Added build system with distribution and zip commands
* Added automated semantic versioning instructions

**Changed**
* **BREAKING**: Increased minimum WordPress and PHP requirements
* Refactored plugin architecture with proper class-based structure
* Improved admin columns functionality with better duplicate prevention

= 3.0.0 (2024-08-20) =

**Added**
* Responsive grid layout with device-specific columns
* Custom gap spacing for different screen sizes
* 4:3 aspect ratio for featured images with responsive design
* Event status filtering in admin
* Location field with visual indicators
* Comprehensive ACF dependency checking
* Scroll hints and loading animations
* Enhanced event card design with hover effects

**Changed**
* Updated initial event display from 9 to 6 events
* Improved infinite scroll with better error handling
* Enhanced admin interface with better column management
* Event cards now use flexbox layout for consistent heights
* Improved responsive design with device-specific adjustments

**Fixed**
* Infinite scroll error handling
* Past events filtering
* Date comparison issues using WordPress timezone
* ACF Pro detection reliability
* Event ordering consistency

== Upgrade Notice ==

= 5.2.0 =
Adds an in-admin "Upgrade to Pro" area (banner, feature preview, and menu link) for the upcoming Pro version. No changes to your events, settings, or the front end; the banner is dismissible per user.

= 5.1.0 =
Adds display widgets, single-event/Add-to-Calendar features, and a one-time migration that renames very old `events`/`events-cat` data to `simple-events`/`simple-events-cat` so existing events keep working. Back up your database and test on staging before upgrading a production site.

= 5.0.0 =
Advanced Custom Fields is no longer required. Your existing events are preserved with no migration needed, and ACF can be deactivated after upgrading. Event date/time/location are now edited in the built-in "Event Details" box.

= 4.2.4 =
BREAKING CHANGE: Shortcode changed from [simple_events_calendar] to [sec_events]. Please update your shortcodes after upgrading.

= 4.0.0 =
This version requires WordPress 6.0+ and PHP 7.4+. Please ensure your site meets these requirements before upgrading.

= 3.0.0 =
Major redesign with responsive grid layout and improved user experience. Backup your site before upgrading.