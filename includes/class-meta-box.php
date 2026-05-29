<?php

/**
 * Native event-details meta box for Simple Events Calendar
 *
 * Replaces the ACF-provided editing UI. Reads and writes the exact same post
 * meta keys and storage formats ACF used, so existing events are unaffected:
 *   - event_date          stored as Ymd      (e.g. 20260529)
 *   - event_start_time    stored as g:i a    (e.g. "2:30 pm")
 *   - event_end_time      stored as g:i a    (optional)
 *   - event_location      plain text         (optional)
 *   - event_repeats       int 1/0
 *   - event_repeat_interval   int
 *   - event_repeat_frequency  daily|weekly|monthly|yearly
 *   - event_repeat_end_type   never|count|until
 *   - event_repeat_count      int
 *   - event_repeat_until      Ymd
 *
 * Saves at save_post priority 10 so the recurrence engine (priority 30) reads
 * the persisted rule meta — matching ACF's old timing.
 *
 * @package Simple_Events_Calendar
 * @since 5.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Simple_Events_Meta_Box class
 */
class Simple_Events_Meta_Box {

    /**
     * Nonce action.
     */
    const NONCE_ACTION = 'simple_events_save_details';

    /**
     * Nonce field name.
     */
    const NONCE_NAME = 'simple_events_details_nonce';

    /**
     * Constructor.
     */
    public function __construct() {
        add_action('add_meta_boxes_simple-events', array($this, 'register'));
        add_action('save_post_simple-events', array($this, 'save'), 10, 2);
        add_action('admin_enqueue_scripts', array($this, 'enqueue'));
    }

    /**
     * Register the Event Details meta box.
     */
    public function register() {
        add_meta_box(
            'simple_events_details',
            __('Event Details', 'simple_events'),
            array($this, 'render'),
            'simple-events',
            'normal',
            'high'
        );
    }

    /**
     * Enqueue the conditional-logic admin script on the event edit screen.
     *
     * @param string $hook Current admin page.
     */
    public function enqueue($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }

        $screen = get_current_screen();
        if (!$screen || 'simple-events' !== $screen->post_type) {
            return;
        }

        wp_enqueue_script(
            'simple-events-admin',
            PLUGIN_ASSETS . '/js/simple-events-admin.js',
            array(),
            PLUGIN_VERSION,
            true
        );
    }

    /**
     * Render the meta box.
     *
     * @param WP_Post $post Current post.
     */
    public function render($post) {
        wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME);

        $date        = (string) get_post_meta($post->ID, 'event_date', true);          // Ymd
        $start       = (string) get_post_meta($post->ID, 'event_start_time', true);    // g:i a
        $end         = (string) get_post_meta($post->ID, 'event_end_time', true);      // g:i a
        $location    = (string) get_post_meta($post->ID, 'event_location', true);

        $repeats     = (int) get_post_meta($post->ID, 'event_repeats', true) === 1;
        $interval    = max(1, (int) get_post_meta($post->ID, 'event_repeat_interval', true));
        $frequency   = (string) get_post_meta($post->ID, 'event_repeat_frequency', true);
        $end_type    = (string) get_post_meta($post->ID, 'event_repeat_end_type', true);
        $count       = max(1, (int) get_post_meta($post->ID, 'event_repeat_count', true));
        $until       = (string) get_post_meta($post->ID, 'event_repeat_until', true);  // Ymd

        // Convert stored formats to HTML5 input formats.
        $date_input  = self::ymd_to_input($date);
        $start_input = self::time_to_input($start);
        $end_input   = self::time_to_input($end);
        $until_input = self::ymd_to_input($until);

        $is_child = class_exists('Simple_Events_Recurrence')
            && (int) get_post_meta($post->ID, Simple_Events_Recurrence::META_PARENT, true) > 0;
        ?>
        <div class="simple-events-meta-box">
            <p>
                <label for="sec_event_date"><strong><?php esc_html_e('Event Date', 'simple_events'); ?></strong> <span class="description">(<?php esc_html_e('required', 'simple_events'); ?>)</span></label><br />
                <input type="date" id="sec_event_date" name="sec_event_date" value="<?php echo esc_attr($date_input); ?>" required />
            </p>

            <p>
                <label for="sec_event_start_time"><strong><?php esc_html_e('Start Time', 'simple_events'); ?></strong></label>
                &nbsp;&nbsp;
                <label for="sec_event_end_time"><strong><?php esc_html_e('End Time', 'simple_events'); ?></strong> <span class="description">(<?php esc_html_e('optional', 'simple_events'); ?>)</span></label>
                <br />
                <input type="time" id="sec_event_start_time" name="sec_event_start_time" value="<?php echo esc_attr($start_input); ?>" />
                &nbsp;&nbsp;
                <input type="time" id="sec_event_end_time" name="sec_event_end_time" value="<?php echo esc_attr($end_input); ?>" />
            </p>

            <p>
                <label for="sec_event_location"><strong><?php esc_html_e('Location', 'simple_events'); ?></strong> <span class="description">(<?php esc_html_e('optional', 'simple_events'); ?>)</span></label><br />
                <input type="text" id="sec_event_location" name="sec_event_location" class="widefat" maxlength="255" value="<?php echo esc_attr($location); ?>" placeholder="<?php esc_attr_e('e.g., Conference Room A, 123 Main St, or Online', 'simple_events'); ?>" />
            </p>

            <hr />

            <?php if ($is_child) : ?>
                <p class="description"><?php esc_html_e('This event is part of a recurring series. Recurrence settings are managed on the series parent.', 'simple_events'); ?></p>
            <?php else : ?>
                <p>
                    <label>
                        <input type="checkbox" id="sec_event_repeats" name="sec_event_repeats" value="1" <?php checked($repeats); ?> data-sec-toggle="recur" />
                        <strong><?php esc_html_e('This is a recurring event', 'simple_events'); ?></strong>
                    </label>
                </p>

                <div class="sec-recur-fields" data-sec-recur-group>
                    <p>
                        <label for="sec_event_repeat_frequency"><?php esc_html_e('Repeats', 'simple_events'); ?></label><br />
                        <?php esc_html_e('Every', 'simple_events'); ?>
                        <input type="number" id="sec_event_repeat_interval" name="sec_event_repeat_interval" min="1" step="1" value="<?php echo esc_attr($interval); ?>" style="width:5em;" />
                        <select id="sec_event_repeat_frequency" name="sec_event_repeat_frequency">
                            <?php
                            $freqs = array(
                                'daily'   => __('day(s)', 'simple_events'),
                                'weekly'  => __('week(s)', 'simple_events'),
                                'monthly' => __('month(s)', 'simple_events'),
                                'yearly'  => __('year(s)', 'simple_events'),
                            );
                            foreach ($freqs as $value => $label) {
                                printf(
                                    '<option value="%s" %s>%s</option>',
                                    esc_attr($value),
                                    selected($frequency, $value, false),
                                    esc_html($label)
                                );
                            }
                            ?>
                        </select>
                    </p>

                    <p>
                        <label for="sec_event_repeat_end_type"><?php esc_html_e('Ends', 'simple_events'); ?></label><br />
                        <select id="sec_event_repeat_end_type" name="sec_event_repeat_end_type" data-sec-toggle="end-type">
                            <?php
                            $end_types = array(
                                'never' => __('Never', 'simple_events'),
                                'count' => __('After a number of occurrences', 'simple_events'),
                                'until' => __('On a date', 'simple_events'),
                            );
                            foreach ($end_types as $value => $label) {
                                printf(
                                    '<option value="%s" %s>%s</option>',
                                    esc_attr($value),
                                    selected($end_type ?: 'never', $value, false),
                                    esc_html($label)
                                );
                            }
                            ?>
                        </select>
                    </p>

                    <p data-sec-end="count">
                        <label for="sec_event_repeat_count"><?php esc_html_e('Number of occurrences', 'simple_events'); ?></label><br />
                        <input type="number" id="sec_event_repeat_count" name="sec_event_repeat_count" min="1" step="1" value="<?php echo esc_attr($count); ?>" />
                    </p>

                    <p data-sec-end="until">
                        <label for="sec_event_repeat_until"><?php esc_html_e('Repeat until', 'simple_events'); ?></label><br />
                        <input type="date" id="sec_event_repeat_until" name="sec_event_repeat_until" value="<?php echo esc_attr($until_input); ?>" />
                    </p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Persist the meta box fields.
     *
     * @param int     $post_id Post ID.
     * @param WP_Post $post    Post object.
     */
    public function save($post_id, $post) {
        // Bail while the recurrence engine is generating occurrences: child
        // inserts fire save_post inside the parent's request, and we must not
        // write the parent's submitted values onto generated children.
        if (!empty($GLOBALS['sec_generating_series'])) {
            return;
        }

        // Bail on autosave / revisions / missing nonce / insufficient caps.
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (wp_is_post_revision($post_id)) {
            return;
        }
        if (!isset($_POST[self::NONCE_NAME]) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[self::NONCE_NAME])), self::NONCE_ACTION)) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // --- Core fields ---------------------------------------------------
        $date_input = isset($_POST['sec_event_date']) ? sanitize_text_field(wp_unslash($_POST['sec_event_date'])) : '';
        $ymd = self::input_to_ymd($date_input);
        if ('' !== $ymd) {
            update_post_meta($post_id, 'event_date', $ymd);
        } else {
            delete_post_meta($post_id, 'event_date');
        }

        $start_input = isset($_POST['sec_event_start_time']) ? sanitize_text_field(wp_unslash($_POST['sec_event_start_time'])) : '';
        $start = self::input_to_time($start_input);
        if ('' !== $start) {
            update_post_meta($post_id, 'event_start_time', $start);
        } else {
            delete_post_meta($post_id, 'event_start_time');
        }

        $end_input = isset($_POST['sec_event_end_time']) ? sanitize_text_field(wp_unslash($_POST['sec_event_end_time'])) : '';
        $end = self::input_to_time($end_input);
        if ('' !== $end) {
            update_post_meta($post_id, 'event_end_time', $end);
        } else {
            delete_post_meta($post_id, 'event_end_time');
        }

        $location = isset($_POST['sec_event_location']) ? sanitize_text_field(wp_unslash($_POST['sec_event_location'])) : '';
        $location = mb_substr($location, 0, 255);
        if ('' !== $location) {
            update_post_meta($post_id, 'event_location', $location);
        } else {
            delete_post_meta($post_id, 'event_location');
        }

        // --- Recurrence rule ----------------------------------------------
        // Children never carry the recurrence inputs (the field set is hidden
        // for them), so leave any inherited series meta untouched in that case.
        $is_child = class_exists('Simple_Events_Recurrence')
            && (int) get_post_meta($post_id, Simple_Events_Recurrence::META_PARENT, true) > 0;
        if ($is_child) {
            return;
        }

        $repeats = !empty($_POST['sec_event_repeats']);
        update_post_meta($post_id, 'event_repeats', $repeats ? 1 : 0);

        if (!$repeats) {
            // Leave the detail fields in place but they are inert without
            // event_repeats=1 (read_rule() returns null). Nothing else to do.
            return;
        }

        $interval = isset($_POST['sec_event_repeat_interval']) ? absint($_POST['sec_event_repeat_interval']) : 1;
        update_post_meta($post_id, 'event_repeat_interval', max(1, $interval));

        $frequency = isset($_POST['sec_event_repeat_frequency']) ? sanitize_text_field(wp_unslash($_POST['sec_event_repeat_frequency'])) : '';
        if (!in_array($frequency, array('daily', 'weekly', 'monthly', 'yearly'), true)) {
            $frequency = 'weekly';
        }
        update_post_meta($post_id, 'event_repeat_frequency', $frequency);

        $end_type = isset($_POST['sec_event_repeat_end_type']) ? sanitize_text_field(wp_unslash($_POST['sec_event_repeat_end_type'])) : 'never';
        if (!in_array($end_type, array('never', 'count', 'until'), true)) {
            $end_type = 'never';
        }
        update_post_meta($post_id, 'event_repeat_end_type', $end_type);

        $count = isset($_POST['sec_event_repeat_count']) ? absint($_POST['sec_event_repeat_count']) : 1;
        update_post_meta($post_id, 'event_repeat_count', max(1, $count));

        $until_input = isset($_POST['sec_event_repeat_until']) ? sanitize_text_field(wp_unslash($_POST['sec_event_repeat_until'])) : '';
        $until_ymd = self::input_to_ymd($until_input);
        update_post_meta($post_id, 'event_repeat_until', $until_ymd);
    }

    /* ---------------------------------------------------------------------
     * Format conversion helpers.
     * ------------------------------------------------------------------- */

    /**
     * Stored Ymd -> HTML date input value (Y-m-d).
     *
     * @param string $ymd Stored value.
     * @return string
     */
    private static function ymd_to_input($ymd) {
        if (8 !== strlen((string) $ymd)) {
            return '';
        }
        $dt = DateTimeImmutable::createFromFormat('!Ymd', (string) $ymd);
        return $dt ? $dt->format('Y-m-d') : '';
    }

    /**
     * HTML date input value (Y-m-d) -> stored Ymd.
     *
     * @param string $input Submitted value.
     * @return string
     */
    private static function input_to_ymd($input) {
        if ('' === (string) $input) {
            return '';
        }
        $dt = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $input);
        return $dt ? $dt->format('Ymd') : '';
    }

    /**
     * Stored 12h time (g:i a) -> HTML time input value (H:i).
     *
     * @param string $time Stored value.
     * @return string
     */
    private static function time_to_input($time) {
        if ('' === (string) $time) {
            return '';
        }
        $dt = DateTimeImmutable::createFromFormat('g:i a', (string) $time);
        return $dt ? $dt->format('H:i') : '';
    }

    /**
     * HTML time input value (H:i) -> stored 12h time (g:i a).
     *
     * @param string $input Submitted value.
     * @return string
     */
    private static function input_to_time($input) {
        if ('' === (string) $input) {
            return '';
        }
        $dt = DateTimeImmutable::createFromFormat('H:i', (string) $input);
        return $dt ? $dt->format('g:i a') : '';
    }
}
