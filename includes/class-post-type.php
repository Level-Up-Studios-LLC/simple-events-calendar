<?php

/**
 * Post type registration class for Simple Events Calendar
 *
 * @package Simple_Events_Calendar
 * @since 3.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Simple Events Post Type class
 */
class Simple_Events_Post_Type {

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'register_post_type'), 10);
        add_action('init', array($this, 'register_taxonomies'), 10);
    }

    /**
     * Register the 'simple-events' post type
     *
     * @return void
     */
    public function register_post_type() {
        $labels = array(
            'name'                  => _x('Events', 'Post Type General Name', PLUGIN_TEXT_DOMAIN),
            'singular_name'         => _x('Event', 'Post Type Singular Name', PLUGIN_TEXT_DOMAIN),
            'menu_name'             => __('Events', PLUGIN_TEXT_DOMAIN),
            'name_admin_bar'        => __('Event', PLUGIN_TEXT_DOMAIN),
            'archives'              => __('Event Archives', PLUGIN_TEXT_DOMAIN),
            'attributes'            => __('Event Attributes', PLUGIN_TEXT_DOMAIN),
            'parent_item_colon'     => __('Parent Event:', PLUGIN_TEXT_DOMAIN),
            'all_items'             => __('All Events', PLUGIN_TEXT_DOMAIN),
            'add_new_item'          => __('Add New Event', PLUGIN_TEXT_DOMAIN),
            'add_new'               => __('Add New', PLUGIN_TEXT_DOMAIN),
            'new_item'              => __('New Event', PLUGIN_TEXT_DOMAIN),
            'edit_item'             => __('Edit Event', PLUGIN_TEXT_DOMAIN),
            'update_item'           => __('Update Event', PLUGIN_TEXT_DOMAIN),
            'view_item'             => __('View Event', PLUGIN_TEXT_DOMAIN),
            'view_items'            => __('View Events', PLUGIN_TEXT_DOMAIN),
            'search_items'          => __('Search Events', PLUGIN_TEXT_DOMAIN),
            'not_found'             => __('Not found', PLUGIN_TEXT_DOMAIN),
            'not_found_in_trash'    => __('Not found in Trash', PLUGIN_TEXT_DOMAIN),
            'featured_image'        => __('Featured Image', PLUGIN_TEXT_DOMAIN),
            'set_featured_image'    => __('Set featured image', PLUGIN_TEXT_DOMAIN),
            'remove_featured_image' => __('Remove featured image', PLUGIN_TEXT_DOMAIN),
            'use_featured_image'    => __('Use as featured image', PLUGIN_TEXT_DOMAIN),
            'insert_into_item'      => __('Insert into event', PLUGIN_TEXT_DOMAIN),
            'uploaded_to_this_item' => __('Uploaded to this event', PLUGIN_TEXT_DOMAIN),
            'items_list'            => __('Events list', PLUGIN_TEXT_DOMAIN),
            'items_list_navigation' => __('Events list navigation', PLUGIN_TEXT_DOMAIN),
            'filter_items_list'     => __('Filter events list', PLUGIN_TEXT_DOMAIN),
        );

        $args = array(
            'label'                 => __('Event', PLUGIN_TEXT_DOMAIN),
            'description'           => __('Events for the Simple Events Calendar', PLUGIN_TEXT_DOMAIN),
            'labels'                => $labels,
            'supports'              => array('title', 'editor', 'thumbnail', 'revisions', 'excerpt'),
            'taxonomies'            => array('simple-events-cat'),
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 20,
            'menu_icon'             => 'dashicons-calendar-alt',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'page',
            'show_in_rest'          => true,
            'rest_base'             => 'simple-events',
            'rest_controller_class' => 'WP_REST_Posts_Controller',
            'rewrite'               => array(
                'slug'       => 'events',
                'with_front' => false,
                'pages'      => true,
                'feeds'      => true,
            ),
        );

        register_post_type('simple-events', $args);
    }

    /**
     * Register taxonomies for events
     *
     * @return void
     */
    public function register_taxonomies() {
        $this->register_category_taxonomy();
    }

    /**
     * Register the event category taxonomy
     *
     * @return void
     */
    private function register_category_taxonomy() {
        $labels = array(
            'name'                       => _x('Event Categories', 'Taxonomy General Name', PLUGIN_TEXT_DOMAIN),
            'singular_name'              => _x('Event Category', 'Taxonomy Singular Name', PLUGIN_TEXT_DOMAIN),
            'menu_name'                  => __('Event Categories', PLUGIN_TEXT_DOMAIN),
            'all_items'                  => __('All Event Categories', PLUGIN_TEXT_DOMAIN),
            'parent_item'                => __('Parent Event Category', PLUGIN_TEXT_DOMAIN),
            'parent_item_colon'          => __('Parent Event Category:', PLUGIN_TEXT_DOMAIN),
            'new_item_name'              => __('New Event Category Name', PLUGIN_TEXT_DOMAIN),
            'add_new_item'               => __('Add New Event Category', PLUGIN_TEXT_DOMAIN),
            'edit_item'                  => __('Edit Event Category', PLUGIN_TEXT_DOMAIN),
            'update_item'                => __('Update Event Category', PLUGIN_TEXT_DOMAIN),
            'view_item'                  => __('View Event Category', PLUGIN_TEXT_DOMAIN),
            'separate_items_with_commas' => __('Separate event categories with commas', PLUGIN_TEXT_DOMAIN),
            'add_or_remove_items'        => __('Add or remove event categories', PLUGIN_TEXT_DOMAIN),
            'choose_from_most_used'      => __('Choose from the most used', PLUGIN_TEXT_DOMAIN),
            'popular_items'              => __('Popular Event Categories', PLUGIN_TEXT_DOMAIN),
            'search_items'               => __('Search Event Categories', PLUGIN_TEXT_DOMAIN),
            'not_found'                  => __('Not Found', PLUGIN_TEXT_DOMAIN),
            'no_terms'                   => __('No event categories', PLUGIN_TEXT_DOMAIN),
            'items_list'                 => __('Event categories list', PLUGIN_TEXT_DOMAIN),
            'items_list_navigation'      => __('Event categories list navigation', PLUGIN_TEXT_DOMAIN),
        );

        $args = array(
            'labels'                => $labels,
            'hierarchical'          => true,
            'public'                => true,
            'show_ui'               => true,
            'show_admin_column'     => true,
            'show_in_nav_menus'     => true,
            'show_tagcloud'         => true,
            'show_in_rest'          => true,
            'rest_base'             => 'simple-events-categories',
            'rest_controller_class' => 'WP_REST_Terms_Controller',
            'rewrite'               => array(
                'slug'         => 'event-category',
                'with_front'   => false,
                'hierarchical' => true,
            ),
        );

        register_taxonomy('simple-events-cat', array('simple-events'), $args);
    }

    /**
     * Get post type slug
     *
     * @return string
     */
    public static function get_post_type() {
        return 'simple-events';
    }

    /**
     * Get category taxonomy slug
     *
     * @return string
     */
    public static function get_category_taxonomy() {
        return 'simple-events-cat';
    }

    /**
     * Check if current page is an event page
     *
     * @return bool
     */
    public static function is_event_page() {
        return is_singular('simple-events') ||
               is_post_type_archive('simple-events') ||
               is_tax('simple-events-cat');
    }

    /**
     * Get events query args
     *
     * @param array $args Additional query arguments
     * @return array
     */
    public static function get_events_query_args($args = array()) {
        $defaults = array(
            'post_type'      => 'simple-events',
            'post_status'    => 'publish',
            'posts_per_page' => 6,
            'orderby'        => 'meta_value',
            'order'          => 'ASC',
            'meta_key'       => 'event_date',
            'meta_type'      => 'DATE',
            'meta_query'     => array(
                array(
                    'key'     => 'event_date',
                    'compare' => '>=',
                    'value'   => current_time('Ymd'),
                    'type'    => 'DATE'
                )
            )
        );

        return wp_parse_args($args, $defaults);
    }
}