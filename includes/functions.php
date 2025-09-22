<?php

/**
 * Utility functions for Simple Events Calendar
 *
 * @package Simple_Events_Calendar
 * @since 3.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Check if ACF is available and functional
 *
 * @return bool True if ACF is available
 */
function simple_events_check_acf_dependency() {
    if (function_exists('acf_get_setting')) {
        return true;
    }

    if (function_exists('acf_add_local_field_group')) {
        return true;
    }

    if (defined('ACF_VERSION')) {
        return true;
    }

    if (class_exists('ACF')) {
        return true;
    }

    return false;
}

/**
 * Get ACF version and type information
 *
 * @return array ACF version details
 */
function simple_events_get_acf_version_info() {
    $info = array(
        'is_available' => false,
        'version' => '',
        'is_pro' => false,
        'type' => 'Not Available',
        'detection_method' => ''
    );

    if (function_exists('acf_get_setting')) {
        $info['is_available'] = true;
        $info['detection_method'] = 'acf_get_setting';

        try {
            $version = acf_get_setting('version');
            if ($version) {
                $info['version'] = $version;

                if (strpos($version, 'PRO') !== false) {
                    $info['is_pro'] = true;
                    $info['type'] = 'ACF Pro';
                } else {
                    $info['is_pro'] = false;
                    $info['type'] = 'ACF Free';
                }
            } else {
                $info['type'] = 'ACF (version unknown)';
            }
        } catch (Exception $e) {
            $info['type'] = 'ACF (error getting version)';
        }
    }
    elseif (defined('ACF_PRO') && ACF_PRO) {
        $info['is_available'] = true;
        $info['is_pro'] = true;
        $info['type'] = 'ACF Pro';
        $info['detection_method'] = 'ACF_PRO constant';
        $info['version'] = defined('ACF_VERSION') ? ACF_VERSION : 'Unknown';
    }
    elseif (defined('ACF_VERSION')) {
        $info['is_available'] = true;
        $info['is_pro'] = false;
        $info['type'] = 'ACF Free';
        $info['detection_method'] = 'ACF_VERSION constant';
        $info['version'] = ACF_VERSION;
    }
    elseif (function_exists('acf_add_local_field_group')) {
        $info['is_available'] = true;
        $info['type'] = 'ACF (type unknown)';
        $info['detection_method'] = 'function check';
        $info['version'] = 'Unknown';
    }

    return $info;
}

/**
 * Get detailed ACF status for error messages
 *
 * @return array Status information
 */
function simple_events_get_acf_status() {
    $acf_pro_path = WP_PLUGIN_DIR . '/advanced-custom-fields-pro/acf.php';
    $acf_free_path = WP_PLUGIN_DIR . '/advanced-custom-fields/acf.php';

    $active_plugins = get_option('active_plugins', array());
    $network_active_plugins = is_multisite() ? get_site_option('active_sitewide_plugins', array()) : array();

    $acf_info = simple_events_get_acf_version_info();

    return array(
        'pro_installed' => file_exists($acf_pro_path),
        'free_installed' => file_exists($acf_free_path),
        'pro_active' => in_array('advanced-custom-fields-pro/acf.php', $active_plugins) ||
                       isset($network_active_plugins['advanced-custom-fields-pro/acf.php']) ||
                       (function_exists('is_plugin_active') && is_plugin_active('advanced-custom-fields-pro/acf.php')),
        'free_active' => in_array('advanced-custom-fields/acf.php', $active_plugins) ||
                        isset($network_active_plugins['advanced-custom-fields/acf.php']) ||
                        (function_exists('is_plugin_active') && is_plugin_active('advanced-custom-fields/acf.php')),
        'functions_available' => function_exists('acf_add_local_field_group') || function_exists('acf_get_field_groups'),
        'acf_get_setting_available' => function_exists('acf_get_setting'),
        'class_available' => class_exists('ACF') || class_exists('acf'),
        'version_defined' => defined('ACF_VERSION'),
        'pro_defined' => defined('ACF_PRO'),
        'path_defined' => defined('ACF_PATH'),
        'is_multisite' => is_multisite(),
        'active_plugins_count' => count($active_plugins),
        'network_active_count' => count($network_active_plugins),
        'acf_version_info' => $acf_info
    );
}

/**
 * Format event date for display
 *
 * @param string $date_string Date string
 * @param string $format Date format (optional)
 * @return string Formatted date
 */
function simple_events_format_date($date_string, $format = null) {
    if (empty($date_string)) {
        return '';
    }

    if (!$format) {
        $format = get_option('date_format');
    }

    $timestamp = strtotime($date_string);
    if (!$timestamp) {
        return $date_string; // Return original if parsing fails
    }

    return date_i18n($format, $timestamp);
}

/**
 * Format event time for display
 *
 * @param string $start_time Start time
 * @param string $end_time End time (optional)
 * @return string Formatted time range
 */
function simple_events_format_time($start_time, $end_time = '') {
    if (empty($start_time)) {
        return '';
    }

    $formatted = esc_html($start_time);

    if (!empty($end_time)) {
        $formatted .= ' - ' . esc_html($end_time);
    }

    return $formatted;
}

/**
 * Get event status based on date
 *
 * @param string $event_date Event date
 * @return string Status: 'past', 'today', 'upcoming'
 */
function simple_events_get_event_status($event_date) {
    if (empty($event_date)) {
        return 'unknown';
    }

    $event_timestamp = strtotime($event_date);
    $today_timestamp = strtotime(current_time('Y-m-d'));

    if ($event_timestamp < $today_timestamp) {
        return 'past';
    } elseif ($event_timestamp === $today_timestamp) {
        return 'today';
    } else {
        return 'upcoming';
    }
}

/**
 * Get events by status
 *
 * @param string $status Event status: 'past', 'today', 'upcoming'
 * @param array $args Additional query arguments
 * @return WP_Query Events query
 */
function simple_events_get_events_by_status($status, $args = array()) {
    $today = current_time('Ymd');

    $meta_query = array();

    switch ($status) {
        case 'past':
            $meta_query[] = array(
                'key' => 'event_date',
                'value' => $today,
                'compare' => '<',
                'type' => 'DATE'
            );
            break;

        case 'today':
            $meta_query[] = array(
                'key' => 'event_date',
                'value' => $today,
                'compare' => '=',
                'type' => 'DATE'
            );
            break;

        case 'upcoming':
            $meta_query[] = array(
                'key' => 'event_date',
                'value' => $today,
                'compare' => '>',
                'type' => 'DATE'
            );
            break;
    }

    $defaults = array(
        'post_type' => 'simple-events',
        'post_status' => 'publish',
        'meta_key' => 'event_date',
        'orderby' => 'meta_value',
        'meta_type' => 'DATE',
        'order' => ($status === 'past') ? 'DESC' : 'ASC',
        'meta_query' => $meta_query
    );

    $query_args = wp_parse_args($args, $defaults);

    return new WP_Query($query_args);
}

/**
 * Get events in date range
 *
 * @param string $start_date Start date (Y-m-d format)
 * @param string $end_date End date (Y-m-d format)
 * @param array $args Additional query arguments
 * @return WP_Query Events query
 */
function simple_events_get_events_in_range($start_date, $end_date, $args = array()) {
    $defaults = array(
        'post_type' => 'simple-events',
        'post_status' => 'publish',
        'meta_key' => 'event_date',
        'orderby' => 'meta_value',
        'meta_type' => 'DATE',
        'order' => 'ASC',
        'meta_query' => array(
            array(
                'key' => 'event_date',
                'value' => array($start_date, $end_date),
                'compare' => 'BETWEEN',
                'type' => 'DATE'
            )
        )
    );

    $query_args = wp_parse_args($args, $defaults);

    return new WP_Query($query_args);
}

/**
 * Clear all Simple Events transients
 *
 * @return bool True on success
 */
function simple_events_clear_all_transients() {
    global $wpdb;

    $result = $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
            '_transient_simple_events_%',
            '_transient_timeout_simple_events_%'
        )
    );

    return $result !== false;
}

/**
 * Get plugin version
 *
 * @return string Plugin version
 */
function simple_events_get_version() {
    return defined('PLUGIN_VERSION') ? PLUGIN_VERSION : '3.0.0';
}

/**
 * Get plugin directory path
 *
 * @return string Plugin directory path
 */
function simple_events_get_plugin_dir() {
    return defined('PLUGIN_DIR') ? PLUGIN_DIR : plugin_dir_path(__FILE__);
}

/**
 * Get plugin directory URL
 *
 * @return string Plugin directory URL
 */
function simple_events_get_plugin_url() {
    return defined('PLUGIN_URL') ? PLUGIN_URL : plugin_dir_url(__FILE__);
}

/**
 * Get assets URL
 *
 * @return string Assets URL
 */
function simple_events_get_assets_url() {
    return defined('PLUGIN_ASSETS') ? PLUGIN_ASSETS : simple_events_get_plugin_url() . '/assets';
}

/**
 * Log debug message if WP_DEBUG is enabled
 *
 * @param string $message Debug message
 * @param array $context Additional context
 */
function simple_events_debug_log($message, $context = array()) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $log_message = 'Simple Events Calendar: ' . $message;

        if (!empty($context)) {
            $log_message .= ' Context: ' . print_r($context, true);
        }

        error_log($log_message);
    }
}

/**
 * Sanitize and validate shortcode attributes
 *
 * @param array $atts Raw attributes
 * @return array Sanitized attributes
 */
function simple_events_sanitize_shortcode_atts($atts) {
    $defaults = array(
        'posts_per_page' => 6,
        'category' => '',
        'show_past' => 'no',
        'order' => 'ASC',
        'orderby' => 'event_date',
        'show_time' => 'yes',
        'show_excerpt' => 'yes',
        'show_location' => 'yes',
        'show_footer' => 'yes'
    );

    $atts = wp_parse_args($atts, $defaults);

    // Sanitize posts_per_page
    $posts_per_page = absint($atts['posts_per_page']);
    $atts['posts_per_page'] = ($posts_per_page > 0 && $posts_per_page <= 50) ? $posts_per_page : 6;

    // Sanitize text fields
    $atts['category'] = sanitize_text_field($atts['category']);
    $atts['orderby'] = sanitize_text_field($atts['orderby']);

    // Sanitize boolean fields
    $atts['show_past'] = ($atts['show_past'] === 'yes');
    $atts['show_time'] = ($atts['show_time'] !== 'no');
    $atts['show_excerpt'] = ($atts['show_excerpt'] !== 'no');
    $atts['show_location'] = ($atts['show_location'] !== 'no');
    $atts['show_footer'] = ($atts['show_footer'] !== 'no');

    // Validate order
    $atts['order'] = in_array(strtoupper($atts['order']), ['ASC', 'DESC']) ? strtoupper($atts['order']) : 'ASC';

    return $atts;
}

/**
 * Check if current page is an event-related page
 *
 * @return bool True if on event page
 */
function simple_events_is_event_page() {
    return is_singular('simple-events') ||
           is_post_type_archive('simple-events') ||
           is_tax('simple-events-cat');
}

/**
 * Get template part with fallback
 *
 * @param string $template_name Template name
 * @param array $args Template arguments
 * @return void
 */
function simple_events_get_template_part($template_name, $args = array()) {
    $template_path = simple_events_get_plugin_dir() . '/template-parts/' . $template_name . '.php';

    if (file_exists($template_path)) {
        // Extract args for use in template
        if (!empty($args)) {
            extract($args);
        }

        include $template_path;
    } else {
        simple_events_debug_log("Template not found: {$template_name}");
    }
}