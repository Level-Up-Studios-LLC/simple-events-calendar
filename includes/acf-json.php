<?php

/**
 * ACF Local JSON Settings for Simple Events Calendar Plugin
 *
 * This file configures ACF to save and load field groups as JSON files
 * within the plugin directory. This ensures field groups are version-controlled
 * and automatically sync across environments.
 *
 * @link https://www.advancedcustomfields.com/resources/local-json/
 */

// Only proceed if ACF is available
if (!function_exists('acf_add_local_field_group')) {
    return;
}

// Set up JSON save and load paths
add_filter('acf/settings/load_json', 'simple_events_acf_json_load_point');
add_filter('acf/settings/save_json', 'simple_events_acf_json_save_point');

/**
 * Add custom load point for ACF JSON files
 *
 * This tells ACF to also look in our plugin directory for JSON field group files.
 * We don't remove the default path to maintain compatibility with theme/other plugins.
 *
 * @param array $paths Existing JSON load paths
 * @return array Modified paths array
 */
function simple_events_acf_json_load_point($paths)
{
    // Add our plugin's JSON directory to the load paths
    $paths[] = PLUGIN_DIR . '/includes/acf-json';

    return $paths;
}

/**
 * Set custom save point for ACF JSON files created by this plugin
 *
 * This ensures that field groups created for simple-events post type
 * are saved within the plugin directory rather than the theme.
 *
 * @param string $path Default save path
 * @return string Modified save path
 */
function simple_events_acf_json_save_point($path)
{
    // Only change save path for our field groups
    if (isset($_POST['acf_field_group']) && is_array($_POST['acf_field_group'])) {
        $field_group = $_POST['acf_field_group'];

        // Check if this field group is related to simple-events
        if (isset($field_group['location']) && is_array($field_group['location'])) {
            foreach ($field_group['location'] as $location_group) {
                foreach ($location_group as $location_rule) {
                    if (
                        isset($location_rule['param']) &&
                        $location_rule['param'] === 'post_type' &&
                        isset($location_rule['value']) &&
                        $location_rule['value'] === 'simple-events'
                    ) {

                        return PLUGIN_DIR . '/includes/acf-json';
                    }
                }
            }
        }
    }

    // Return default path for other field groups
    return $path;
}

/**
 * Hide ACF admin menus if ACF is bundled with another plugin/theme
 *
 * This prevents conflicts when multiple plugins bundle ACF.
 * Only applies if the free version is not explicitly installed.
 */
function simple_events_maybe_hide_acf_admin()
{
    // Check if ACF free plugin is installed (not just bundled)
    $acf_free_installed = file_exists(WP_PLUGIN_DIR . '/advanced-custom-fields/acf.php');
    $acf_pro_installed = file_exists(WP_PLUGIN_DIR . '/advanced-custom-fields-pro/acf.php');

    // If neither free nor pro is explicitly installed as a plugin,
    // assume ACF is bundled and hide admin menus to avoid confusion
    if (!$acf_free_installed && !$acf_pro_installed) {
        add_filter('acf/settings/show_admin', '__return_false');
        add_filter('acf/settings/show_updates', '__return_false');
    }
}
add_action('acf/init', 'simple_events_maybe_hide_acf_admin');

/**
 * Create the ACF JSON directory if it doesn't exist
 *
 * This ensures the directory exists when ACF tries to save JSON files.
 */
function simple_events_create_acf_json_dir()
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
add_action('acf/init', 'simple_events_create_acf_json_dir');

/**
 * Validate that our required field groups are loaded
 *
 * This function checks if the event details field group is properly loaded.
 * If not, it will trigger a notice for administrators.
 */
function simple_events_validate_field_groups()
{
    if (!is_admin() || !current_user_can('manage_options')) {
        return;
    }

    // Only check on relevant admin pages
    $screen = get_current_screen();
    if (!$screen || !in_array($screen->id, ['dashboard', 'edit-simple-events', 'simple-events'])) {
        return;
    }

    // Wait a bit to ensure field groups are registered
    if (!did_action('wp_loaded')) {
        return;
    }

    // Check if our main field group exists
    $field_group = null;
    if (function_exists('acf_get_field_group')) {
        $field_group = acf_get_field_group('group_event_details');
    }

    if (!$field_group) {
        add_action('admin_notices', function () {
?>
            <div class="notice notice-warning is-dismissible">
                <p><strong>Simple Events Calendar:</strong> Event Details field group is missing. The plugin will now try to create it automatically.</p>
                <p>If this message persists, please try deactivating and reactivating the plugin.</p>
            </div>
<?php
        });

        // Try to register the field group immediately
        if (function_exists('register_event_details_fields')) {
            register_event_details_fields();
        }
    }
}
add_action('admin_init', 'simple_events_validate_field_groups', 20);
