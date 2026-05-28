<?php

/**
 * Recurrence engine for Simple Events Calendar.
 *
 * @package Simple_Events_Calendar
 * @since 4.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Simple_Events_Recurrence
{
    const META_PARENT        = '_sec_series_parent';
    const META_INDEX         = '_sec_series_occurrence_index';
    const META_OVERRIDES     = '_sec_field_overrides';
    const META_RULE_FREQ     = '_sec_recur_freq';
    const META_RULE_INTERVAL = '_sec_recur_interval';
    const META_RULE_END_TYPE = '_sec_recur_end_type';
    const META_RULE_COUNT    = '_sec_recur_count';
    const META_RULE_UNTIL    = '_sec_recur_until';
    const META_RULE_HORIZON  = '_sec_recur_horizon';
    const META_RULE_SKIPPED  = '_sec_recur_skipped_indexes';

    const CRON_EXTEND_HOOK   = 'sec_recur_extend_horizon';
    const CRON_CONTINUE_HOOK = 'sec_recur_continue_generation';

    const NONCE_ACTION_SCOPE = 'sec_recur_edit_scope';
    const NONCE_FIELD_SCOPE  = 'sec_recur_edit_scope_nonce';

    /**
     * Snapshots of OLD post/meta values captured at post_updated time,
     * keyed by post_id. Used to compute the change diff on child saves
     * after ACF has overwritten the meta at save_post priority 10.
     *
     * @var array
     */
    private static $pre_save_snapshots = array();

    public function __construct()
    {
        $this->init_hooks();
    }

    private function init_hooks()
    {
        add_action('post_updated', array($this, 'snapshot_pre_save'), 10, 3);
        // save_post_{type} fires before save_post, but ACF hooks save_post at
        // priority 10 — so we hook the general save_post action at 30 to read
        // meta values that ACF has already persisted, then filter on post_type.
        add_action('save_post', array($this, 'handle_save_post'), 30, 3);
        add_action('before_delete_post', array($this, 'handle_before_delete'));
        add_action('wp_trash_post', array($this, 'handle_trash'));
        add_action('untrashed_post', array($this, 'handle_untrash'));
        add_action('add_meta_boxes_simple-events', array($this, 'register_edit_scope_metabox'));
        add_action(self::CRON_EXTEND_HOOK, array($this, 'cron_extend_horizon'));
        add_action(self::CRON_CONTINUE_HOOK, array($this, 'continue_background_generation'), 10, 2);
        add_action('init', array($this, 'maybe_reschedule_cron'), 20);
        add_action('admin_notices', array($this, 'render_admin_notices'));
    }

    public static function schedule_cron()
    {
        if (!wp_next_scheduled(self::CRON_EXTEND_HOOK)) {
            wp_schedule_event(time() + DAY_IN_SECONDS, 'daily', self::CRON_EXTEND_HOOK);
        }
    }

    public static function unschedule_cron()
    {
        wp_clear_scheduled_hook(self::CRON_EXTEND_HOOK);
        wp_clear_scheduled_hook(self::CRON_CONTINUE_HOOK);
    }

    public function maybe_reschedule_cron()
    {
        if (!wp_next_scheduled(self::CRON_EXTEND_HOOK)) {
            self::schedule_cron();
        }
    }

    // ---------------------------------------------------------------------
    // Hook handlers
    // ---------------------------------------------------------------------

    public function snapshot_pre_save($post_id, $post_after, $post_before)
    {
        unset($post_after);

        if (self::is_generating()) {
            return;
        }
        if (!$post_before || $post_before->post_type !== 'simple-events') {
            return;
        }
        if (!get_post_meta($post_id, self::META_PARENT, true)) {
            return;
        }

        self::$pre_save_snapshots[(int) $post_id] = array(
            'post_title'       => (string) $post_before->post_title,
            'post_content'     => (string) $post_before->post_content,
            'post_excerpt'     => (string) $post_before->post_excerpt,
            '_thumbnail_id'    => (string) get_post_meta($post_id, '_thumbnail_id', true),
            'event_date'       => (string) get_post_meta($post_id, 'event_date', true),
            'event_start_time' => (string) get_post_meta($post_id, 'event_start_time', true),
            'event_end_time'   => (string) get_post_meta($post_id, 'event_end_time', true),
            'event_location'   => (string) get_post_meta($post_id, 'event_location', true),
        );
    }

    public function handle_save_post($post_id, $post, $update)
    {
        unset($update);

        if (self::is_generating()) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (wp_is_post_revision($post_id)) {
            return;
        }
        if (!$post || $post->post_type !== 'simple-events') {
            return;
        }
        if ($post->post_status === 'auto-draft') {
            return;
        }

        $parent_id = (int) get_post_meta($post_id, self::META_PARENT, true);
        if ($parent_id) {
            $this->handle_child_save((int) $post_id, $parent_id);
            return;
        }

        if ((int) get_post_meta($post_id, 'event_repeats', true) !== 1) {
            // event_repeats just went from on -> off. Detect by the lingering
            // rule snapshot meta we wrote during the last regenerate_series.
            if (get_post_meta($post_id, self::META_RULE_FREQ, true)) {
                $this->handle_toggle_off((int) $post_id);
            }
            return;
        }

        $this->regenerate_series((int) $post_id, 'parent_save');
    }

    public function handle_before_delete($post_id)
    {
        if (self::is_generating()) {
            return;
        }
        if (get_post_type($post_id) !== 'simple-events') {
            return;
        }

        $parent_id = (int) get_post_meta($post_id, self::META_PARENT, true);
        if ($parent_id) {
            // Force-deleting a child: record its index as skipped so the next
            // regeneration on the parent doesn't recreate it. Trashing alone
            // doesn't mark skipped — the user can still restore from trash.
            $this->record_skipped_index($parent_id, (int) get_post_meta($post_id, self::META_INDEX, true));
            return;
        }

        // Parent being force-deleted — cascade. Includes trashed children in
        // case the parent was trashed first and is now being purged.
        $this->cascade_children(
            (int) $post_id,
            array('publish', 'pending', 'draft', 'future', 'private', 'trash'),
            'delete'
        );
    }

    public function handle_trash($post_id)
    {
        if (self::is_generating()) {
            return;
        }
        if (get_post_type($post_id) !== 'simple-events') {
            return;
        }

        if (get_post_meta($post_id, self::META_PARENT, true)) {
            // Child being trashed individually: do nothing. The series meta
            // stays intact so the child can be restored back into the series.
            return;
        }

        $this->cascade_children(
            (int) $post_id,
            array('publish', 'pending', 'draft', 'future', 'private'),
            'trash'
        );
    }

    public function handle_untrash($post_id)
    {
        if (self::is_generating()) {
            return;
        }
        if (get_post_type($post_id) !== 'simple-events') {
            return;
        }

        if (get_post_meta($post_id, self::META_PARENT, true)) {
            // Individually restoring a child: nothing to cascade.
            return;
        }

        // Parent restored from trash — untrash any of its children that were
        // cascade-trashed alongside it.
        $children = get_posts(array(
            'post_type'      => 'simple-events',
            'post_status'    => 'trash',
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'     => self::META_PARENT,
                    'value'   => (int) $post_id,
                    'compare' => '=',
                    'type'    => 'NUMERIC',
                ),
            ),
            'no_found_rows'  => true,
            'fields'         => 'ids',
        ));

        if (empty($children)) {
            return;
        }

        self::lock_generation((int) $post_id);
        try {
            foreach ($children as $child_id) {
                wp_untrash_post((int) $child_id);
            }
        } finally {
            self::unlock_generation();
        }
    }

    private function record_skipped_index($parent_id, $index)
    {
        if ($index <= 0) {
            return;
        }
        $skipped = get_post_meta($parent_id, self::META_RULE_SKIPPED, true);
        $skipped = is_array($skipped) ? array_map('intval', $skipped) : array();
        if (in_array($index, $skipped, true)) {
            return;
        }
        $skipped[] = $index;
        sort($skipped);
        update_post_meta($parent_id, self::META_RULE_SKIPPED, $skipped);
    }

    private function cascade_children($parent_id, array $statuses, $action)
    {
        $query = new WP_Query(array(
            'post_type'      => 'simple-events',
            'post_status'    => $statuses,
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'     => self::META_PARENT,
                    'value'   => (int) $parent_id,
                    'compare' => '=',
                    'type'    => 'NUMERIC',
                ),
            ),
            'no_found_rows'  => true,
            'suppress_filters' => false,
        ));

        if (empty($query->posts)) {
            wp_reset_postdata();
            return;
        }

        self::lock_generation($parent_id);
        try {
            foreach ($query->posts as $child) {
                $overrides = get_post_meta($child->ID, self::META_OVERRIDES, true);
                $has_overrides = is_array($overrides) && !empty($overrides);

                if ($has_overrides) {
                    // Don't destroy user-edited data — detach into a standalone event.
                    delete_post_meta($child->ID, self::META_PARENT);
                    delete_post_meta($child->ID, self::META_INDEX);
                    delete_post_meta($child->ID, self::META_OVERRIDES);
                    continue;
                }

                if ($action === 'trash') {
                    wp_trash_post($child->ID);
                } else {
                    wp_delete_post($child->ID, true);
                }
            }
        } finally {
            wp_reset_postdata();
            self::unlock_generation();
        }
    }

    private function handle_toggle_off($parent_id)
    {
        self::lock_generation($parent_id);
        $deleted  = 0;
        $detached = 0;

        try {
            $children = $this->get_existing_children($parent_id);
            foreach ($children as $child) {
                $overrides = get_post_meta($child->ID, self::META_OVERRIDES, true);
                if (is_array($overrides) && !empty($overrides)) {
                    delete_post_meta($child->ID, self::META_PARENT);
                    delete_post_meta($child->ID, self::META_INDEX);
                    delete_post_meta($child->ID, self::META_OVERRIDES);
                    $detached++;
                } else {
                    wp_delete_post($child->ID, true);
                    $deleted++;
                }
            }

            delete_post_meta($parent_id, self::META_RULE_FREQ);
            delete_post_meta($parent_id, self::META_RULE_INTERVAL);
            delete_post_meta($parent_id, self::META_RULE_END_TYPE);
            delete_post_meta($parent_id, self::META_RULE_COUNT);
            delete_post_meta($parent_id, self::META_RULE_UNTIL);
            delete_post_meta($parent_id, self::META_RULE_HORIZON);
            delete_post_meta($parent_id, self::META_RULE_SKIPPED);
        } finally {
            self::unlock_generation();
        }

        $this->enqueue_admin_notice(
            $parent_id,
            sprintf(
                /* translators: 1: number of occurrences deleted, 2: number detached as standalone events */
                __('Recurrence disabled. %1$d unmodified occurrence(s) deleted; %2$d modified occurrence(s) detached as standalone events.', PLUGIN_TEXT_DOMAIN),
                $deleted,
                $detached
            ),
            'warning'
        );
    }

    private function enqueue_admin_notice($post_id, $message, $type = 'info')
    {
        $key     = 'sec_recur_notice_' . (int) $post_id;
        $notices = get_transient($key);
        $notices = is_array($notices) ? $notices : array();
        $notices[] = array('message' => $message, 'type' => $type);
        set_transient($key, $notices, HOUR_IN_SECONDS);
    }

    public function register_edit_scope_metabox($post)
    {
        if (!$post || !get_post_meta($post->ID, self::META_PARENT, true)) {
            return;
        }

        add_meta_box(
            'sec_recur_edit_scope',
            __('Series Edit Scope', PLUGIN_TEXT_DOMAIN),
            array($this, 'render_edit_scope_metabox'),
            'simple-events',
            'side',
            'high'
        );
    }

    public function render_edit_scope_metabox($post)
    {
        $parent_id = (int) get_post_meta($post->ID, self::META_PARENT, true);
        $index     = (int) get_post_meta($post->ID, self::META_INDEX, true);

        wp_nonce_field(self::NONCE_ACTION_SCOPE, self::NONCE_FIELD_SCOPE);

        $options = array(
            'only'   => __('Only this occurrence', PLUGIN_TEXT_DOMAIN),
            'future' => __('This and future occurrences', PLUGIN_TEXT_DOMAIN),
            'series' => __('Entire series', PLUGIN_TEXT_DOMAIN),
        );

        $parent_edit_url = get_edit_post_link($parent_id);

        echo '<p>';
        printf(
            /* translators: %d is the occurrence number within the series */
            esc_html__('This event is occurrence #%d in a recurring series.', PLUGIN_TEXT_DOMAIN),
            (int) $index
        );
        if ($parent_edit_url) {
            echo ' <a href="' . esc_url($parent_edit_url) . '">' . esc_html__('Edit parent', PLUGIN_TEXT_DOMAIN) . '</a>';
        }
        echo '</p>';

        echo '<p><label for="sec_edit_scope"><strong>' . esc_html__('Apply changes to:', PLUGIN_TEXT_DOMAIN) . '</strong></label></p>';
        echo '<select name="sec_edit_scope" id="sec_edit_scope" class="widefat">';
        foreach ($options as $value => $label) {
            printf(
                '<option value="%s">%s</option>',
                esc_attr($value),
                esc_html($label)
            );
        }
        echo '</select>';

        echo '<p class="description">';
        esc_html_e('Only this occurrence: changes stay here. This and future: changes propagate to later siblings (date excluded). Entire series: propagates to the parent and every sibling, and a date change shifts the whole series.', PLUGIN_TEXT_DOMAIN);
        echo '</p>';
    }

    public function cron_extend_horizon()
    {
    }

    public function continue_background_generation($parent_id, $next_index)
    {
        unset($parent_id, $next_index);
    }

    public function render_admin_notices()
    {
        if (!is_admin() || !function_exists('get_current_screen')) {
            return;
        }
        $screen = get_current_screen();
        if (!$screen || $screen->base !== 'post' || $screen->post_type !== 'simple-events') {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display lookup, no state change.
        $post_id = isset($_GET['post']) ? (int) wp_unslash($_GET['post']) : 0;
        if (!$post_id) {
            return;
        }

        $key     = 'sec_recur_notice_' . $post_id;
        $notices = get_transient($key);
        if (!is_array($notices) || empty($notices)) {
            return;
        }
        delete_transient($key);

        foreach ($notices as $notice) {
            $type = isset($notice['type']) && in_array($notice['type'], array('success', 'warning', 'error', 'info'), true)
                ? $notice['type']
                : 'info';
            $message = isset($notice['message']) ? (string) $notice['message'] : '';
            printf(
                '<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
                esc_attr($type),
                esc_html($message)
            );
        }
    }

    // ---------------------------------------------------------------------
    // Child save handling
    // ---------------------------------------------------------------------

    private function handle_child_save($child_id, $parent_id)
    {
        if (!isset($_POST[self::NONCE_FIELD_SCOPE])) {
            return;
        }
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[self::NONCE_FIELD_SCOPE])), self::NONCE_ACTION_SCOPE)) {
            return;
        }
        if (!current_user_can('edit_post', $child_id)) {
            return;
        }

        $scope = isset($_POST['sec_edit_scope'])
            ? sanitize_text_field(wp_unslash($_POST['sec_edit_scope']))
            : 'only';
        if (!in_array($scope, array('only', 'future', 'series'), true)) {
            $scope = 'only';
        }

        $changed = $this->compute_changed_fields($child_id);
        if (empty($changed)) {
            return;
        }

        switch ($scope) {
            case 'only':
                $this->apply_only_scope($child_id, $changed);
                break;
            case 'future':
                $this->apply_future_scope($child_id, $parent_id, $changed);
                break;
            case 'series':
                $this->apply_series_scope($child_id, $parent_id, $changed);
                break;
        }
    }

    private function compute_changed_fields($child_id)
    {
        if (!isset(self::$pre_save_snapshots[$child_id])) {
            return array();
        }
        $snapshot = self::$pre_save_snapshots[$child_id];

        $current = array(
            'post_title'       => (string) get_post_field('post_title', $child_id),
            'post_content'     => (string) get_post_field('post_content', $child_id),
            'post_excerpt'     => (string) get_post_field('post_excerpt', $child_id),
            '_thumbnail_id'    => (string) get_post_meta($child_id, '_thumbnail_id', true),
            'event_date'       => (string) get_post_meta($child_id, 'event_date', true),
            'event_start_time' => (string) get_post_meta($child_id, 'event_start_time', true),
            'event_end_time'   => (string) get_post_meta($child_id, 'event_end_time', true),
            'event_location'   => (string) get_post_meta($child_id, 'event_location', true),
        );

        $changed = array();
        foreach ($current as $key => $value) {
            if (!array_key_exists($key, $snapshot)) {
                continue;
            }
            if ($value !== (string) $snapshot[$key]) {
                $changed[$key] = $value;
            }
        }

        return $changed;
    }

    private function apply_only_scope($child_id, array $changed)
    {
        $existing = get_post_meta($child_id, self::META_OVERRIDES, true);
        $existing = is_array($existing) ? $existing : array();
        $merged   = array_values(array_unique(array_merge($existing, array_keys($changed))));
        update_post_meta($child_id, self::META_OVERRIDES, $merged);
    }

    private function apply_future_scope($child_id, $parent_id, array $changed)
    {
        $this_index = (int) get_post_meta($child_id, self::META_INDEX, true);
        if (!$this_index) {
            return;
        }

        // Mark all changed keys as overrides on this child, so a later
        // "series" edit won't silently blow them away.
        $this->apply_only_scope($child_id, $changed);

        // event_date is not propagated under "future" scope — date changes
        // affect only the edited occurrence. Use "series" to shift everything.
        $propagate = $changed;
        unset($propagate['event_date']);
        if (empty($propagate)) {
            return;
        }

        $children = $this->get_existing_children($parent_id);
        foreach ($children as $idx => $sibling) {
            if ($idx <= $this_index) {
                continue;
            }

            $sibling_overrides = get_post_meta($sibling->ID, self::META_OVERRIDES, true);
            $sibling_overrides = is_array($sibling_overrides) ? $sibling_overrides : array();

            foreach ($propagate as $key => $value) {
                if (in_array($key, $sibling_overrides, true)) {
                    continue;
                }
                $this->write_field_to_post($sibling->ID, $key, $value);
                $sibling_overrides[] = $key;
            }

            $sibling_overrides = array_values(array_unique($sibling_overrides));
            update_post_meta($sibling->ID, self::META_OVERRIDES, $sibling_overrides);
        }
    }

    private function apply_series_scope($child_id, $parent_id, array $changed)
    {
        // event_date changes are interpreted as a series-wide shift: compute
        // delta and move the parent's anchor date. The regenerate at the end
        // will then re-key every unmodified child to its new date.
        if (isset($changed['event_date'])) {
            $this->shift_series_by_child_date_delta($child_id, $parent_id, $changed['event_date']);
            unset($changed['event_date']);
        }

        // Propagate the remaining (non-date) changes to the parent and any
        // siblings that haven't overridden the same key locally.
        foreach ($changed as $key => $value) {
            $this->write_field_to_post($parent_id, $key, $value);
        }

        $children = $this->get_existing_children($parent_id);
        foreach ($children as $sibling) {
            if ((int) $sibling->ID === (int) $child_id) {
                continue;
            }
            $sibling_overrides = get_post_meta($sibling->ID, self::META_OVERRIDES, true);
            $sibling_overrides = is_array($sibling_overrides) ? $sibling_overrides : array();
            foreach ($changed as $key => $value) {
                if (in_array($key, $sibling_overrides, true)) {
                    continue;
                }
                $this->write_field_to_post($sibling->ID, $key, $value);
            }
        }

        // The child whose edit triggered this scope now matches the rest of
        // the series for any propagated key — clear those keys from its
        // override list so future series-scope edits can update them again.
        $this_overrides = get_post_meta($child_id, self::META_OVERRIDES, true);
        if (is_array($this_overrides) && !empty($this_overrides)) {
            $remaining = array_values(array_diff($this_overrides, array_keys($changed)));
            if (empty($remaining)) {
                delete_post_meta($child_id, self::META_OVERRIDES);
            } else {
                update_post_meta($child_id, self::META_OVERRIDES, $remaining);
            }
        }

        $this->regenerate_series($parent_id, 'cascade');
    }

    private function shift_series_by_child_date_delta($child_id, $parent_id, $new_child_date)
    {
        if (!isset(self::$pre_save_snapshots[$child_id]['event_date'])) {
            return;
        }
        $old_child_date = (string) self::$pre_save_snapshots[$child_id]['event_date'];
        if (strlen($old_child_date) !== 8 || strlen($new_child_date) !== 8) {
            return;
        }

        $tz     = wp_timezone();
        $old_dt = DateTimeImmutable::createFromFormat('!Ymd', $old_child_date, $tz);
        $new_dt = DateTimeImmutable::createFromFormat('!Ymd', $new_child_date, $tz);
        if (!$old_dt instanceof DateTimeImmutable || !$new_dt instanceof DateTimeImmutable) {
            return;
        }

        $delta_days = (int) $old_dt->diff($new_dt)->format('%r%a');
        if ($delta_days === 0) {
            return;
        }

        $parent_old_ymd = (string) get_post_meta($parent_id, 'event_date', true);
        $parent_old_dt  = DateTimeImmutable::createFromFormat('!Ymd', $parent_old_ymd, $tz);
        if (!$parent_old_dt instanceof DateTimeImmutable) {
            return;
        }

        try {
            $modifier      = ($delta_days >= 0 ? '+' : '') . $delta_days . ' days';
            $parent_new_dt = $parent_old_dt->modify($modifier);
        } catch (Exception $e) {
            return;
        }
        if (!$parent_new_dt instanceof DateTimeImmutable) {
            return;
        }

        update_post_meta($parent_id, 'event_date', $parent_new_dt->format('Ymd'));
    }

    private function write_field_to_post($post_id, $key, $value)
    {
        if (in_array($key, array('post_title', 'post_content', 'post_excerpt'), true)) {
            global $wpdb;
            // Direct posts-table write: wp_update_post would re-fire save_post
            // (and ACF's field-save handler) on every propagation target,
            // overwriting their meta with $_POST values from the OTHER post
            // being edited. Cache is invalidated explicitly below.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->update(
                $wpdb->posts,
                array($key => $value),
                array('ID' => (int) $post_id),
                array('%s'),
                array('%d')
            );
            clean_post_cache((int) $post_id);
            return;
        }

        update_post_meta($post_id, $key, $value);
    }

    // ---------------------------------------------------------------------
    // Core generation
    // ---------------------------------------------------------------------

    public function regenerate_series($parent_id, $context)
    {
        unset($context);

        $parent_id = (int) $parent_id;
        if (!$parent_id) {
            return;
        }

        $lock_key = 'sec_recur_lock_' . $parent_id;
        if (get_transient($lock_key)) {
            simple_events_debug_log('Recurrence lock held; skipping regeneration', array('parent_id' => $parent_id));
            return;
        }
        set_transient($lock_key, 1, MINUTE_IN_SECONDS);

        self::lock_generation($parent_id);

        $revisions_filter = static function ($num, $post) {
            if ($post && isset($post->post_type) && $post->post_type === 'simple-events') {
                return 0;
            }
            return $num;
        };
        add_filter('wp_revisions_to_keep', $revisions_filter, 10, 2);

        try {
            $rule = $this->read_rule($parent_id);
            if (!$rule) {
                return;
            }

            $start_ymd = (string) get_post_meta($parent_id, 'event_date', true);
            if (strlen($start_ymd) !== 8) {
                return;
            }

            $computed = $this->compute_occurrence_dates($start_ymd, $rule);
            if (empty($computed)) {
                return;
            }

            $existing = $this->get_existing_children($parent_id);
            $result   = $this->diff_and_apply($parent_id, $computed, $existing);

            if ($rule['end_type'] === 'never' && !empty($result['last_date'])) {
                update_post_meta($parent_id, self::META_RULE_HORIZON, $result['last_date']);
            } else {
                delete_post_meta($parent_id, self::META_RULE_HORIZON);
            }

            // Persist rule snapshot so cron / background workers don't need ACF.
            update_post_meta($parent_id, self::META_RULE_FREQ, $rule['freq']);
            update_post_meta($parent_id, self::META_RULE_INTERVAL, $rule['interval']);
            update_post_meta($parent_id, self::META_RULE_END_TYPE, $rule['end_type']);
            if ($rule['end_type'] === 'count') {
                update_post_meta($parent_id, self::META_RULE_COUNT, $rule['count']);
            } else {
                delete_post_meta($parent_id, self::META_RULE_COUNT);
            }
            if ($rule['end_type'] === 'until') {
                update_post_meta($parent_id, self::META_RULE_UNTIL, $rule['until']);
            } else {
                delete_post_meta($parent_id, self::META_RULE_UNTIL);
            }
        } finally {
            remove_filter('wp_revisions_to_keep', $revisions_filter, 10);
            self::unlock_generation();
            delete_transient($lock_key);
        }
    }

    private function read_rule($parent_id)
    {
        if ((int) get_post_meta($parent_id, 'event_repeats', true) !== 1) {
            return null;
        }

        $freq     = (string) get_post_meta($parent_id, 'event_repeat_frequency', true);
        $interval = max(1, (int) get_post_meta($parent_id, 'event_repeat_interval', true));
        $end_type = (string) get_post_meta($parent_id, 'event_repeat_end_type', true);
        $count    = max(1, (int) get_post_meta($parent_id, 'event_repeat_count', true));
        $until    = (string) get_post_meta($parent_id, 'event_repeat_until', true);

        if (!in_array($freq, array('daily', 'weekly', 'monthly', 'yearly'), true)) {
            return null;
        }
        if (!in_array($end_type, array('never', 'count', 'until'), true)) {
            return null;
        }
        if ($end_type === 'until' && strlen($until) !== 8) {
            return null;
        }

        return array(
            'freq'     => $freq,
            'interval' => $interval,
            'end_type' => $end_type,
            'count'    => $count,
            'until'    => $until,
        );
    }

    private function compute_occurrence_dates($start_ymd, array $rule)
    {
        $max_occurrences    = max(1, (int) apply_filters('sec_recur_max_occurrences', 1000));
        $max_horizon_months = max(1, (int) apply_filters('sec_recur_max_horizon_months', 60));

        $tz    = wp_timezone();
        $start = DateTimeImmutable::createFromFormat('!Ymd', $start_ymd, $tz);
        if (!$start instanceof DateTimeImmutable) {
            return array();
        }

        if ($rule['end_type'] === 'count') {
            $stop_count = min($max_occurrences, $rule['count']);
            $stop_date  = null;
        } elseif ($rule['end_type'] === 'until') {
            $stop_count = $max_occurrences;
            $stop_date  = $rule['until'];
        } else {
            $stop_count = $max_occurrences;
            $months     = (int) apply_filters('sec_recur_horizon_extend_months', 12)
                + (int) apply_filters('sec_recur_horizon_refill_threshold_months', 6);
            $months     = max(1, min($months, $max_horizon_months));

            try {
                $hard_cap_dt = $start->add(new DateInterval('P' . $max_horizon_months . 'M'));
                $horizon_dt  = $start->add(new DateInterval('P' . $months . 'M'));
            } catch (Exception $e) {
                return array();
            }

            if ($horizon_dt > $hard_cap_dt) {
                $horizon_dt = $hard_cap_dt;
            }
            $stop_date = $horizon_dt->format('Ymd');
        }

        $dates = array();
        for ($index = 0; $index < $stop_count; $index++) {
            $date = $this->advance_date($start, $rule['freq'], $rule['interval'] * $index);
            if (!$date instanceof DateTimeImmutable) {
                break;
            }

            $ymd = $date->format('Ymd');
            if ($stop_date !== null && $ymd > $stop_date) {
                break;
            }

            $dates[$index] = $ymd;
        }

        return $dates;
    }

    private function advance_date(DateTimeImmutable $start, $freq, $offset_units)
    {
        if ($offset_units === 0) {
            return $start;
        }

        try {
            switch ($freq) {
                case 'daily':
                    return $start->add(new DateInterval('P' . $offset_units . 'D'));
                case 'weekly':
                    return $start->add(new DateInterval('P' . ($offset_units * 7) . 'D'));
                case 'monthly':
                    return $this->add_months_clamped($start, $offset_units);
                case 'yearly':
                    return $this->add_years_clamped($start, $offset_units);
            }
        } catch (Exception $e) {
            return null;
        }
        return null;
    }

    private function add_months_clamped(DateTimeImmutable $start, $months)
    {
        $year  = (int) $start->format('Y');
        $month = (int) $start->format('n');
        $day   = (int) $start->format('j');

        $total     = (($month - 1) + $months);
        $new_year  = $year + intdiv($total, 12);
        $new_month = ($total % 12) + 1;
        if ($new_month <= 0) {
            $new_month += 12;
            $new_year  -= 1;
        }

        $last_day = (int) date('t', mktime(0, 0, 0, $new_month, 1, $new_year));
        $new_day  = min($day, $last_day);

        return $start->setDate($new_year, $new_month, $new_day);
    }

    private function add_years_clamped(DateTimeImmutable $start, $years)
    {
        $year  = (int) $start->format('Y');
        $month = (int) $start->format('n');
        $day   = (int) $start->format('j');

        $new_year = $year + $years;

        if ($month === 2 && $day === 29) {
            $last_day = (int) date('t', mktime(0, 0, 0, 2, 1, $new_year));
            $day      = min($day, $last_day);
        }

        return $start->setDate($new_year, $month, $day);
    }

    private function get_existing_children($parent_id)
    {
        $query = new WP_Query(array(
            'post_type'        => 'simple-events',
            'post_status'      => 'any',
            'posts_per_page'   => -1,
            'meta_query'       => array(
                array(
                    'key'     => self::META_PARENT,
                    'value'   => (int) $parent_id,
                    'compare' => '=',
                    'type'    => 'NUMERIC',
                ),
            ),
            'no_found_rows'    => true,
            'suppress_filters' => false,
            'orderby'          => 'ID',
            'order'            => 'ASC',
        ));

        $by_index = array();
        foreach ($query->posts as $child) {
            $idx = (int) get_post_meta($child->ID, self::META_INDEX, true);
            $by_index[$idx] = $child;
        }
        wp_reset_postdata();

        return $by_index;
    }

    private function diff_and_apply($parent_id, array $computed_dates, array $existing_children)
    {
        $skipped = get_post_meta($parent_id, self::META_RULE_SKIPPED, true);
        $skipped = is_array($skipped) ? array_map('intval', $skipped) : array();

        $parent        = get_post($parent_id);
        $parent_status = $parent ? $parent->post_status : 'publish';
        $copyable      = $this->get_copyable_field_keys();

        $created   = 0;
        $updated   = 0;
        $deleted   = 0;
        $detached  = 0;
        $last_date = '';

        foreach ($computed_dates as $index => $ymd) {
            if ($index === 0) {
                $last_date = $ymd;
                continue;
            }

            if (in_array($index, $skipped, true)) {
                continue;
            }

            if (isset($existing_children[$index])) {
                $child     = $existing_children[$index];
                $overrides = get_post_meta($child->ID, self::META_OVERRIDES, true);
                $overrides = is_array($overrides) ? $overrides : array();

                if (!in_array('event_date', $overrides, true)) {
                    update_post_meta($child->ID, 'event_date', $ymd);
                    $updated++;
                }
                $last_date = $ymd;
                unset($existing_children[$index]);
            } else {
                $child_id = $this->create_child($parent_id, $parent, $parent_status, $index, $ymd, $copyable);
                if ($child_id) {
                    $created++;
                    $last_date = $ymd;
                }
            }
        }

        foreach ($existing_children as $child) {
            $overrides = get_post_meta($child->ID, self::META_OVERRIDES, true);
            if (is_array($overrides) && !empty($overrides)) {
                delete_post_meta($child->ID, self::META_PARENT);
                delete_post_meta($child->ID, self::META_INDEX);
                delete_post_meta($child->ID, self::META_OVERRIDES);
                $detached++;
            } else {
                wp_delete_post($child->ID, true);
                $deleted++;
            }
        }

        return array(
            'created'   => $created,
            'updated'   => $updated,
            'deleted'   => $deleted,
            'detached'  => $detached,
            'last_date' => $last_date,
        );
    }

    private function create_child($parent_id, $parent, $parent_status, $index, $ymd, array $copyable)
    {
        if (!$parent) {
            return 0;
        }

        $insert_data = array(
            'post_type'   => 'simple-events',
            'post_status' => $parent_status,
            'post_author' => $parent->post_author,
        );
        if (in_array('post_title', $copyable, true)) {
            $insert_data['post_title'] = $parent->post_title;
        }
        if (in_array('post_content', $copyable, true)) {
            $insert_data['post_content'] = $parent->post_content;
        }
        if (in_array('post_excerpt', $copyable, true)) {
            $insert_data['post_excerpt'] = $parent->post_excerpt;
        }

        $child_id = wp_insert_post($insert_data, true);
        if (is_wp_error($child_id) || !$child_id) {
            return 0;
        }

        if (in_array('_thumbnail_id', $copyable, true)) {
            $thumbnail_id = get_post_meta($parent_id, '_thumbnail_id', true);
            if ($thumbnail_id) {
                update_post_meta($child_id, '_thumbnail_id', $thumbnail_id);
            }
        }

        foreach (array('event_start_time', 'event_end_time', 'event_location') as $acf_key) {
            if (!in_array($acf_key, $copyable, true)) {
                continue;
            }
            $value = get_post_meta($parent_id, $acf_key, true);
            update_post_meta($child_id, $acf_key, $value);

            $field_key_ref = get_post_meta($parent_id, '_' . $acf_key, true);
            if ($field_key_ref) {
                update_post_meta($child_id, '_' . $acf_key, $field_key_ref);
            }
        }

        update_post_meta($child_id, 'event_date', $ymd);
        $event_date_field_key = get_post_meta($parent_id, '_event_date', true);
        if ($event_date_field_key) {
            update_post_meta($child_id, '_event_date', $event_date_field_key);
        }

        $terms = wp_get_object_terms($parent_id, 'simple-events-cat', array('fields' => 'ids'));
        if (!is_wp_error($terms) && !empty($terms)) {
            wp_set_object_terms($child_id, $terms, 'simple-events-cat');
        }

        update_post_meta($child_id, self::META_PARENT, (int) $parent_id);
        update_post_meta($child_id, self::META_INDEX, (int) $index);

        return $child_id;
    }

    private function get_copyable_field_keys()
    {
        $default = array(
            'post_title',
            'post_content',
            'post_excerpt',
            '_thumbnail_id',
            'event_start_time',
            'event_end_time',
            'event_location',
        );

        $filtered = apply_filters('sec_recur_copyable_field_keys', $default);
        return is_array($filtered) && !empty($filtered) ? $filtered : $default;
    }

    // ---------------------------------------------------------------------
    // Recursion guard
    // ---------------------------------------------------------------------

    private static function is_generating()
    {
        return !empty($GLOBALS['sec_generating_series']);
    }

    private static function lock_generation($parent_id)
    {
        $GLOBALS['sec_generating_series'] = (int) $parent_id;
    }

    private static function unlock_generation()
    {
        unset($GLOBALS['sec_generating_series']);
    }
}
