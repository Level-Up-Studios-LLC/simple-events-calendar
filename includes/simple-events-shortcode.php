<?php

/**
 * Shortcode functionality for Simple Events Calendar
 *
 * Provides [simple_events_calendar] shortcode with caching and proper
 * error handling.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Retrieve and display a shortcode for the simple events calendar archive
 *
 * @param array $atts The shortcode attributes
 * @return string The HTML markup for the shortcode
 */
function simple_events_calendar_archive_shortcode($atts)
{
    // Set default attributes with validation
    $atts = shortcode_atts(array(
        'posts_per_page' => 6,
        'category'       => '',
        'show_past'      => 'no',
        'order'          => 'ASC',
        'orderby'        => 'event_date',
        'show_time'         => 'yes',
        'show_excerpt'     => 'no',
        'show_location'  => 'no',
        'show_footer'    => 'yes'
    ), $atts, 'simple_events_calendar');

    // Sanitize attributes
    $posts_per_page = absint($atts['posts_per_page']);
    $posts_per_page = ($posts_per_page > 0 && $posts_per_page <= 50) ? $posts_per_page : 6;

    $category = sanitize_text_field($atts['category']);
    $show_past = ($atts['show_past'] === 'yes') ? true : false;
    $order = in_array(strtoupper($atts['order']), ['ASC', 'DESC']) ? strtoupper($atts['order']) : 'ASC';
    $orderby = sanitize_text_field($atts['orderby']);

    // Create cache key based on attributes
    $cache_key = 'simple_events_shortcode_' . md5(serialize($atts));
    $cached_result = get_transient($cache_key);

    // Return cached result if available and not in admin
    if (!is_admin() && $cached_result !== false) {
        return $cached_result;
    }

    // Build base query arguments
    $args = array(
        'post_type'         => 'simple-events',
        'post_status'       => 'publish',
        'posts_per_page'    => $posts_per_page,
        'orderby'           => 'meta_value',
        'order'             => $order,
        'meta_key'          => 'event_date',
        'no_found_rows'     => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    );

    // Add date filtering - only show current and upcoming events
    if (!$show_past) {
        $today = current_time('Ymd'); // Use WordPress timezone
        $args['meta_query'] = array(
            array(
                'key'       => 'event_date',
                'compare'   => '>=',
                'value'     => $today,
                'type'      => 'DATE'
            )
        );
    }

    // Add category filtering if specified
    if (!empty($category)) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'simple-events-cat',
                'field'    => 'slug',
                'terms'    => $category,
            ),
        );
    }

    // Execute query
    $the_query = new WP_Query($args);

    // Start output buffering
    ob_start();

    // Check if there are any posts
    if ($the_query->have_posts()) {
        echo '<div class="simple-events-calendar" data-shortcode="true">';

        // Loop through the posts
        while ($the_query->have_posts()) {
            $the_query->the_post();

            // Prepare post data with validation
            $post_data = array(
                'title'        => get_the_title(),
                'permalink'    => get_permalink(),
                'thumbnail'    => get_the_post_thumbnail_url(get_the_ID(), 'medium_large'),
                'excerpt'      => wp_trim_words(get_the_excerpt(), 30, '...'),
                'date'         => get_field('event_date'),
                'start_time'   => get_field('event_start_time'),
                'end_time'     => get_field('event_end_time'),
                'show_time'    => $atts['show_time'],
                'show_excerpt' => $atts['show_excerpt'],
                'show_location' => $atts['show_location'],
                'show_footer'  => $atts['show_footer']
            );

            // Skip events without required data
            if (empty($post_data['title']) || empty($post_data['date'])) {
                continue;
            }

            // Include the template
            $template_path = PLUGIN_DIR . '/template-parts/content-event-card.php';
            if (file_exists($template_path)) {
                include $template_path;
            } else {
                // Fallback rendering
                simple_events_render_fallback_card($post_data);
            }
        }

        echo '</div>';

        // Add load more functionality hint if there might be more events
        // We'll check if there are potentially more events by running a quick count query
        $count_args = $args;
        $count_args['posts_per_page'] = -1;
        $count_args['fields'] = 'ids';
        $count_query = new WP_Query($count_args);
        $total_events = $count_query->post_count;
    } else {
        // No events message with helpful information
        echo '<div class="simple-events-calendar simple-events-no-events">';
        echo '<div class="simple-events-empty-state">';
        echo '<h3>No Events Found</h3>';

        if (!empty($category)) {
            echo '<p>No events found in the "' . esc_html($category) . '" category.</p>';
        } elseif (!$show_past) {
            echo '<p>No upcoming events scheduled. Check back soon!</p>';
        } else {
            echo '<p>No events have been created yet.</p>';
        }

        // Show admin link if user can manage events
        if (current_user_can('edit_posts')) {
            $admin_url = admin_url('post-new.php?post_type=simple-events');
            echo '<p><a href="' . esc_url($admin_url) . '" class="button">Add New Event</a></p>';
        }

        echo '</div>';
        echo '</div>';
    }

    // Reset the post data
    wp_reset_postdata();

    // Get the output and clean buffer
    $output = ob_get_clean();

    // Cache the result for 15 minutes (don't cache if no events or in admin)
    if (!is_admin() && $the_query->have_posts()) {
        set_transient($cache_key, $output, 15 * MINUTE_IN_SECONDS);
    }

    return $output;
}

/**
 * Clear shortcode cache when events are updated
 *
 * @param int $post_id The post ID being saved
 */
function simple_events_clear_shortcode_cache($post_id)
{
    // Only clear cache for simple-events post type
    if (get_post_type($post_id) !== 'simple-events') {
        return;
    }

    // Clear all shortcode transients
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_simple_events_shortcode_%'");
}

/**
 * Enqueue additional scripts for shortcode functionality
 */
function simple_events_shortcode_scripts()
{
    // Only enqueue if shortcode is present on the page
    global $post;
    if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'simple_events_calendar')) {

        wp_enqueue_script(
            'simple-events-shortcode',
            PLUGIN_ASSETS . '/js/simple-events-shortcode.js',
            array('jquery'),
            PLUGIN_VERSION,
            true
        );

        wp_localize_script(
            'simple-events-shortcode',
            'simple_events_shortcode_params',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('load_more_events_nonce'),
                'loading_text' => __('Loading more events...', PLUGIN_TEXT_DOMAIN),
                'error_text'   => __('Error loading events. Please try again.', PLUGIN_TEXT_DOMAIN),
                'no_more_text' => __('No more events to load.', PLUGIN_TEXT_DOMAIN)
            )
        );
    }
}

// Register the shortcode
add_shortcode('simple_events_calendar', 'simple_events_calendar_archive_shortcode');

// Clear cache when posts are updated
add_action('save_post', 'simple_events_clear_shortcode_cache');
add_action('delete_post', 'simple_events_clear_shortcode_cache');

// Enqueue shortcode-specific scripts
add_action('wp_enqueue_scripts', 'simple_events_shortcode_scripts');

// Clear cache when events are published/unpublished
add_action('transition_post_status', function ($new_status, $old_status, $post) {
    if ($post->post_type === 'simple-events' && $new_status !== $old_status) {
        simple_events_clear_shortcode_cache($post->ID);
    }
}, 10, 3);
