<?php

/**
 * Registers the 'simple-events' post type if it doesn't exist yet.
 *
 * This function registers the 'simple-events' post type with the provided arguments.
 *
 * @return void
 */
if (!function_exists('simple_events_post_type')) {

    /**
     * Registers the 'simple-events' post type.
     *
     * @return void
     */
    function register_simple_events_post_type()
    {

        // Define the labels for the post type.
        $postTypeLabels = [
            'name'                  => _x('Events', 'Post Type General Name', PLUGIN_TEXT_DOMAIN),
            'singular_name'         => _x('Event', 'Post Type Singular Name', PLUGIN_TEXT_DOMAIN),
            'menu_name'             => __('Events', PLUGIN_TEXT_DOMAIN),
            'archives'              => __('Event Archives', PLUGIN_TEXT_DOMAIN),
        ];

        // Define the arguments for the post type.
        $postTypeArgs = [
            'label'                 => __('Event', PLUGIN_TEXT_DOMAIN),
            'labels'                => $postTypeLabels,
            'supports'              => ['title', 'editor', 'thumbnail', 'revisions', 'excerpt'],
            'taxonomies'            => ['simple-events-cat'],
            'public'                => true,
            'show_ui'               => true,
            'menu_position'         => 20,
            'menu_icon'             => 'dashicons-calendar-alt',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'publicly_queryable'    => true,
            'capability_type'       => 'page',
            'show_in_rest'          => true,
        ];

        // Register the 'simple-events' post type with the provided arguments.
        register_post_type('simple-events', $postTypeArgs);
    }

    // Add the 'simple_events_post_type' function to the 'init' action hook.
    add_action('init', 'register_simple_events_post_type', 10);

}
