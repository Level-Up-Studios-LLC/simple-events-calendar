<?php

// Check if ACF free is installed
if (!file_exists(WP_PLUGIN_DIR . '/advanced-custom-fields/acf.php')) {
    // Free plugin not installed
    // Hide the ACF admin menu item.
    add_filter('acf/settings/show_admin', '__return_false');
    // Hide the ACF Updates menu
    add_filter('acf/settings/show_updates', '__return_false', 100);
}