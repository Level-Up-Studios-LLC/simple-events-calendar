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

    // Create a unique key for the transient
    $transient_key = 'simple_events_initial_' . md5(serialize($atts));

    // Retrieve the cached HTML or generate it if it's not cached
    $events_html = get_transient($transient_key);
    if (false === $events_html) {
        // Set query arguments
        $args = array(
            'post_type'       => 'simple-events',
            'post_status'     => 'publish',
            'posts_per_page'  => $atts['posts_per_page'],
            'orderby'         => 'meta_value_num', // Order by event date
            'order'           => 'ASC',
            'meta_query'      => array( // Retrieve only events with a date greater than or equal to today
                array(
                    'key'     => 'event_date', // The meta key for event date
                    'compare' => '>=', // Compare the value to the current date
                    'value'   => date("Ymd"), // The current date
                    'type'    => 'DATE' // The meta value type
                )
            )
        );

        // Get the query
        $the_query = new WP_Query($args);

        // Start output buffering
        ob_start();

        // Check if there are any posts
        if ($the_query->have_posts()) {
            // Container for the events
            echo '<div id="simple-events-container" class="simple-events-calendar">';

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

            // Loading message
            echo '<div id="load-more-events" class="load-more" style="display: none;">Loading...</div>';
        } else {
            // No events message
            echo '<div id="simple-events-container" class="simple-events-calendar">There are no more events to display.</div>';
        }

        // Get the buffered content into a variable
        $events_html = ob_get_clean();

        // Cache the output for 1 hour
        set_transient($transient_key, $events_html, HOUR_IN_SECONDS);

        wp_reset_postdata();
    }

    // Return the cached or freshly generated HTML
    return $events_html;
}

add_shortcode('simple_events_calendar', 'simple_events_calendar_archive_shortcode');
