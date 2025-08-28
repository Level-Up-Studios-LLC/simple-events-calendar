<?php

/**
 * Plugin Name: Simple Events Calendar
 * Plugin URI: https://github.com/Level-Up-Studios-LLC/simple-events-calendar
 * Description: A simple events calendar plugin for WordPress. Requires Advanced Custom Fields (Free or Pro).
 * Version: 3.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Requires Plugins: advanced-custom-fields
 * Author: Level Up Studios, LLC
 * Author URI: https://www.levelupstudios.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: simple_events
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

define('PLUGIN_TEXT_DOMAIN', 'simple_events');
define('PLUGIN_DIR', __DIR__);
define('PLUGIN_URL', untrailingslashit(plugin_dir_url(__FILE__)));
define('PLUGIN_ASSETS', PLUGIN_URL . '/assets');
define('PLUGIN_VERSION', '3.0.0');

/**
 * Include WordPress plugin functions if not already available
 */
if (!function_exists('is_plugin_active')) {
    include_once ABSPATH . 'wp-admin/includes/plugin.php';
}

/**
 * Check if ACF is properly installed and activated
 * Uses the most reliable detection method
 *
 * @return bool True if ACF is available and functional
 */
function simple_events_check_acf_dependency()
{
    // Method 1: Check for acf_get_setting function (most reliable)
    if (function_exists('acf_get_setting')) {
        return true;
    }

    // Method 2: Check for core ACF functions
    if (function_exists('acf_add_local_field_group')) {
        return true;
    }

    // Method 3: Check for ACF constants (ACF is loaded)
    if (defined('ACF_VERSION')) {
        return true;
    }

    // Method 4: Check for ACF class
    if (class_exists('ACF')) {
        return true;
    }

    return false;
}

/**
 * Get ACF version and type information
 * Uses reliable detection based on acf_get_setting
 *
 * @return array ACF version details
 */
function simple_events_get_acf_version_info()
{
    $info = array(
        'is_available' => false,
        'version' => '',
        'is_pro' => false,
        'type' => 'Not Available',
        'detection_method' => ''
    );

    // Primary method: Use acf_get_setting (most reliable)
    if (function_exists('acf_get_setting')) {
        $info['is_available'] = true;
        $info['detection_method'] = 'acf_get_setting';

        try {
            $version = acf_get_setting('version');
            if ($version) {
                $info['version'] = $version;

                // Check if it's Pro version - Pro versions contain 'PRO' in version string
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
    // Fallback method: Check constants
    elseif (defined('ACF_PRO') && ACF_PRO) {
        $info['is_available'] = true;
        $info['is_pro'] = true;
        $info['type'] = 'ACF Pro';
        $info['detection_method'] = 'ACF_PRO constant';
        $info['version'] = defined('ACF_VERSION') ? ACF_VERSION : 'Unknown';
    } elseif (defined('ACF_VERSION')) {
        $info['is_available'] = true;
        $info['is_pro'] = false;
        $info['type'] = 'ACF Free';
        $info['detection_method'] = 'ACF_VERSION constant';
        $info['version'] = ACF_VERSION;
    }
    // Last resort: Check if ACF functions exist
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
 * @return array Status information about ACF
 */
function simple_events_get_acf_status()
{
    $acf_pro_path = WP_PLUGIN_DIR . '/advanced-custom-fields-pro/acf.php';
    $acf_free_path = WP_PLUGIN_DIR . '/advanced-custom-fields/acf.php';

    // Get active plugins
    $active_plugins = get_option('active_plugins', array());
    $network_active_plugins = is_multisite() ? get_site_option('active_sitewide_plugins', array()) : array();

    // Get ACF version info using the reliable method
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
 * Display detailed admin notice when ACF dependency is not met
 */
function simple_events_acf_dependency_notice()
{
    if (!current_user_can('activate_plugins')) {
        return;
    }

    $status = simple_events_get_acf_status();
    $install_url = admin_url('plugin-install.php?s=advanced+custom+fields&tab=search&type=term');
    $plugins_url = admin_url('plugins.php');

?>
    <div class="notice notice-error">
        <h3>Simple Events Calendar - Dependency Error</h3>

        <?php if (!$status['pro_installed'] && !$status['free_installed']): ?>
            <p><strong>Advanced Custom Fields is not installed.</strong></p>
            <p>Simple Events Calendar requires ACF to manage event data. Please install either the free or pro version.</p>
            <p>
                <a href="<?php echo esc_url($install_url); ?>" class="button button-primary">Install ACF Free</a>
                <a href="https://www.advancedcustomfields.com/pro/" class="button" target="_blank">Get ACF Pro</a>
            </p>

        <?php elseif (($status['pro_installed'] && !$status['pro_active']) || ($status['free_installed'] && !$status['free_active'])): ?>
            <p><strong>Advanced Custom Fields is installed but not activated.</strong></p>
            <p>Please activate ACF to use Simple Events Calendar.</p>
            <p>
                <a href="<?php echo esc_url($plugins_url); ?>" class="button button-primary">Go to Plugins</a>
            </p>

        <?php else: ?>
            <p><strong>Advanced Custom Fields detection results:</strong></p>
            <?php
            $acf_info = $status['acf_version_info'];
            if ($acf_info['is_available']):
            ?>
                <p>✅ ACF is loaded and functional!</p>
                <p><strong>Type:</strong> <?php echo esc_html($acf_info['type']); ?></p>
                <p><strong>Version:</strong> <?php echo esc_html($acf_info['version']); ?></p>
                <p>If you're seeing this message, there might be a plugin loading order issue.</p>
            <?php else: ?>
                <p>❌ ACF appears to be installed but is not fully loaded.</p>
                <p>This could be due to a plugin conflict or loading order issue. Try:</p>
                <ul style="margin-left: 20px;">
                    <li>Deactivating and reactivating ACF</li>
                    <li>Checking for plugin conflicts</li>
                    <li>Ensuring you have the latest version of ACF</li>
                    <li>Checking if ACF is network activated (for multisite)</li>
                </ul>
            <?php endif; ?>

            <details style="margin-top: 15px;">
                <summary><strong>Debug Information (click to expand)</strong></summary>
                <div style="background: #f0f0f0; padding: 10px; margin-top: 10px; font-family: monospace; font-size: 12px;">
                    Pro Installed: <?php echo $status['pro_installed'] ? 'Yes' : 'No'; ?><br>
                    Free Installed: <?php echo $status['free_installed'] ? 'Yes' : 'No'; ?><br>
                    Pro Active: <?php echo $status['pro_active'] ? 'Yes' : 'No'; ?><br>
                    Free Active: <?php echo $status['free_active'] ? 'Yes' : 'No'; ?><br>
                    acf_get_setting Available: <?php echo $status['acf_get_setting_available'] ? 'Yes' : 'No'; ?><br>
                    Functions Available: <?php echo $status['functions_available'] ? 'Yes' : 'No'; ?><br>
                    Class Available: <?php echo $status['class_available'] ? 'Yes' : 'No'; ?><br>
                    Version Defined: <?php echo $status['version_defined'] ? 'Yes' : 'No'; ?><br>
                    Pro Defined: <?php echo $status['pro_defined'] ? 'Yes' : 'No'; ?><br>
                    Path Defined: <?php echo $status['path_defined'] ? 'Yes' : 'No'; ?><br>
                    Is Multisite: <?php echo $status['is_multisite'] ? 'Yes' : 'No'; ?><br>

                    <?php if ($acf_info['is_available']): ?>
                        <br><strong>ACF Detection Results:</strong><br>
                        ACF Available: Yes<br>
                        ACF Type: <?php echo esc_html($acf_info['type']); ?><br>
                        ACF Version: <?php echo esc_html($acf_info['version']); ?><br>
                        Is Pro: <?php echo $acf_info['is_pro'] ? 'Yes' : 'No'; ?><br>
                    <?php else: ?>
                        <br>ACF Available: No<br>
                    <?php endif; ?>

                    <?php if (defined('ACF_VERSION')): ?>
                        <br>ACF_VERSION Constant: <?php echo ACF_VERSION; ?><br>
                    <?php endif; ?>
                    <?php if (defined('ACF_PRO')): ?>
                        ACF_PRO Constant: <?php echo ACF_PRO ? 'True' : 'False'; ?><br>
                    <?php endif; ?>
                </div>
            </details>
        <?php endif; ?>
    </div>
<?php
}

/**
 * Strict activation check - prevents activation without ACF
 */
function simple_events_activation_check()
{
    // Force refresh of plugin cache
    if (function_exists('wp_clean_plugins_cache')) {
        wp_clean_plugins_cache();
    }

    // Check ACF dependency
    if (!simple_events_check_acf_dependency()) {
        // Deactivate this plugin
        deactivate_plugins(plugin_basename(__FILE__));

        // Get detailed status for error message
        $status = simple_events_get_acf_status();

        // Create detailed error message
        $error_message = '<h1>Plugin Activation Error</h1>';
        $error_message .= '<p><strong>Simple Events Calendar</strong> requires Advanced Custom Fields to function.</p>';

        if (!$status['pro_installed'] && !$status['free_installed']) {
            $error_message .= '<p>ACF is not installed. Please install ACF first:</p>';
            $error_message .= '<ul>';
            $error_message .= '<li><a href="' . admin_url('plugin-install.php?s=advanced+custom+fields&tab=search&type=term') . '">Install ACF Free</a></li>';
            $error_message .= '<li><a href="https://www.advancedcustomfields.com/pro/" target="_blank">Get ACF Pro</a></li>';
            $error_message .= '</ul>';
        } elseif (($status['pro_installed'] && !$status['pro_active']) || ($status['free_installed'] && !$status['free_active'])) {
            $error_message .= '<p>ACF is installed but not activated. Please <a href="' . admin_url('plugins.php') . '">activate ACF</a> first.</p>';
        } else {
            $error_message .= '<p>ACF appears to be installed but is not loading properly. Please check for plugin conflicts.</p>';
        }

        $error_message .= '<p><a href="' . admin_url('plugins.php') . '">Return to Plugins</a></p>';

        // Die with detailed error message
        wp_die(
            $error_message,
            'Plugin Dependency Error',
            array('back_link' => true)
        );
    }

    // ACF is available, proceed with activation
    simple_events_create_acf_json_directory();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'simple_events_activation_check');

/**
 * Runtime dependency check - deactivates plugin if ACF becomes unavailable
 */
function simple_events_runtime_dependency_check()
{
    if (!simple_events_check_acf_dependency()) {
        // Deactivate the plugin
        deactivate_plugins(plugin_basename(__FILE__));

        // Show admin notice
        add_action('admin_notices', 'simple_events_acf_dependency_notice');

        // Prevent further execution
        return false;
    }

    return true;
}

/**
 * Plugin deactivation hook
 */
function simple_events_deactivation()
{
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'simple_events_deactivation');

/**
 * Create ACF JSON directory if it doesn't exist
 */
function simple_events_create_acf_json_directory()
{
    $json_dir = PLUGIN_DIR . '/includes/acf-json';

    if (!file_exists($json_dir)) {
        wp_mkdir_p($json_dir);

        // Create index.php file for security
        $index_file = $json_dir . '/index.php';
        if (!file_exists($index_file)) {
            file_put_contents($index_file, '<?php // Silence is golden.');
        }
    }
}

/**
 * Initialize plugin only after confirming ACF is available
 */
function simple_events_init()
{
    // Runtime dependency check
    if (!simple_events_runtime_dependency_check()) {
        return;
    }

    // Load plugin components
    simple_events_load_components();

    // Ensure field groups are registered after a short delay
    // This helps with timing issues
    add_action('wp_loaded', 'simple_events_ensure_field_groups');
}

/**
 * Ensure field groups are properly registered
 * This runs after WordPress is fully loaded
 */
function simple_events_ensure_field_groups()
{
    if (function_exists('acf_add_local_field_group')) {
        // Force re-registration of field groups
        if (function_exists('register_event_details_fields')) {
            register_event_details_fields();
        }
    }
}

/**
 * Load all plugin components
 */
function simple_events_load_components()
{
    $components = array(
        'includes/acf-json.php',
        'includes/acf-settings-page.php',
        'includes/simple-events-post-type.php',
        'includes/simple-events-taxonomies.php',
        'includes/simple-events-admin-columns.php',
        'includes/simple-events-shortcode.php',
        'includes/simple-events-ajax.php',
    );

    foreach ($components as $component) {
        $file_path = PLUGIN_DIR . '/' . $component;
        if (file_exists($file_path)) {
            require_once $file_path;
        }
    }

    // Add query modification for archive pages
    add_action('pre_get_posts', 'simple_events_modify_archive_query');
}

/**
 * Modify the main query for simple-events archive pages
 * Only show current and upcoming events, ordered by date ASC
 *
 * @param WP_Query $query The main query object
 */
function simple_events_modify_archive_query($query)
{
    // Only modify the main query on frontend archive pages
    if (is_admin() || !$query->is_main_query()) {
        return;
    }

    // Only modify simple-events post type archives
    if (!is_post_type_archive('simple-events') && !is_tax('simple-events-cat')) {
        return;
    }

    // Set up the query to only show current and upcoming events
    $today = current_time('Ymd'); // Use WordPress timezone

    // Set ordering by event date (ascending)
    $query->set('orderby', 'meta_value');
    $query->set('order', 'ASC');
    $query->set('meta_key', 'event_date');

    // Add meta query to filter out past events
    $meta_query = array(
        array(
            'key'       => 'event_date',
            'compare'   => '>=',
            'value'     => $today,
            'type'      => 'DATE'
        )
    );

    // If there's already a meta query, merge it
    $existing_meta_query = $query->get('meta_query');
    if (!empty($existing_meta_query)) {
        $meta_query = array_merge($existing_meta_query, $meta_query);
    }

    $query->set('meta_query', $meta_query);
}

/**
 * Enqueue styles and scripts for the Simple Events plugin
 */
function enqueue_simple_events_scripts()
{
    // Only proceed if dependencies are met
    if (!simple_events_check_acf_dependency()) {
        return;
    }

    // Check if we should load scripts
    global $post;

    $should_load = false;

    // Check if we're on events archive or single event
    if (
        is_post_type_archive('simple-events') ||
        is_singular('simple-events') ||
        is_tax('simple-events-cat')
    ) {
        $should_load = true;
    }

    // Check if shortcode is used
    if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'simple_events_calendar')) {
        $should_load = true;
    }

    // Also check for shortcode in widgets or other areas
    if (!$should_load && is_active_widget(false, false, 'text')) {
        $should_load = true; // Load on pages with text widgets (might contain shortcode)
    }

    if (!$should_load) {
        return;
    }

    // Enqueue stylesheet
    wp_enqueue_style(
        'simple-events-style',
        PLUGIN_ASSETS . '/css/simple-events.css',
        array(),
        PLUGIN_VERSION
    );

    // Enqueue JavaScript for infinite scroll
    wp_enqueue_script(
        'simple-events-script',
        PLUGIN_ASSETS . '/js/simple-events.js',
        array('jquery'),
        PLUGIN_VERSION,
        true
    );

    // Localize script for AJAX
    wp_localize_script(
        'simple-events-script',
        'ajax_params',
        array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('load_more_events_nonce'),
            'initial_offset' => 6,
            'load_increment' => 6
        )
    );
}
add_action('wp_enqueue_scripts', 'enqueue_simple_events_scripts');

/**
 * Plugin uninstall cleanup
 */
function simple_events_uninstall()
{
    // Delete all simple-events posts
    $events = get_posts(array(
        'post_type' => 'simple-events',
        'numberposts' => -1,
        'post_status' => 'any'
    ));

    foreach ($events as $event) {
        wp_delete_post($event->ID, true);
    }

    // Delete taxonomies
    $terms = get_terms(array(
        'taxonomy' => 'simple-events-cat',
        'hide_empty' => false
    ));

    if (!is_wp_error($terms)) {
        foreach ($terms as $term) {
            wp_delete_term($term->term_id, 'simple-events-cat');
        }
    }

    // Clean up transients and options
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_simple_events_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_simple_events_%'");

    // Flush rewrite rules
    flush_rewrite_rules();
}
register_uninstall_hook(__FILE__, 'simple_events_uninstall');

/**
 * Add action links to plugin page
 */
function simple_events_action_links($links)
{
    if (!simple_events_check_acf_dependency()) {
        return $links;
    }

    $plugin_links = array(
        '<a href="' . admin_url('edit.php?post_type=simple-events') . '">Events</a>',
        '<a href="' . admin_url('edit.php?post_type=acf-field-group') . '">Field Groups</a>',
    );

    return array_merge($plugin_links, $links);
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'simple_events_action_links');

// Initialize the plugin with strict dependency checking
add_action('plugins_loaded', 'simple_events_init', 20);

// Also initialize when ACF is specifically ready
add_action('acf/init', 'simple_events_init');

// Admin initialization for dependency checks
add_action('admin_init', function () {
    if (is_admin() && current_user_can('activate_plugins')) {
        simple_events_runtime_dependency_check();
    }
});
