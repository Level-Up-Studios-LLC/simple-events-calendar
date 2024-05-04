<?php

/**
 * Registers the 'simple-events-cat' taxonomy for the 'simple-events' post type.
 *
 * This function is hooked to the 'init' action with a priority of 10.
 */
if (!function_exists('simple_events_category')) {
    function simple_events_category()
    {
        // Define the labels for the taxonomy.
        $labels = [
            'name'              => __('Event Categories', PLUGIN_TEXT_DOMAIN),
            'singular_name'     => __('Event Category', PLUGIN_TEXT_DOMAIN),
            'menu_name'         => __('Event Categories', PLUGIN_TEXT_DOMAIN)
        ];
        // Define the arguments for the taxonomy.
        $args = [
            'labels'            => $labels,
            'hierarchical'      => true,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tag_cloud'    => true,
            'show_in_rest'      => true
        ];

        // Register the 'simple-events-cat' taxonomy with the provided arguments.
        register_taxonomy('simple-events-cat', ['simple-events'], $args);
    }
    // Hook the 'simple_events_category' function to the 'init' action with a priority of 10.
    add_action('init', 'simple_events_category', 10);
}
