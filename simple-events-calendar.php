<?php

/**
 * Plugin Name: Simple Events Calendar
 * Plugin URI: https://www.lvlupstudios.com/
 * Description: This plugin is to display events/happenings of the Restoration Hotel Rooftop.
 * Version: 1.0.0
 * Requires at least: 5.8
 * Requires PHP: 8.1
 * Author: Level Up Studios, LLC
 * Author URI: https://www.lvlupstudios.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI: https://example.com/my-plugin/
 * Text Domain: simple_events
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

define('PLUGIN_MAIN_FILE', plugin_dir_url(__FILE__));
define('PLUGIN_VERSION', '1.0.0');

function simple_events_calendar_scripts()
{
    wp_enqueue_style('simple-events-styles', PLUGIN_MAIN_FILE . 'assets/css/rh-custom-events-calendar.min.css', array(), PLUGIN_VERSION);
}
add_action('wp_enqueue_scripts', 'rh_custom_events_calendar_scripts');

// Register Events CPT
if (!function_exists('events_post_type')) {

    function events_post_type()
    {

        $labels = array(
            'name' => _x('Events', 'Post Type General Name', 'rh_cpt'),
            'singular_name' => _x('Event', 'Post Type Singular Name', 'rh_cpt'),
            'menu_name' => __('Events', 'rh_cpt'),
            'name_admin_bar' => __('Event', 'rh_cpt'),
            'archives' => __('Event Archives', 'rh_cpt'),
            'attributes' => __('Event Attributes', 'rh_cpt'),
            'parent_item_colon' => __('Parent Item:', 'rh_cpt'),
            'all_items' => __('All Events', 'rh_cpt'),
            'add_new_item' => __('Add New Event', 'rh_cpt'),
            'add_new' => __('Add New', 'rh_cpt'),
            'new_item' => __('New Event', 'rh_cpt'),
            'edit_item' => __('Edit Event', 'rh_cpt'),
            'update_item' => __('Update Event', 'rh_cpt'),
            'view_item' => __('View Event', 'rh_cpt'),
            'view_items' => __('View Events', 'rh_cpt'),
            'search_items' => __('Search Event', 'rh_cpt'),
            'not_found' => __('Not found', 'rh_cpt'),
            'not_found_in_trash' => __('Not found in Trash', 'rh_cpt'),
            'featured_image' => __('Featured Image', 'rh_cpt'),
            'set_featured_image' => __('Set featured image', 'rh_cpt'),
            'remove_featured_image' => __('Remove featured image', 'rh_cpt'),
            'use_featured_image' => __('Use as featured image', 'rh_cpt'),
            'insert_into_item' => __('Insert into event', 'rh_cpt'),
            'uploaded_to_this_item' => __('Uploaded to this event', 'rh_cpt'),
            'items_list' => __('Events list', 'rh_cpt'),
            'items_list_navigation' => __('Events list navigation', 'rh_cpt'),
            'filter_items_list' => __('Filter events list', 'rh_cpt'),
        );

        $args = array(
            'label' => __('Event', 'rh_cpt'),
            'description' => __('The Restoration Hotel Events', 'rh_cpt'),
            'labels' => $labels,
            'supports' => array('title', 'editor', 'thumbnail', 'revisions', 'excerpt'),
            'taxonomies' => array('events-cat'),
            'hierarchical' => false,
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_position' => 20,
            'menu_icon' => 'dashicons-tickets-alt',
            'show_in_admin_bar' => true,
            'show_in_nav_menus' => true,
            'can_export' => true,
            'has_archive' => true,
            'exclude_from_search' => false,
            'publicly_queryable' => true,
            'capability_type' => 'page',
            'show_in_rest' => true,
        );

        register_post_type('events', $args);
    }

    add_action('init', 'events_post_type', 0);
}

// Register Events Category Taxonomy
if (!function_exists('events_category')) {

    function events_category()
    {

        $labels = array(
            'name'                       => _x('Event Categories', 'Taxonomy General Name', 'rh_cpt'),
            'singular_name'              => _x('Event Category', 'Taxonomy Singular Name', 'rh_cpt'),
            'menu_name'                  => __('Event Categories', 'rh_cpt'),
            'all_items'                  => __('All Event Categories', 'rh_cpt'),
            'parent_item'                => __('Parent Category', 'rh_cpt'),
            'parent_item_colon'          => __('Parent Category:', 'rh_cpt'),
            'new_item_name'              => __('New Category Name', 'rh_cpt'),
            'add_new_item'               => __('Add New Category', 'rh_cpt'),
            'edit_item'                  => __('Edit Category', 'rh_cpt'),
            'update_item'                => __('Update Category', 'rh_cpt'),
            'view_item'                  => __('View Item', 'rh_cpt'),
            'separate_items_with_commas' => __('Separate categories with commas', 'rh_cpt'),
            'add_or_remove_items'        => __('Add or remove categories', 'rh_cpt'),
            'choose_from_most_used'      => __('Choose from the most used categories', 'rh_cpt'),
            'popular_items'              => __('Popular Items', 'rh_cpt'),
            'search_items'               => __('Search categories', 'rh_cpt'),
            'not_found'                  => __('Not Found', 'rh_cpt'),
            'no_terms'                   => __('No items', 'rh_cpt'),
            'items_list'                 => __('Items list', 'rh_cpt'),
            'items_list_navigation'      => __('Items list navigation', 'rh_cpt'),
        );

        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => true,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => true,
            'show_tagcloud'              => true,
            'show_in_rest'               => true,
        );

        register_taxonomy('events-cat', array('events'), $args);
    }

    add_action('init', 'events_category', 0);
}

// Custom Events Post Type Admin Columns
if (!function_exists('events_post_type_admin_columns')) {
    // Register custom events post type admin columns
    function events_post_type_admin_columns($columns)
    {
        return array(
            'cb'                    => $columns['cb'],
            'title'                 => $columns['title'],
            'taxonomy-events-cat'   => __('Event Categories', 'rh_cpt'),
            'event_date'            => __('Event Date', 'rh_cpt'),
        );
    }
    add_filter('manage_events_posts_columns', 'events_post_type_admin_columns');

    // Display custom events post type admin columns
    function fill_events_post_type_admin_columns($column, $post_id)
    {
        switch ($column) {
            case 'event_date':
                $time = strtotime(get_field($column)); // ACF custom date field
                $date = date("F d, Y", $time);
                echo $date;
                break;
            case 'taxonomy-events-cat':
                echo get_post_meta($post_id, $column, true);
                break;
        }
    }
    add_action('manage_events_posts_custom_column', 'fill_events_post_type_admin_columns', 10, 2);

    // Sortable custom event date admin column
    function sortable_events_post_type_admin_columns($columns)
    {
        $columns['event_date'] = 'event_date';

        return $columns;
    }
    add_filter('manage_edit-events_sortable_columns', 'sortable_events_post_type_admin_columns');

    // Sort custom field admin column by date
    function events_post_type_admin_columns_orderby($query)
    {
        $orderby = $query->get('orderby');

        switch ($orderby) {
            case 'event_date':
                $query->set('meta_key', 'event_date');
                $query->set('orderby', 'meta_value_num');
                break;
            default:
                break;
        }
    }
    add_action('pre_get_posts', 'events_post_type_admin_columns_orderby');
}

// Events Archive Page
function rh_custom_events_calendar_archive_shortcode($atts)
{
    // Attributes
    $atts = shortcode_atts(
        array('category' => ''),
        $atts,
        'rh_custom_events_calendar'
    );

    if (!empty($atts['category'])) {
        $args = array(
            'post_type'         => 'events',
            'post_status'       => 'publish',
            'posts_per_page'    => -1,
            'orderby'           => 'meta_value_num',
            'order'             => 'ASC',
            'meta_query'        => array(
                array(
                    'key'       => 'event_date',
                    'compare'   => '>=',
                    'value'     => date("Ymd"),
                    'type'      => 'DATE'
                )
            ),
            'tax_query'         => array(
                array(
                    'taxonomy' => 'events-cat',
                    'field' => 'slug',
                    'terms' => array($atts['category'])
                )
            )
        );
    } else {
        $args = array(
            'post_type'         => 'events',
            'post_status'       => 'publish',
            'posts_per_page'    => -1,
            'orderby'           => 'meta_value_num',
            'order'             => 'ASC',
            'meta_query'        => array(
                array(
                    'key'       => 'event_date',
                    'compare'   => '>=',
                    'value'     => date("Ymd"),
                    'type'      => 'DATE'
                )
            )
        );
    }

    $the_query = new WP_Query($args);

    ob_start();

    // Display posts
    if ($the_query->have_posts()) : ?>

        <div class="rh-events-calendar elementor-element elementor-grid-3 elementor-grid-tablet-2 elementor-grid-mobile-1 elementor-posts--thumbnail-top elementor-widget elementor-widget-posts">
            <div class="elementor-widget-container">
                <div class="elementor-posts-container elementor-posts elementor-posts--skin-classic elementor-grid elementor-has-item-ratio">

                    <?php while ($the_query->have_posts()) : $the_query->the_post(); ?>

                        <article class="elementor-post elementor-grid-item events type-events status-publish has-post-thumbnail hentry entry has-media">
                            <a class="elementor-post__thumbnail__link" href="<?php the_permalink() ?>">
                                <div class="elementor-post__thumbnail"><?php the_post_thumbnail('medium_large'); ?></div>
                            </a>
                            <div class="elementor-post__text">
                                <h3 class="elementor-post__title">
                                    <a href="<?php the_permalink() ?>"><?php the_title(); ?></a>
                                </h3>
                                <div class="elementor-post__meta-data">
                                    <span class="elementor-post-date"><?php the_field('event_date'); ?></span>
                                    <span class="elementor-post-time">
                                        <?php
                                        if (!empty(get_field('event_start_time'))) {
                                            echo ' | ' . get_field('event_start_time');
                                        }
                                        if (!empty(get_field('event_end_time'))) {
                                            echo ' - ' . get_field('event_end_time');
                                        }
                                        ?>
                                    </span>
                                </div>
                                <div class="elementor-post__excerpt">
                                    <p><?php the_excerpt(); ?></p>
                                </div>
                            </div>
                        </article>

                    <?php endwhile; ?>

                </div>
            </div>
        </div>

    <?php else : ?>

        <div class="elementor-widget-wrap elementor-element-populated">
            <div class="elementor-element elementor-widget elementor-widget-text-editor" data-element_type="widget" data-widget_type="text-editor.default">
                <div class="elementor-widget-container">
                    <p>There are no events to display.</p>
                </div>
            </div>
        </div>

<?php endif;

    $output = ob_get_contents();

    ob_end_clean();

    wp_reset_query();

    return $output;
}
add_shortcode('rh_custom_events_calendar', 'rh_custom_events_calendar_archive_shortcode');

/**
 * Filter the except length to 20 words.
 *
 * @param int $length Excerpt length.
 * @return int (Maybe) modified excerpt length.
 */
function rh_custom_events_calendar_excerpt_length($length)
{
    return 20;
}
add_filter('excerpt_length', 'rh_custom_events_calendar_excerpt_length', 999);

/**
 * Filter the excerpt "read more" string.
 *
 * @param string $more "Read more" excerpt string.
 * @return string (Maybe) modified "read more" excerpt string.
 */
function rh_custom_events_calendar_excerpt_more($more)
{
    return '...';
}
add_filter('excerpt_more', 'rh_custom_events_calendar_excerpt_more');
