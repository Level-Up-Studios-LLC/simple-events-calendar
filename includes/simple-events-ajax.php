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

    // Today's date and current time based on the WordPress timezone settings
    $today_date = date('Ymd'); // Current date in 'YYYYMMDD' format
    $current_time = current_time('H:i'); // Current time

    // Basic argument for querying future events based on date
    $date_query = array(
        'key'       => 'event_date',
        'compare'   => '>=',
        'value'     => $today_date,
        'type'      => 'DATE'
    );

    // Meta query for events that have not yet ended today
    $meta_query = array(
        'relation' => 'AND',
        $date_query,
        array(
            'relation' => 'OR',
            array( // This part handles events with a valid end time
                'key'     => 'event_end_time',
                'compare' => '>=',
                'value'   => $current_time,
                'type'    => 'TIME'
            ),
            array( // This handles events where the end time field might be empty
                'key'     => 'event_end_time',
                'compare' => 'NOT EXISTS'
            )
        )
    );

    // Query arguments
    $args = array(
        'post_type'       => 'simple-events',
        'post_status'     => 'publish',
        'posts_per_page'  => 6,
        'offset'          => $offset,
        'orderby'         => 'meta_value_num',
        'order'           => 'ASC',
        'meta_query'      => $meta_query
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
        echo 'No events found';
    }

    // Reset the post data
    wp_reset_postdata();

    // Terminate the AJAX request
    wp_die();
}

add_action('wp_ajax_load_more_events', 'ajax_load_more_events');
add_action('wp_ajax_nopriv_load_more_events', 'ajax_load_more_events');
