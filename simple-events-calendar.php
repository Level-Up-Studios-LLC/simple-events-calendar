<?php

/**
 * Plugin Name: Simple Events Calendar
 * Plugin URI: https://github.com/Level-Up-Studios-LLC/simple-events-calendar
 * Description: A simple events calendar plugin for WordPress. Requires Advanced Custom Fields (Free or Pro).
 * Version: 4.1.1
 * Requires at least: 6.2
 * Requires PHP: 8.0
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

// Define plugin constants
define('PLUGIN_TEXT_DOMAIN', 'simple_events');
define('PLUGIN_DIR', __DIR__);
define('PLUGIN_URL', untrailingslashit(plugin_dir_url(__FILE__)));
define('PLUGIN_ASSETS', PLUGIN_URL . '/assets');
define('PLUGIN_VERSION', '4.1.1');
define('SIMPLE_EVENTS_PLUGIN_FILE', __FILE__);

// Load the main plugin class
require_once PLUGIN_DIR . '/includes/class-main.php';

/**
 * Initialize the plugin
 *
 * @return Simple_Events_Calendar|null
 */
function simple_events_calendar()
{
    return Simple_Events_Calendar::get_instance();
}

// Initialize the plugin
simple_events_calendar();
