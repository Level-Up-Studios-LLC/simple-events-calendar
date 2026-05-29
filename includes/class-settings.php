<?php

/**
 * Settings page for Simple Events Calendar
 *
 * Registers a single option array (simple_events_settings) via the WordPress
 * Settings API and renders an admin page under the Events menu. Also wires the
 * relevant settings into the plugin's public recurrence filters and provides a
 * "Clear cache now" action.
 *
 * @package Simple_Events_Calendar
 * @since 5.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Simple_Events_Settings class
 */
class Simple_Events_Settings {

    /**
     * Settings option name. Must match SIMPLE_EVENTS_SETTINGS_OPTION.
     */
    const OPTION = 'simple_events_settings';

    /**
     * Settings page slug.
     */
    const PAGE = 'simple-events-settings';

    /**
     * Constructor.
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_post_simple_events_clear_cache', array($this, 'handle_clear_cache'));

        // Wire the recurrence-limit settings into the public filters.
        add_filter('sec_recur_max_occurrences', array($this, 'filter_max_occurrences'));
        add_filter('sec_recur_max_horizon_months', array($this, 'filter_max_horizon_months'));
    }

    /**
     * Register the settings submenu page under the Events menu.
     */
    public function add_menu() {
        add_submenu_page(
            'edit.php?post_type=simple-events',
            __('Events Settings', 'simple_events'),
            __('Settings', 'simple_events'),
            'manage_options',
            self::PAGE,
            array($this, 'render_page')
        );
    }

    /**
     * Register the option and its sanitize callback.
     */
    public function register_settings() {
        register_setting(
            self::PAGE,
            self::OPTION,
            array(
                'type'              => 'array',
                'sanitize_callback' => array($this, 'sanitize'),
                'default'           => simple_events_get_setting_defaults(),
            )
        );
    }

    /**
     * Sanitize the full settings array on save and flush rendered caches.
     *
     * @param array $input Raw submitted values.
     * @return array Sanitized values merged over defaults.
     */
    public function sanitize($input) {
        $defaults = simple_events_get_setting_defaults();
        $input    = is_array($input) ? $input : array();
        $clean    = array();

        // Date format: allow a non-empty PHP format string, else default.
        $date_format = isset($input['date_format']) ? trim((string) $input['date_format']) : '';
        $clean['date_format'] = ('' !== $date_format) ? sanitize_text_field($date_format) : $defaults['date_format'];

        // Time format: 12 or 24.
        $clean['time_format'] = (isset($input['time_format']) && '24' === (string) $input['time_format']) ? '24' : '12';

        // Per-page count clamped 1-50 (mirrors the shortcode clamp).
        $ppp = isset($input['posts_per_page']) ? absint($input['posts_per_page']) : $defaults['posts_per_page'];
        $clean['posts_per_page'] = ($ppp >= 1 && $ppp <= 50) ? $ppp : $defaults['posts_per_page'];

        // Order.
        $clean['order'] = (isset($input['order']) && 'DESC' === strtoupper((string) $input['order'])) ? 'DESC' : 'ASC';

        // Yes/no toggles.
        foreach (array('show_past', 'show_time', 'show_excerpt', 'show_location', 'show_footer', 'enable_schema') as $flag) {
            $clean[$flag] = (isset($input[$flag]) && 'yes' === (string) $input[$flag]) ? 'yes' : 'no';
        }

        // Empty-state copy.
        $clean['empty_state_heading'] = isset($input['empty_state_heading'])
            ? sanitize_text_field($input['empty_state_heading'])
            : $defaults['empty_state_heading'];
        $clean['empty_state_text'] = isset($input['empty_state_text'])
            ? sanitize_text_field($input['empty_state_text'])
            : $defaults['empty_state_text'];

        // Cache TTL in minutes (1-1440).
        $ttl = isset($input['cache_ttl']) ? absint($input['cache_ttl']) : $defaults['cache_ttl'];
        $clean['cache_ttl'] = ($ttl >= 1 && $ttl <= 1440) ? $ttl : $defaults['cache_ttl'];

        // Load-more batch size (1-50).
        $inc = isset($input['load_increment']) ? absint($input['load_increment']) : $defaults['load_increment'];
        $clean['load_increment'] = ($inc >= 1 && $inc <= 50) ? $inc : $defaults['load_increment'];

        // Recurrence limits.
        $max_occ = isset($input['recur_max_occurrences']) ? absint($input['recur_max_occurrences']) : $defaults['recur_max_occurrences'];
        $clean['recur_max_occurrences'] = max(1, $max_occ);
        $max_hor = isset($input['recur_max_horizon_months']) ? absint($input['recur_max_horizon_months']) : $defaults['recur_max_horizon_months'];
        $clean['recur_max_horizon_months'] = max(1, $max_hor);

        // Output now depends on settings — flush the rendered shortcode caches.
        if (function_exists('simple_events_clear_all_transients')) {
            simple_events_clear_all_transients();
        }

        return $clean;
    }

    /**
     * Filter callback: cap on total occurrences per series.
     *
     * @param int $value Default value.
     * @return int
     */
    public function filter_max_occurrences($value) {
        return (int) simple_events_get_setting('recur_max_occurrences', $value);
    }

    /**
     * Filter callback: cap on the "never"-series horizon in months.
     *
     * @param int $value Default value.
     * @return int
     */
    public function filter_max_horizon_months($value) {
        return (int) simple_events_get_setting('recur_max_horizon_months', $value);
    }

    /**
     * Handle the "Clear cache now" admin-post action.
     */
    public function handle_clear_cache() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to do that.', 'simple_events'));
        }

        check_admin_referer('simple_events_clear_cache');

        if (function_exists('simple_events_clear_all_transients')) {
            simple_events_clear_all_transients();
        }

        wp_safe_redirect(add_query_arg(
            array('page' => self::PAGE, 'sec_cache_cleared' => '1'),
            admin_url('edit.php?post_type=simple-events')
        ));
        exit;
    }

    /**
     * Render the settings page.
     */
    public function render_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $settings = simple_events_get_settings();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Events Settings', 'simple_events'); ?></h1>

            <?php if (isset($_GET['sec_cache_cleared'])) : ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php echo esc_html__('Event cache cleared.', 'simple_events'); ?></p>
                </div>
            <?php endif; ?>

            <form method="post" action="options.php">
                <?php settings_fields(self::PAGE); ?>

                <h2><?php echo esc_html__('Display formatting', 'simple_events'); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php echo esc_html__('Date format', 'simple_events'); ?></th>
                        <td>
                            <input type="text" class="regular-text" name="<?php echo esc_attr(self::OPTION); ?>[date_format]" value="<?php echo esc_attr($settings['date_format']); ?>" />
                            <p class="description">
                                <?php echo esc_html__('PHP date format used on the front end.', 'simple_events'); ?>
                                <?php
                                printf(
                                    /* translators: %s: example formatted date */
                                    esc_html__('Preview: %s', 'simple_events'),
                                    '<strong>' . esc_html(wp_date($settings['date_format'])) . '</strong>'
                                );
                                ?>
                                <br />
                                <?php
                                $presets = array('l, F j, Y', 'F j, Y', 'm/d/Y', 'Y-m-d', 'D, M j');
                                $examples = array();
                                foreach ($presets as $preset) {
                                    $examples[] = esc_html($preset) . ' &rarr; ' . esc_html(wp_date($preset));
                                }
                                echo wp_kses_post(implode(' &nbsp;|&nbsp; ', $examples));
                                ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Time format', 'simple_events'); ?></th>
                        <td>
                            <label><input type="radio" name="<?php echo esc_attr(self::OPTION); ?>[time_format]" value="12" <?php checked($settings['time_format'], '12'); ?> /> <?php echo esc_html__('12-hour (2:30 pm)', 'simple_events'); ?></label><br />
                            <label><input type="radio" name="<?php echo esc_attr(self::OPTION); ?>[time_format]" value="24" <?php checked($settings['time_format'], '24'); ?> /> <?php echo esc_html__('24-hour (14:30)', 'simple_events'); ?></label>
                        </td>
                    </tr>
                </table>

                <h2><?php echo esc_html__('Display defaults', 'simple_events'); ?></h2>
                <p class="description"><?php echo esc_html__('These are defaults for the [sec_events] shortcode and archives. Shortcode attributes still override them per instance.', 'simple_events'); ?></p>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php echo esc_html__('Events per page', 'simple_events'); ?></th>
                        <td><input type="number" min="1" max="50" name="<?php echo esc_attr(self::OPTION); ?>[posts_per_page]" value="<?php echo esc_attr($settings['posts_per_page']); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Default sort order', 'simple_events'); ?></th>
                        <td>
                            <select name="<?php echo esc_attr(self::OPTION); ?>[order]">
                                <option value="ASC" <?php selected($settings['order'], 'ASC'); ?>><?php echo esc_html__('Ascending (soonest first)', 'simple_events'); ?></option>
                                <option value="DESC" <?php selected($settings['order'], 'DESC'); ?>><?php echo esc_html__('Descending (latest first)', 'simple_events'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <?php
                    $toggles = array(
                        'show_past'     => __('Show past events by default', 'simple_events'),
                        'show_time'     => __('Show event time', 'simple_events'),
                        'show_excerpt'  => __('Show excerpt', 'simple_events'),
                        'show_location' => __('Show location', 'simple_events'),
                        'show_footer'   => __('Show card footer / read-more', 'simple_events'),
                    );
                    foreach ($toggles as $key => $label) :
                        ?>
                        <tr>
                            <th scope="row"><?php echo esc_html($label); ?></th>
                            <td><label><input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[<?php echo esc_attr($key); ?>]" value="yes" <?php checked($settings[$key], 'yes'); ?> /> <?php echo esc_html__('Enabled', 'simple_events'); ?></label></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <th scope="row"><?php echo esc_html__('"Load more" batch size', 'simple_events'); ?></th>
                        <td>
                            <input type="number" min="1" max="50" name="<?php echo esc_attr(self::OPTION); ?>[load_increment]" value="<?php echo esc_attr($settings['load_increment']); ?>" />
                            <p class="description"><?php echo esc_html__('How many events to load per "load more" / infinite-scroll request.', 'simple_events'); ?></p>
                        </td>
                    </tr>
                </table>

                <h2><?php echo esc_html__('Empty state', 'simple_events'); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php echo esc_html__('Heading', 'simple_events'); ?></th>
                        <td><input type="text" class="regular-text" name="<?php echo esc_attr(self::OPTION); ?>[empty_state_heading]" value="<?php echo esc_attr($settings['empty_state_heading']); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Message', 'simple_events'); ?></th>
                        <td>
                            <input type="text" class="large-text" name="<?php echo esc_attr(self::OPTION); ?>[empty_state_text]" value="<?php echo esc_attr($settings['empty_state_text']); ?>" />
                            <p class="description"><?php echo esc_html__('Leave blank to use the context-aware default message.', 'simple_events'); ?></p>
                        </td>
                    </tr>
                </table>

                <h2><?php echo esc_html__('SEO', 'simple_events'); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php echo esc_html__('schema.org JSON-LD', 'simple_events'); ?></th>
                        <td><label><input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[enable_schema]" value="yes" <?php checked($settings['enable_schema'], 'yes'); ?> /> <?php echo esc_html__('Output Event structured data on cards and single event pages', 'simple_events'); ?></label></td>
                    </tr>
                </table>

                <h2><?php echo esc_html__('Recurrence limits', 'simple_events'); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php echo esc_html__('Max occurrences per series', 'simple_events'); ?></th>
                        <td><input type="number" min="1" name="<?php echo esc_attr(self::OPTION); ?>[recur_max_occurrences]" value="<?php echo esc_attr($settings['recur_max_occurrences']); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Max horizon (months) for "never" series', 'simple_events'); ?></th>
                        <td><input type="number" min="1" name="<?php echo esc_attr(self::OPTION); ?>[recur_max_horizon_months]" value="<?php echo esc_attr($settings['recur_max_horizon_months']); ?>" /></td>
                    </tr>
                </table>

                <h2><?php echo esc_html__('Cache', 'simple_events'); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php echo esc_html__('Cache lifetime (minutes)', 'simple_events'); ?></th>
                        <td><input type="number" min="1" max="1440" name="<?php echo esc_attr(self::OPTION); ?>[cache_ttl]" value="<?php echo esc_attr($settings['cache_ttl']); ?>" /></td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>

            <hr />
            <h2><?php echo esc_html__('Maintenance', 'simple_events'); ?></h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="simple_events_clear_cache" />
                <?php wp_nonce_field('simple_events_clear_cache'); ?>
                <?php submit_button(__('Clear cache now', 'simple_events'), 'secondary', 'submit', false); ?>
                <p class="description"><?php echo esc_html__('Clears all cached event listings immediately.', 'simple_events'); ?></p>
            </form>
        </div>
        <?php
    }
}
