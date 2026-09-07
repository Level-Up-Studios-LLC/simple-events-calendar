<?php

/**
 * Plugin Name: Simple Events Calendar
 * Plugin URI: https://github.com/Level-Up-Studios-LLC/simple-events-calendar
 * Description: A simple, responsive events calendar for WordPress. Easily create one-time or recurring events and display them anywhere with shortcodes, Elementor widgets, and one-click Add to Calendar.
 * Version: 5.3.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Level Up Studios, LLC
 * Author URI: https://www.levelupstudios.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: simply-events-calendar
 * Domain Path: /languages
 *
 * @copyright Copyright (C) 2026 Level Up Studios, LLC
 *
 * Simple Events Calendar is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * Simple Events Calendar is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see https://www.gnu.org/licenses/.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Define plugin constants
define('SIMPLE_EVENTS_DIR', __DIR__);
define('SIMPLE_EVENTS_URL', untrailingslashit(plugin_dir_url(__FILE__)));
define('SIMPLE_EVENTS_ASSETS', SIMPLE_EVENTS_URL . '/assets');
define('SIMPLE_EVENTS_VERSION', '5.3.0');
define('SIMPLE_EVENTS_PLUGIN_FILE', __FILE__);
define('SIMPLE_EVENTS_NONCE_ACTION', 'load_more_events_nonce');

// Load the main plugin class
require_once SIMPLE_EVENTS_DIR . '/includes/class-main.php';

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
