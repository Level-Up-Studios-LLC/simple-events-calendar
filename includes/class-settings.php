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
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));

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

        // Date format: a known preset, or a custom PHP format when "custom" is chosen.
        $allowed_presets = array('l, F j, Y', 'F j, Y', 'm/d/Y', 'M j, Y');
        $preset = isset($input['date_format_preset']) ? (string) $input['date_format_preset'] : '';
        if ('custom' === $preset) {
            $custom = isset($input['date_format_custom']) ? trim((string) $input['date_format_custom']) : '';
            $clean['date_format'] = ('' !== $custom) ? sanitize_text_field($custom) : $defaults['date_format'];
        } elseif (in_array($preset, $allowed_presets, true)) {
            $clean['date_format'] = $preset;
        } else {
            $clean['date_format'] = $defaults['date_format'];
        }

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
     * Enqueue the settings-page script (date-format preset toggle + preview).
     *
     * @param string $hook Current admin page hook suffix.
     */
    public function enqueue_assets($hook) {
        // Submenu pages under a CPT use the "<post_type>_page_<slug>" hook form.
        if ('simple-events_page_' . self::PAGE !== $hook) {
            return;
        }

        wp_enqueue_script(
            'simple-events-settings',
            PLUGIN_ASSETS . '/js/simple-events-settings.js',
            array(),
            PLUGIN_VERSION,
            true
        );
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
                            <?php
                            $presets = array(
                                'l, F j, Y' => __('Weekday, Month Day, Year', 'simple_events'),
                                'F j, Y'    => __('Month Day, Year', 'simple_events'),
                                'm/d/Y'     => __('MM/DD/YYYY', 'simple_events'),
                                'M j, Y'    => __('Abbreviated Month Day, Year', 'simple_events'),
                            );
                            $current  = (string) $settings['date_format'];
                            $is_preset = array_key_exists($current, $presets);
                            ?>
                            <select id="sec-date-format-preset" name="<?php echo esc_attr(self::OPTION); ?>[date_format_preset]">
                                <?php foreach ($presets as $fmt => $label) : ?>
                                    <option value="<?php echo esc_attr($fmt); ?>" <?php selected($is_preset && $current === $fmt); ?>>
                                        <?php echo esc_html($label . ' — ' . wp_date($fmt)); ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="custom" <?php selected(!$is_preset); ?>><?php echo esc_html__('Custom…', 'simple_events'); ?></option>
                            </select>
                            <span id="sec-date-format-custom-wrap" style="<?php echo esc_attr($is_preset ? 'display:none;' : ''); ?>">
                                <input type="text" id="sec-date-format-custom" class="regular-text" name="<?php echo esc_attr(self::OPTION); ?>[date_format_custom]" value="<?php echo esc_attr(!$is_preset ? $current : ''); ?>" placeholder="l, F j, Y" />
                            </span>
                            <p class="description">
                                <?php
                                printf(
                                    /* translators: %s: example formatted date */
                                    esc_html__('Preview: %s', 'simple_events'),
                                    '<strong id="sec-date-format-preview">' . esc_html(wp_date($current)) . '</strong>'
                                );
                                ?>
                                <br />
                                <?php echo esc_html__('Choose a preset, or pick "Custom…" to enter a PHP date format.', 'simple_events'); ?>
                                <a href="https://wordpress.org/documentation/article/customize-date-and-time-format/" target="_blank" rel="noopener noreferrer"><?php echo esc_html__('Format reference', 'simple_events'); ?></a>
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
                <p class="description"><?php echo esc_html__('Defaults for the [sec_events] shortcode and the event archives; shortcode attributes override them per instance. On archive pages the page size uses the "Load more" batch size below (so infinite-scroll offsets line up), not "Events per page".', 'simple_events'); ?></p>
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
