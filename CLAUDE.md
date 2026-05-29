# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Simple Events Calendar is a WordPress plugin (v5.0.0) that registers a `simple-events` custom post type and renders events via the `[sec_events]` shortcode, per-element shortcodes, post-type archives, and default templates. It is **fully self-contained** — event fields are edited through a native meta box (`includes/class-meta-box.php`). **As of v5.0.0 there is no Advanced Custom Fields dependency.** (Versions ≤ 4.4.0 required ACF; v5.0.0 removed it without any data migration — event values were always plain post meta.)

- PHP 7.4+, WordPress 6.0+
- Text domain: `simple_events`
- No automated test suite exists.

## Common Commands

```bash
# CSS build (SCSS is the source of truth; CSS is compiled)
npm run dev           # expanded CSS + source maps
npm run watch         # chokidar-based watcher (runs build:css:dev on change)
npm run watch:css     # native sass --watch alternative
npm run build         # minified production CSS (no source map)

# Linting
npm run lint:css      # stylelint on src/css/**/*.scss
npm run lint          # runs lint:js (no-op) then lint:css

# Packaging
npm run dist          # builds a dist/ folder (uses Windows robocopy)
npm run zip           # produces simple-events-calendar.zip (uses python)
```

PHP code style is enforced via **phpcs.xml** (WordPress Coding Standards). Run with phpcs/phpcbf locally if installed — the ruleset prefixes are `simple_events`, `Simple_Events`, `PLUGIN_`, `SIMPLE_EVENTS_`.

`npm run dist` and `npm run zip` mix POSIX (`rm -rf`, `mkdir`) with Windows `robocopy` and a Python one-liner — they're meant to run in **Git Bash on Windows** and will not work as-is in cmd/PowerShell or on macOS/Linux.

## Architecture

### Entry point and bootstrap
`simple-events-calendar.php` defines plugin constants (`PLUGIN_DIR`, `PLUGIN_URL`, `PLUGIN_ASSETS`, `PLUGIN_VERSION`, `PLUGIN_TEXT_DOMAIN`, `SIMPLE_EVENTS_PLUGIN_FILE`) and calls `Simple_Events_Calendar::get_instance()` (singleton). All real work happens inside `includes/class-main.php`.

### Initialization order matters
`class-main.php` hooks `plugins_loaded` (priority 20) to `init()`, guarded by an `$initialized` flag so it runs once. `init()` calls `load_components()`; any new feature that depends on plugin state must not fire before `load_components()` has run.

`load_components()` is where the subsystem classes are `require_once`'d and instantiated, in this order:
1. `includes/functions.php` (shared helpers; loaded first — other classes may depend on it)
2. `class-post-type.php` → `Simple_Events_Post_Type` (registers `simple-events` CPT + `simple-events-cat` taxonomy; also emits single-page schema on `wp_head`)
3. `class-renderer.php` → `Simple_Events_Renderer` (shared element renderer + `[sec_event_*]` element shortcodes)
4. `class-shortcode.php` → `Simple_Events_Shortcode` (`[sec_events]` + transient caching)
5. `class-ajax.php` → `Simple_Events_Ajax` (infinite scroll handler)
6. `class-admin-columns.php` → `Simple_Events_Admin_Columns`
7. `class-meta-box.php` → `Simple_Events_Meta_Box` (native Event Details editing UI)
8. `class-settings.php` → `Simple_Events_Settings` (Events → Settings page + `simple_events_settings` option)
9. `class-templates.php` → `Simple_Events_Templates` (default single/archive/taxonomy templates via `template_include`)
10. `class-recurrence.php` → `Simple_Events_Recurrence` (recurring-events engine)
11. `includes/elementor/class-elementor.php` → `Simple_Events_Elementor::init()` (no-op unless Elementor is active)

All component instances hang off the main singleton (`$plugin->post_type`, `->renderer`, `->shortcode`, `->ajax`, `->admin_columns`, `->meta_box`, `->settings`, `->templates`, `->recurrence`) — reach them via `simple_events_calendar()` rather than constructing new ones.

### Settings and the display helpers
All tunables live in one option array `simple_events_settings` (see `Simple_Events_Settings` + `simple_events_get_setting_defaults()`). Read settings via `simple_events_get_setting($key, $fallback)`. The front-end date/time format is a setting, so **never** read `event_date`/time meta raw for display — use `simple_events_get_event_date($id)` and `simple_events_get_event_time($id, $key)`, which convert the stored `Ymd` / `g:i a` values to the configured display format. Schema is built once by `simple_events_get_event_schema($id)` (returns null when the JSON-LD setting is off). Saving settings flushes the shortcode transients.

### Native fields and storage formats (critical)
The meta box reads/writes the exact keys/formats earlier versions used via ACF, so existing data is untouched: `event_date` = `Ymd`, `event_start_time`/`event_end_time` = `g:i a`, `event_location` = text, and the rule keys `event_repeats` (int 1/0), `event_repeat_interval` (int), `event_repeat_frequency` (`daily|weekly|monthly|yearly`), `event_repeat_end_type` (`never|count|until`), `event_repeat_count` (int), `event_repeat_until` (`Ymd`). The meta box hooks `save_post_simple-events` at priority 10 (which fires before any `save_post`), so the recurrence engine at `save_post`:30 still reads the persisted rule. **The meta box bails when `$GLOBALS['sec_generating_series']` is set**, so generated children are never overwritten with the parent's submitted values.

### Element shortcodes, templates, Elementor
`Simple_Events_Renderer` is the single source of truth for per-element HTML. The `[sec_event_*]` shortcodes, the Elementor widgets (`includes/elementor/widgets.php`), and the Dynamic Tags (`includes/elementor/dynamic-tags.php`) all call it — keep new element output there, not duplicated. Default templates (`templates/`) are a fallback only: `Simple_Events_Templates::template_include()` (priority 5) yields to theme files (`locate_template`), block/FSE themes (`wp_is_block_theme`), and — by running before Elementor's filter — Elementor Pro Theme Builder; the `simple_events_use_default_template` filter disables them.

### CPT and archive query
The post type slug is `simple-events` and the taxonomy is `simple-events-cat`. **Front-end rewrite slugs differ from internal names**: posts live under `/events/...` and taxonomy archives under `/event-category/...` (see `class-post-type.php`). REST bases are `simple-events` and `simple-events-categories`.

`modify_archive_query()` in `class-main.php` hooks `pre_get_posts` on the front-end main query and forces:
- `orderby = meta_value`, `meta_key = event_date`, `meta_type = DATE`, `order = ASC`
- a `meta_query` filter that hides events where `event_date < current_time('Ymd')`
- If a `meta_query` already exists, it is **nested under an `AND` relation** rather than merged — preserve this pattern when adding more filters.

Any new archive-facing query must go through the same pattern (ACF `event_date` meta, `Ymd` format) or it will not sort/filter consistently with the rest of the plugin.

### Asset enqueue gating
`enqueue_scripts()` only enqueues CSS/JS when the current request is a `simple-events` archive/single/taxonomy, a post containing the `[sec_events]` shortcode, or a text widget. Test that this gate still holds when adding new rendering paths — silently enqueueing everywhere is a regression.

`wp_localize_script` exposes `ajax_params` (`ajaxurl`, `nonce`, `initial_offset`, `load_increment`) to the infinite-scroll JS. `initial_offset` and `load_increment` both come from the `load_increment` setting (default 6). The nonce action string lives in the `SIMPLE_EVENTS_NONCE_ACTION` constant (defined in the main plugin file as `'load_more_events_nonce'`) — use it everywhere, never hardcode the string. Changing any of these keys requires updating `assets/js/simple-events.js` in lockstep.

The infinite-scroll JS reads the listing **context from the container's data attributes** (`data-offset`, `data-category`, `data-show-past`, `data-show-*`) and sends them with each request, so "load more" continues the correct query (e.g. stays within a category archive). The shortcode container and the archive/taxonomy templates both set these.

The AJAX handler is registered under the `load_more_events` action (priv + nopriv), returns `wp_send_json_success({ html, has_more })` on success and `wp_send_json_error({ message }, status)` on failure. The JS consumes `response.data.html` / `response.data.has_more`. Don't regress this to bare-string responses. The handler's `posts_per_page` comes from the `load_increment` setting and `offset` is capped at 10000 (see `class-ajax.php`). `modify_archive_query()` also sets the archive main query's `posts_per_page` to `load_increment` so the first batch lines up with the load-more offsets.

### Shortcode and its caching
`[sec_events]` accepts these attributes (all optional, defaults shown):
`posts_per_page=6` (clamped 1–50), `category=""` (taxonomy slug), `show_past="no"`, `order="ASC"`, `orderby="event_date"`, `show_time="yes"`, `show_excerpt="yes"`, `show_location="yes"`, `show_footer="yes"`.

Attribute **defaults derive from the settings page** (`posts_per_page`, `show_past`, `order`, `show_*`); an explicit shortcode attribute still overrides per instance.

`Simple_Events_Shortcode::render_shortcode()` caches rendered output in a transient for the `cache_ttl` setting (default 15 minutes), keyed on `md5(serialize($sanitized_atts) . '|' . PLUGIN_VERSION . '|' . is_user_logged_in())`. The version segment auto-invalidates the cache on plugin upgrade; the login-state segment prevents admin-only variations from leaking to anonymous visitors. **Empty-state output (containing `simple-events-no-events`) is intentionally not cached**, so adding new "no results" markup must keep that class to avoid pinning empty results.

Cache is invalidated via `save_post`, `delete_post`, and `transition_post_status` — but **only when the post being changed is a `simple-events` post**. If new logic causes the shortcode output to depend on other post types/terms/options, extend the invalidation hooks accordingly.

### Event fields (native, no ACF)
Event fields are registered and persisted entirely by `Simple_Events_Meta_Box` — there is no ACF and no field-group JSON. The fields are plain post meta: `event_date`, `event_start_time`, `event_end_time`, `event_location`, and the `event_repeat_*` rule keys (see "Native fields and storage formats" above for exact formats). To add a field: render an input in `Simple_Events_Meta_Box::render()`, sanitize + persist it in `save()`, add a display path via `Simple_Events_Renderer` (and optionally a `[sec_event_*]` shortcode / Elementor widget), and update `simple_events_get_event_schema()` if it should affect SEO.

### Admin columns and filters
`Simple_Events_Admin_Columns` adds Thumbnail / Event Date / Time / Location / Categories columns to the `simple-events` list table, plus two filter dropdowns: a category filter (uses the taxonomy query var `simple-events-cat`) and a status filter (custom query var `event_status` with values `upcoming`, `today`, `past`). When adding new admin filters, follow the same `parse_query` + meta_query pattern.

### Uninstall is destructive
`Simple_Events_Calendar::uninstall()` (registered via `register_uninstall_hook`) **deletes every `simple-events` post, every `simple-events-cat` term, and every `_transient_simple_events_*` row** on plugin uninstall. There is no opt-out. Any data the plugin should preserve must live outside that post type/taxonomy/transient prefix.

### Recurring events
`Simple_Events_Recurrence` (in `includes/class-recurrence.php`) implements per-occurrence recurring events. Each occurrence is a **real** `simple-events` post with its own `event_date` linked back to a parent via `_sec_series_parent` post meta, so the shortcode, archive, AJAX, and admin filters work unchanged.

Key invariants:
- The **parent post is occurrence #0**. Children carry `_sec_series_parent`, `_sec_series_occurrence_index`, and `_sec_field_overrides` (array of meta keys the user diverged on; the generator never overwrites these).
- The rule is stored on the parent as `_sec_recur_freq` / `_sec_recur_interval` / `_sec_recur_end_type` / `_sec_recur_count` / `_sec_recur_until` / `_sec_recur_horizon` / `_sec_recur_skipped_indexes`.
- All date math uses `DateTimeImmutable` + `wp_timezone()`. Monthly recurrences anchor on the parent's day-of-month and clamp to the last day of the target month (Jan 31 → Feb 28 → Mar 31). Yearly Feb 29 anchors clamp to Feb 28 in non-leap years.
- **`save_post` is hooked at priority 30** (not the post-type-specific variant) because `save_post_simple-events` fires before `save_post`, and ACF persists field meta at `save_post` priority 10 — running at 30 guarantees ACF has already populated the rule meta.
- The recursion guard is `$GLOBALS['sec_generating_series']`. Every hook handler (`save_post`, `before_delete_post`, `wp_trash_post`, `untrashed_post`) bails when it's set; the generator and cascade routines set it via `lock_generation()` / `unlock_generation()`.
- A per-parent **atomic option-row lock** (`sec_recur_lock_<parent_id>`, acquired via `add_option(..., '', 'no')` — the options-table INSERT IGNORE provides atomic cross-request semantics, unlike a transient where the get-then-set pair would race) prevents concurrent regenerations from double-creating occurrences. A stale-lock check clears and re-acquires once if the value is older than 60 s, recovering from any process that died holding the lock. **Don't switch this back to a transient** — the round-3 review caught the original implementation race.
- Large series are generated up to `sec_recur_sync_batch_size` (default 50) synchronously, then continued via `wp_schedule_single_event('sec_recur_continue_generation', [parent_id, 0])`. The continuation re-enters `regenerate_series` — `diff_and_apply` is idempotent because already-created indexes are detected and skipped. **Don't add side effects to `diff_and_apply` that aren't safe to repeat.**
- "Never" series have their horizon stored in `_sec_recur_horizon` and refilled by the `sec_recur_extend_horizon` daily cron, capped at start + `sec_recur_max_horizon_months` (60).
- Field propagation for "this and future" / "entire series" edits goes through `write_field_to_post()`, which uses **direct `$wpdb->update` + `clean_post_cache`** for `post_title` / `post_content` / `post_excerpt` to avoid re-firing `save_post` (and ACF's field-save handler) on each propagation target — re-firing would write the original post's `$_POST['acf']` onto every sibling.

The class registers `before_delete_post`, `wp_trash_post`, and `untrashed_post` to cascade parent operations to children: trashing or force-deleting a parent cascades to unmodified children and **detaches** modified children (clears their series meta so they become standalone events). Force-deleting an individual child records its index in `_sec_recur_skipped_indexes` so the next regeneration won't recreate it; trashing a child individually is a no-op so it can be restored back into the series.

`Simple_Events_Calendar::activation_check()` calls `Simple_Events_Recurrence::schedule_cron()`; `deactivation()` inlines `wp_unschedule_hook('sec_recur_extend_horizon')` + `wp_unschedule_hook('sec_recur_continue_generation')` so the deactivation path doesn't depend on `class-recurrence.php` being loaded. `wp_unschedule_hook` (not `wp_clear_scheduled_hook`) is required because the continuation events are scheduled with per-parent args — the no-arg form of `wp_clear_scheduled_hook` only matches no-arg events and would leave queued batches behind.

Public extensibility filters (the plugin's first): `sec_recur_max_occurrences` (1000), `sec_recur_max_horizon_months` (60), `sec_recur_sync_batch_size` (50), `sec_recur_horizon_refill_threshold_months` (6), `sec_recur_horizon_extend_months` (18), `sec_recur_copyable_field_keys`. Keep these stable — they're part of the public contract.

### Templates and SEO markup
Both the shortcode and AJAX paths render each event through `template-parts/content-event-card.php` (with `simple_events_render_fallback_card()` in `includes/functions.php` as a fallback; the archive/taxonomy templates render cards via `simple_events_render_event_card()`). The card emits **schema.org Event JSON-LD** inline via `simple_events_get_event_schema()` (skipped when the JSON-LD setting is off), and single event pages emit the same schema on `wp_head` (`Simple_Events_Post_Type::output_single_schema()`). To change structured data, edit `simple_events_get_event_schema()` in `includes/functions.php` — it's the single source.

### Build pipeline
- `src/css/simple-events.scss` is the source; `assets/css/simple-events.css` is generated output. **Never edit `assets/css/simple-events.css` directly** — it will be overwritten by the next build.
- JS in `assets/js/` is hand-written (no build step). `assets/js/simple-events.js` is the main script; `simple-events-shortcode.js` is shortcode-specific.
- The distribution step excludes `node_modules`, `src`, `.git`, `dist`, dotfiles, `package*.json`, `phpcs.xml`, and `*.md`.

## Internationalization
Three shipped locales (`en`, `es_ES`, `fr_FR`) in `languages/`. Always wrap user-facing strings with `__()`/`_e()`/`_x()` using the `simple_events` / `PLUGIN_TEXT_DOMAIN` text domain — phpcs is configured to enforce this.
