# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Simple Events Calendar is a WordPress plugin (v4.3.0) that registers a `simple-events` custom post type and renders events via the `[sec_events]` shortcode and post-type archives. It **requires** Advanced Custom Fields (Free or Pro) — the plugin deactivates itself if ACF is missing.

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

`npm run dist` and `npm run zip` rely on Windows `robocopy` and a Python one-liner — they will not work as-is on macOS/Linux.

## Architecture

### Entry point and bootstrap
`simple-events-calendar.php` defines plugin constants (`PLUGIN_DIR`, `PLUGIN_URL`, `PLUGIN_ASSETS`, `PLUGIN_VERSION`, `PLUGIN_TEXT_DOMAIN`, `SIMPLE_EVENTS_PLUGIN_FILE`) and calls `Simple_Events_Calendar::get_instance()` (singleton). All real work happens inside `includes/class-main.php`.

### Initialization order matters
`class-main.php` hooks both `plugins_loaded` (priority 20) **and** `acf/init` to `init()`. The plugin guards against double-loading and also checks ACF at `admin_init`. If ACF isn't present, the plugin **auto-deactivates** and shows an admin notice — so any new feature that depends on plugin state must tolerate `init()` being called more than once, and must not fire before `load_components()` has run.

`load_components()` is where the subsystem classes are `require_once`'d and instantiated, in this order:
1. `includes/functions.php` (shared helpers; loaded first — other classes may depend on it)
2. `class-post-type.php` → `Simple_Events_Post_Type` (registers `simple-events` CPT + `simple-events-cat` taxonomy)
3. `class-shortcode.php` → `Simple_Events_Shortcode` (`[sec_events]` + transient caching)
4. `class-ajax.php` → `Simple_Events_Ajax` (infinite scroll handler)
5. `class-admin-columns.php` → `Simple_Events_Admin_Columns`
6. `includes/acf-json.php`, `includes/acf-settings-page.php` (ACF field group registration + save path)

All component instances hang off the main singleton (`$plugin->post_type`, `->shortcode`, `->ajax`, `->admin_columns`) — reach them via `simple_events_calendar()` rather than constructing new ones.

### CPT and archive query
The post type slug is `simple-events` and the taxonomy is `simple-events-cat`. `modify_archive_query()` in `class-main.php` hooks `pre_get_posts` on the front-end main query and forces:
- `orderby = meta_value`, `meta_key = event_date`, `meta_type = DATE`, `order = ASC`
- a `meta_query` filter that hides events where `event_date < current_time('Ymd')`

Any new archive-facing query must go through the same pattern (ACF `event_date` meta, `Ymd` format) or it will not sort/filter consistently with the rest of the plugin.

### Asset enqueue gating
`enqueue_scripts()` only enqueues CSS/JS when the current request is a `simple-events` archive/single/taxonomy, a post containing the `[sec_events]` shortcode, or a text widget. Test that this gate still holds when adding new rendering paths — silently enqueueing everywhere is a regression.

`wp_localize_script` exposes `ajax_params` (`ajaxurl`, `nonce`, `initial_offset = 6`, `load_increment = 6`) to the infinite-scroll JS. The nonce action string lives in the `SIMPLE_EVENTS_NONCE_ACTION` constant (defined in the main plugin file) — use it everywhere, never hardcode the string. Changing any of these keys requires updating `assets/js/simple-events.js` in lockstep.

The AJAX handler returns `wp_send_json_success({ html, has_more })` on success and `wp_send_json_error({ message }, status)` on failure. The JS consumes `response.data.html` / `response.data.has_more`. Don't regress this to bare-string responses.

### Shortcode caching
`Simple_Events_Shortcode::render_shortcode()` caches rendered output in a transient keyed by the MD5 of sanitized attributes for 15 minutes. Cache is invalidated via `save_post`, `delete_post`, and `transition_post_status` hooks. When changing what the shortcode outputs (e.g., new data sources), audit whether existing cached output could become stale, and extend the invalidation hooks if so.

### ACF dependency
The plugin relies on ACF field group `event_details` (fields: `event_date`, `event_start_time`, `event_end_time`, `event_location`). Field definitions live in `includes/acf-settings-page.php` and are synced to `includes/acf-json/` via ACF's local JSON mechanism (wired up in `includes/acf-json.php`). When modifying fields, edit them in WP admin and let ACF sync to JSON — don't hand-edit the JSON.

### Build pipeline
- `src/css/simple-events.scss` is the source; `assets/css/simple-events.css` is generated output. **Never edit `assets/css/simple-events.css` directly** — it will be overwritten by the next build.
- JS in `assets/js/` is hand-written (no build step). `assets/js/simple-events.js` is the main script; `simple-events-shortcode.js` is shortcode-specific.
- The distribution step excludes `node_modules`, `src`, `.git`, `dist`, dotfiles, `package*.json`, `phpcs.xml`, and `*.md`.

## Internationalization
Three shipped locales (`en`, `es_ES`, `fr_FR`) in `languages/`. Always wrap user-facing strings with `__()`/`_e()`/`_x()` using the `simple_events` / `PLUGIN_TEXT_DOMAIN` text domain — phpcs is configured to enforce this.
