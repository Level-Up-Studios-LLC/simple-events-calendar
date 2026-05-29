# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Simple Events Calendar is a WordPress plugin (v4.4.0) that registers a `simple-events` custom post type and renders events via the `[sec_events]` shortcode and post-type archives. It **requires** Advanced Custom Fields (Free or Pro) — the plugin deactivates itself if ACF is missing.

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
`class-main.php` hooks both `plugins_loaded` (priority 20) **and** `acf/init` to `init()`. The plugin guards against double-loading and also checks ACF at `admin_init`. If ACF isn't present, the plugin **auto-deactivates** and shows an admin notice — so any new feature that depends on plugin state must tolerate `init()` being called more than once, and must not fire before `load_components()` has run.

`load_components()` is where the subsystem classes are `require_once`'d and instantiated, in this order:
1. `includes/functions.php` (shared helpers; loaded first — other classes may depend on it)
2. `class-post-type.php` → `Simple_Events_Post_Type` (registers `simple-events` CPT + `simple-events-cat` taxonomy)
3. `class-shortcode.php` → `Simple_Events_Shortcode` (`[sec_events]` + transient caching)
4. `class-ajax.php` → `Simple_Events_Ajax` (infinite scroll handler)
5. `class-admin-columns.php` → `Simple_Events_Admin_Columns`
6. `class-recurrence.php` → `Simple_Events_Recurrence` (recurring-events engine)
7. `includes/acf-json.php`, `includes/acf-settings-page.php` (ACF field group registration + save path)

All component instances hang off the main singleton (`$plugin->post_type`, `->shortcode`, `->ajax`, `->admin_columns`, `->recurrence`) — reach them via `simple_events_calendar()` rather than constructing new ones.

### CPT and archive query
The post type slug is `simple-events` and the taxonomy is `simple-events-cat`. **Front-end rewrite slugs differ from internal names**: posts live under `/events/...` and taxonomy archives under `/event-category/...` (see `class-post-type.php`). REST bases are `simple-events` and `simple-events-categories`.

`modify_archive_query()` in `class-main.php` hooks `pre_get_posts` on the front-end main query and forces:
- `orderby = meta_value`, `meta_key = event_date`, `meta_type = DATE`, `order = ASC`
- a `meta_query` filter that hides events where `event_date < current_time('Ymd')`
- If a `meta_query` already exists, it is **nested under an `AND` relation** rather than merged — preserve this pattern when adding more filters.

Any new archive-facing query must go through the same pattern (ACF `event_date` meta, `Ymd` format) or it will not sort/filter consistently with the rest of the plugin.

### Asset enqueue gating
`enqueue_scripts()` only enqueues CSS/JS when the current request is a `simple-events` archive/single/taxonomy, a post containing the `[sec_events]` shortcode, or a text widget. Test that this gate still holds when adding new rendering paths — silently enqueueing everywhere is a regression.

`wp_localize_script` exposes `ajax_params` (`ajaxurl`, `nonce`, `initial_offset = 6`, `load_increment = 6`) to the infinite-scroll JS. The nonce action string lives in the `SIMPLE_EVENTS_NONCE_ACTION` constant (defined in the main plugin file as `'load_more_events_nonce'`) — use it everywhere, never hardcode the string. Changing any of these keys requires updating `assets/js/simple-events.js` in lockstep.

The AJAX handler is registered under the `load_more_events` action (priv + nopriv), returns `wp_send_json_success({ html, has_more })` on success and `wp_send_json_error({ message }, status)` on failure. The JS consumes `response.data.html` / `response.data.has_more`. Don't regress this to bare-string responses. The handler **hardcodes `posts_per_page` to 6** and caps `offset` at 10000 (see `class-ajax.php`) — these are independent of the shortcode's `posts_per_page` attribute and the `load_increment` localized to JS, so changing the page size requires touching all three sites.

### Shortcode and its caching
`[sec_events]` accepts these attributes (all optional, defaults shown):
`posts_per_page=6` (clamped 1–50), `category=""` (taxonomy slug), `show_past="no"`, `order="ASC"`, `orderby="event_date"`, `show_time="yes"`, `show_excerpt="yes"`, `show_location="yes"`, `show_footer="yes"`.

`Simple_Events_Shortcode::render_shortcode()` caches rendered output in a transient for 15 minutes, keyed on `md5(serialize($sanitized_atts) . '|' . PLUGIN_VERSION . '|' . is_user_logged_in())`. The version segment auto-invalidates the cache on plugin upgrade; the login-state segment prevents admin-only variations from leaking to anonymous visitors. **Empty-state output (containing `simple-events-no-events`) is intentionally not cached**, so adding new "no results" markup must keep that class to avoid pinning empty results.

Cache is invalidated via `save_post`, `delete_post`, and `transition_post_status` — but **only when the post being changed is a `simple-events` post**. If new logic causes the shortcode output to depend on other post types/terms/options, extend the invalidation hooks accordingly.

### ACF dependency
The plugin relies on ACF field group `group_event_details` with these fields:

| Field name              | Type        | Required               |
| ----------------------- | ----------- | ---------------------- |
| `event_date`            | date_picker | Yes                    |
| `event_start_time`      | time_picker | Yes                    |
| `event_end_time`        | time_picker | No                     |
| `event_location`        | text        | No                     |
| `event_repeats`         | true_false  | No                     |
| `event_repeat_frequency`| select      | When `event_repeats=1` |
| `event_repeat_interval` | number      | When `event_repeats=1` |
| `event_repeat_end_type` | select      | When `event_repeats=1` |
| `event_repeat_count`    | number      | When `end_type=count`  |
| `event_repeat_until`    | date_picker | **When `end_type=until`** (`required=1` at the ACF level — conditional logic limits the validation to when the field is visible). |

Field definitions live in `includes/acf-settings-page.php` (PHP-registered via `register_event_details_fields()` on `acf/init`) and are synced to `includes/acf-json/` via ACF's local JSON mechanism (wired up in `includes/acf-json.php`). The `acf-json/` directory is not committed — it's created at activation by `create_acf_json_directory()` / `simple_events_create_acf_json_dir()`. When modifying fields, edit them in WP admin and let ACF sync to JSON — don't hand-edit the JSON.

### Admin columns and filters
`Simple_Events_Admin_Columns` adds Thumbnail / Event Date / Time / Location / Categories columns to the `simple-events` list table, plus two filter dropdowns: a category filter (uses the taxonomy query var `simple-events-cat`) and a status filter (custom query var `event_status` with values `upcoming`, `today`, `past`). When adding new admin filters, follow the same `parse_query` + meta_query pattern.

### Uninstall is destructive
`Simple_Events_Calendar::uninstall()` (registered via `register_uninstall_hook`) **deletes every `simple-events` post, every `simple-events-cat` term, and every `_transient_simple_events_*` row** on plugin uninstall. There is no opt-out. Any data the plugin should preserve must live outside that post type/taxonomy/transient prefix.

### Recurring events
`Simple_Events_Recurrence` (in `includes/class-recurrence.php`) implements per-occurrence recurring events. Each occurrence is a **real** `simple-events` post with its own `event_date` linked back to a parent via `_sec_series_parent` post meta, so the shortcode, archive, AJAX, and admin filters work unchanged.

**Per-parent meta keys** (all exposed as `Simple_Events_Recurrence::META_*` constants):

| Constant            | Meta key                          | Stored on | Purpose                                                                                       |
| ------------------- | --------------------------------- | --------- | --------------------------------------------------------------------------------------------- |
| `META_PARENT`       | `_sec_series_parent`              | child     | Parent post ID                                                                                |
| `META_INDEX`        | `_sec_series_occurrence_index`    | child     | Ordinal in the series (parent is 0)                                                           |
| `META_OVERRIDES`    | `_sec_field_overrides`            | child     | Array of field keys the user has diverged on; the generator never overwrites these            |
| `META_CASCADED_TRASH` | `_sec_recur_cascaded_trash`     | child     | Marker set by `cascade_children('trash')` so `handle_untrash` only restores its own cascaded children, not children the user pre-trashed |
| `META_RULE_FREQ`    | `_sec_recur_freq`                 | parent    | Frequency snapshot (`daily`/`weekly`/`monthly`/`yearly`) — written by `regenerate_series` so cron / background workers don't depend on ACF |
| `META_RULE_INTERVAL`| `_sec_recur_interval`             | parent    | Interval snapshot                                                                              |
| `META_RULE_END_TYPE`| `_sec_recur_end_type`             | parent    | End-type snapshot (`never`/`count`/`until`)                                                    |
| `META_RULE_COUNT`   | `_sec_recur_count`                | parent    | Count snapshot (only when end_type=count)                                                      |
| `META_RULE_UNTIL`   | `_sec_recur_until`                | parent    | Until-date snapshot (`Ymd`, only when end_type=until)                                          |
| `META_RULE_HORIZON` | `_sec_recur_horizon`              | parent    | Target horizon for "never" series — written write-monotonically (`end($computed)`), never `last_date` |
| `META_RULE_SKIPPED` | `_sec_recur_skipped_indexes`      | parent    | Indexes the user force-deleted; regeneration never recreates them                              |
| `META_CHILD_COUNT`  | `_sec_recur_child_count`          | parent    | Cached live (non-trash) child count used by the admin Series column. Refreshed by `recount_children()` after every mutation — **after** the state change (i.e., `trashed_post` / `deleted_post`, not their pre-action siblings) so the cache isn't stale by ±1 |
| `META_FUTURE_SEGMENTS` | `_sec_recur_future_segments`   | parent    | Persisted "this and future" edits as `[{from_index, fields}]` segments — `create_child` overlays matching segments so children generated later by async batching / horizon extension / count increase inherit the edit |

**Invariants:**
- The **parent post is occurrence #0**. Children carry the child-side meta above.
- All date math uses `DateTimeImmutable` + `wp_timezone()` (NOT `date('t', mktime(...))` — keep month-length lookups on the DateTimeImmutable too). Monthly recurrences anchor on the parent's day-of-month and clamp to the last day of the target month (Jan 31 → Feb 28 → Mar 31). Yearly Feb 29 anchors clamp to Feb 28 in non-leap years.
- **`save_post` is hooked at priority 30** (not the post-type-specific variant) because `save_post_simple-events` fires before `save_post`, and ACF persists field meta at `save_post` priority 10 — running at 30 guarantees ACF has already populated the rule meta.
- The recursion guard is `$GLOBALS['sec_generating_series']`. Every hook handler (`post_updated`, `save_post`, `before_delete_post`, `deleted_post`, `wp_trash_post`, `trashed_post`, `untrashed_post`) bails when it's set; the generator and cascade routines set it via `lock_generation()` / `unlock_generation()`.
- A per-parent **atomic option-row lock** (`sec_recur_lock_<parent_id>`, acquired via `add_option(..., '', 'no')` — the options-table INSERT IGNORE provides atomic cross-request semantics, unlike a transient where the get-then-set pair would race) prevents concurrent regenerations from double-creating occurrences. A stale-lock check clears and re-acquires once if the value is older than 60 s, recovering from any process that died holding the lock. **Don't switch this back to a transient** — the round-3 review caught the original implementation race.
- Large series are generated up to `sec_recur_sync_batch_size` (default 50) synchronously, then continued via `wp_schedule_single_event('sec_recur_continue_generation', [parent_id, 0])`. The continuation re-enters `regenerate_series` — `diff_and_apply` is idempotent because already-created indexes are detected and skipped. **Don't add side effects to `diff_and_apply` that aren't safe to repeat.**
- "Never" series have their horizon stored in `_sec_recur_horizon` and refilled by the `sec_recur_extend_horizon` daily cron, capped at start + `sec_recur_max_horizon_months` (60). The persisted value is the **target** horizon (`end($computed)`), not `last_date`, and only ever **grows** — partial passes never roll a cron-extended horizon backward.
- Field propagation for "this and future" / "entire series" edits goes through `write_field_to_post()`, which uses **direct `$wpdb->update` + `clean_post_cache`** for `post_title` / `post_content` / `post_excerpt` to avoid re-firing `save_post` (and ACF's field-save handler) on each propagation target — re-firing would write the original post's `$_POST['acf']` onto every sibling.
- **`create_child` strips inherited recurrence ACF meta**: `wp_insert_post` runs inside the parent's save_post chain, so ACF's save_post handler fires for the new child with the parent's `$_POST['acf']` and writes the recurrence fields onto every child. The helper then explicitly deletes those six field keys (and their `_`-prefixed ACF sibling refs) so each child is a leaf, not a phantom series-parent. Don't remove this cleanup loop.
- `get_existing_children` **includes `'trash'` in the status list** (the WP `'any'` shorthand excludes it). Trashed children show up so the diff pass can short-circuit on their index instead of duplicating a live child at the same slot. The deletion pass detaches them rather than force-deleting.
- **Past-friendly toggle-off**: when `event_repeats` flips on → off, `handle_toggle_off` only force-deletes future unmodified live children. Past, trashed, or per-occurrence-edited children get detached and survive as standalone events. The admin notice and the ACF field instruction both say this; keep them aligned.

**Hook surface:**

| Hook                            | Handler                             | Purpose                                                                                |
| ------------------------------- | ----------------------------------- | -------------------------------------------------------------------------------------- |
| `post_updated` (pri. 10, 3 args)| `snapshot_pre_save`                 | Captures OLD field values into `self::$pre_save_snapshots` so the priority-30 save-post handler can diff against them after ACF rewrites meta at priority 10. |
| `save_post` (pri. 30, 3 args)   | `handle_save_post`                  | Routes to `handle_child_save` (child) or `regenerate_series` / `handle_toggle_off` (parent). |
| `add_meta_boxes_simple-events`  | `register_edit_scope_metabox`       | Adds the Series Edit Scope sidebar metabox on children only.                           |
| `before_delete_post`            | `handle_before_delete`              | Parent → cascade-delete. Child → record skipped index + capture parent ID into `self::$pending_delete_parents` for the post-delete recount. |
| `deleted_post`                  | `handle_deleted_post`               | Refreshes the cached child count using the captured parent ID — runs AFTER the row is gone (when `get_post_meta` on the child returns nothing). |
| `wp_trash_post`                 | `handle_trash`                      | Parent → cascade-trash (marks each cascaded child with `META_CASCADED_TRASH`). Child trash is a documented no-op here. |
| `trashed_post`                  | `handle_trashed_post`               | Refreshes the cached child count after the post status flips to `trash` — recount in the pre-action sibling would still count the trashed-to-be child as live. |
| `untrashed_post`                | `handle_untrash`                    | Parent → restore only children carrying `META_CASCADED_TRASH` (clearing the marker on restore). |
| `sec_recur_extend_horizon` (daily cron) | `cron_extend_horizon`       | Extends `META_RULE_HORIZON` on any "never" series within the refill threshold.         |
| `sec_recur_continue_generation` (one-shot cron) | `continue_background_generation` | Re-enters `regenerate_series` for the next batch of large series.                  |
| `init` (pri. 20)                | `maybe_reschedule_cron`             | Defensively re-registers the daily cron if it disappeared (e.g., DB migration).        |
| `admin_notices`                 | `render_admin_notices`              | Surfaces transient-stored notices on the parent's edit screen.                         |

`Simple_Events_Calendar::activation_check()` calls `Simple_Events_Recurrence::schedule_cron()`; `deactivation()` inlines `wp_unschedule_hook('sec_recur_extend_horizon')` + `wp_unschedule_hook('sec_recur_continue_generation')` so the deactivation path doesn't depend on `class-recurrence.php` being loaded. `wp_unschedule_hook` (not `wp_clear_scheduled_hook`) is required because the continuation events are scheduled with per-parent args — the no-arg form of `wp_clear_scheduled_hook` only matches no-arg events and would leave queued batches behind.

Public extensibility filters (the plugin's first): `sec_recur_max_occurrences` (1000), `sec_recur_max_horizon_months` (60), `sec_recur_sync_batch_size` (50), `sec_recur_horizon_refill_threshold_months` (6), `sec_recur_horizon_extend_months` (18), `sec_recur_copyable_field_keys`. Keep these stable — they're part of the public contract.

### Templates and SEO markup
Both the shortcode and AJAX paths render each event through `template-parts/content-event-card.php` (with `simple_events_render_fallback_card()` in `includes/functions.php` as a fallback). The card emits **schema.org Event JSON-LD** inline; if you add new event metadata that should affect search results, update the `$event_schema` block in the template.

### Build pipeline
- `src/css/simple-events.scss` is the source; `assets/css/simple-events.css` is generated output. **Never edit `assets/css/simple-events.css` directly** — it will be overwritten by the next build.
- JS in `assets/js/` is hand-written (no build step). `assets/js/simple-events.js` is the main script; `simple-events-shortcode.js` is shortcode-specific.
- The distribution step excludes `node_modules`, `src`, `.git`, `dist`, dotfiles, `package*.json`, `phpcs.xml`, and `*.md`.

## Internationalization
Three shipped locales (`en`, `es_ES`, `fr_FR`) in `languages/`. Always wrap user-facing strings with `__()`/`_e()`/`_x()` using the `simple_events` / `PLUGIN_TEXT_DOMAIN` text domain — phpcs is configured to enforce this.
