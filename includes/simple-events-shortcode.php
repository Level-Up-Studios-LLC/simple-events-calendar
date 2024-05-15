<?php

/**
 * Retrieve and display a shortcode for the simple events calendar archive.
 *
 * @param array $atts The shortcode attributes.
 * @return string The HTML markup for the shortcode.
 */
function simple_events_calendar_archive_shortcode($atts)
{
    // Set default attributes
    $atts = shortcode_atts(array(
        'posts_per_page' => 9, // Number of events to display per page
    ), $atts, 'simple_events_calendar');

    // Today's date and current time based on the WordPress timezone settings
    $today_date = date('Ymd'); // Current date in 'YYYYMMDD' format
    $current_time = current_time('H:i'); // Current time

    // Date query for events starting from today
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
        'post_type'         => 'simple-events',
        'post_status'       => 'publish',
        'posts_per_page'    => $atts['posts_per_page'],
        'orderby'           => 'meta_value_num',
        'order'             => 'ASC',
        'meta_query'        => $meta_query
    );

    // Get the query
    $the_query = new WP_Query($args);

    // Start output buffering
    ob_start();

    // Check if there are any posts
    if ($the_query->have_posts()) {
        // Container for the events
        echo '<div class="simple-events-calendar">';

        // Loop through the posts
        while ($the_query->have_posts()) {
            $the_query->the_post();

            // Get the data for the current post
            $post_data = array(
                'title'      => get_the_title(), // The event title
                'permalink'  => get_permalink(), // The event permalink
                'thumbnail'  => get_the_post_thumbnail_url(get_the_ID(), 'medium_large'), // The event thumbnail
                'excerpt'    => wp_trim_words(get_the_excerpt(), 30, '...'), // Trim the event excerpt to 30 words
                'date'       => get_field('event_date'), // The event date
                'start_time' => get_field('event_start_time'), // The event start time
                'end_time'   => get_field('event_end_time') // The event end time
            );

            // Render the template for each event
            include PLUGIN_DIR . '/template-parts/content-event-card.php';
        }

        // Close the container for the events
        echo '</div>';
    } else {
        // No events message
        echo '<div class="simple-events-calendar">There are no more events to display.</div>';
    }

    // Reset the post data
    wp_reset_postdata();

    // Return the cached or freshly generated HTML
    return ob_get_clean();
}

add_shortcode('simple_events_calendar', 'simple_events_calendar_archive_shortcode');
