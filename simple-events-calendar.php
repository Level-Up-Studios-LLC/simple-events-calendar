<?php

/**
 * Plugin Name: Simple Events Calendar
 * Plugin URI: https://github.com/Level-Up-Studios-LLC/simple-events-calendar
 * Description: As it says in its name, it's just a simple events calendar plugin for WordPress.
 * Version: 2.1.2
 * Requires at least: 6.0
 * Requires PHP: 7.4
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
define('PLUGIN_VERSION', '2.1.2');

/**
 * Detecting if the ACF PRO Plugin Is Installed
 */
if (!function_exists('is_plugin_active')) {
    include_once ABSPATH . 'wp-admin/includes/plugin.php';
}

// Check if ACF PRO is active
if (is_plugin_active('advanced-custom-fields-pro/acf.php')) {
    // Abort all bundling, ACF PRO plugin takes priority
    return;
}

// Check if another plugin or theme has bundled ACF
if (defined('MY_ACF_PATH')) {
    return;
}

define('MY_ACF_PATH', PLUGIN_DIR . '/includes/acf/');
define('MY_ACF_URL', PLUGIN_URL . '/includes/acf/');

// Include the ACF plugin.
include_once(MY_ACF_PATH . 'acf.php');

// Customize the URL setting to fix incorrect asset URLs.
add_filter('acf/settings/url', function ($url) {
    return MY_ACF_URL;
});

// Check if the ACF free plugin is activated
if (is_plugin_active('advanced-custom-fields/acf.php')) {
    // Free plugin activated, show notice
    add_action('admin_notices', function () {
?>
        <div class="updated" style="border-left: 4px solid #ffba00;">
            <p>The ACF plugin cannot be activated at the same time as Third-Party Product and has been deactivated. Please keep ACF installed to allow you to use ACF functionality.</p>
        </div>
<?php
    }, 99);

    // Disable ACF free plugin
    deactivate_plugins('advanced-custom-fields/acf.php');
}

/**
 * Enqueues the styles and scripts for the Simple Events plugin.
 *
 * This function is hooked to the 'wp_enqueue_scripts' action.
 */
function enqueue_simple_events_scripts()
{
    // Enqueue the stylesheet for the Simple Events plugin.
    wp_enqueue_style(
        'simple-events-style', // Handle.
        PLUGIN_ASSETS . '/css/simple-events.css', // Source URL.
        array(), // Dependencies.
        PLUGIN_VERSION // Version.
    );

    // Enqueue the JavaScript for the Simple Events plugin.
    wp_enqueue_script(
        'simple-events-script', // Handle.
        PLUGIN_ASSETS . '/js/simple-events.js', // Source URL.
        array('jquery'), // Dependencies.
        PLUGIN_VERSION, // Version.
        true // In footer.
    );

    // Localize the JavaScript for AJAX.
    wp_localize_script(
        'simple-events-script', // Handle.
        'ajax_params', // Object name.
        array(
            'ajaxurl' => admin_url('admin-ajax.php'), // Ajax URL.
            'nonce'   => wp_create_nonce('load_more_events_nonce') // Nonce for security.
        )
    );
}
add_action('wp_enqueue_scripts', 'enqueue_simple_events_scripts');

require 'includes/acf-json.php';
require 'includes/acf-settings-page.php';
require 'includes/acf-restricted-access.php';
require 'includes/simple-events-post-type.php';
require 'includes/simple-events-taxonomies.php';
require 'includes/simple-events-admin-columns.php';
require 'includes/simple-events-shortcode.php';
require 'includes/simple-events-ajax.php';
