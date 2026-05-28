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

    public function __construct()
    {
        $this->init_hooks();
    }

    private function init_hooks()
    {
        add_action('save_post_simple-events', array($this, 'handle_save_post'), 30, 3);
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

    public function handle_save_post($post_id, $post, $update)
    {
        if (self::is_generating()) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (wp_is_post_revision($post_id)) {
            return;
        }
    }

    public function handle_before_delete($post_id)
    {
        if (self::is_generating()) {
            return;
        }
        if (get_post_type($post_id) !== 'simple-events') {
            return;
        }
    }

    public function handle_trash($post_id)
    {
        if (self::is_generating()) {
            return;
        }
        if (get_post_type($post_id) !== 'simple-events') {
            return;
        }
    }

    public function handle_untrash($post_id)
    {
        if (self::is_generating()) {
            return;
        }
        if (get_post_type($post_id) !== 'simple-events') {
            return;
        }
    }

    public function register_edit_scope_metabox()
    {
    }

    public function cron_extend_horizon()
    {
    }

    public function continue_background_generation($parent_id, $next_index)
    {
    }

    public function render_admin_notices()
    {
    }

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
