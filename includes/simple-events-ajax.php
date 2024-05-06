<?php

/**
 * Handles AJAX request to load more events.
 *
 * This function is called when a user scrolls to the of the page to load more events using AJAX.
 * It retrieves more events based on the offset and renders them using the template-parts/content-event-card.php template.
 *
 * @return void
 */
function ajax_load_more_events()
{
    // Verify nonce
    check_ajax_referer('load_more_events_nonce', 'nonce'); // Security check

    // Get the offset from the AJAX request
    $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;

    // Generate a unique transient key based on the offset
    $transient_key = 'simple_events_ajax_' . $offset;

    // Retrieve the events HTML from the transient
    $events_html = get_transient($transient_key);

    if (false === $events_html) {
        // Set up the arguments for the WP_Query
        $args = array(
            'post_type'       => 'simple-events',
            'post_status'     => 'publish',
            'posts_per_page'  => 6,
            'offset'          => $offset,
            'orderby'         => 'meta_value_num', // Order events by date
            'order'           => 'ASC',
            'meta_query'      => array(
                array(
                    'key'     => 'event_date',
                    'compare' => '>=',
                    'value'   => date("Ymd"),
                    'type'    => 'DATE'
                )
            )
        );

        // Get the query
        $the_query = new WP_Query($args);

        // Start output buffering
        ob_start();

        // Check if there are any posts
        if ($the_query->have_posts()) {
            // Loop through the posts and render the template for each one
            while ($the_query->have_posts()) {
                $the_query->the_post();

                // Get the data for the current post
                $post_data = array(
                    'title'      => get_the_title(),
                    'permalink'  => get_permalink(),
                    'thumbnail'  => get_the_post_thumbnail_url(get_the_ID(), 'medium_large'),
                    'excerpt'    => wp_trim_words(get_the_excerpt(), 30, '...'),
                    'date'       => get_field('event_date'),
                    'start_time' => get_field('event_start_time'),
                    'end_time'   => get_field('event_end_time')
                );

                // Render the template for each event
                include(PLUGIN_DIR . '/template-parts/content-event-card.php');
            }
        } else {
            // No more events to display
            echo 'No more events!';
        }

        // Get the buffered output and clear the buffer
        $events_html = ob_get_clean();

        // Store the events HTML in the transient
        set_transient($transient_key, $events_html, HOUR_IN_SECONDS);

        // Reset the post data
        wp_reset_postdata();
    }

    // Return the events HTML
    echo $events_html;

    // Terminate the AJAX request
    wp_die();
}

add_action('wp_ajax_load_more_events', 'ajax_load_more_events');
add_action('wp_ajax_nopriv_load_more_events', 'ajax_load_more_events');
