<?php

/**
 * AJAX functionality for Simple Events Calendar
 *
 * Handles AJAX requests for loading more events with proper security
 * and error handling.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles AJAX request to load more events
 *
 * This function is called when a user scrolls to the bottom of the page 
 * to load more events using AJAX. It retrieves more events based on the 
 * offset and renders them using the template-parts/content-event-card.php template.
 *
 * @return void
 */
function ajax_load_more_events()
{
    // Verify nonce for security
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'load_more_events_nonce')) {
        wp_send_json_error('Security check failed', 403);
        return;
    }

    // Sanitize and validate input
    $offset = isset($_POST['offset']) ? absint($_POST['offset']) : 0;

    // Validate offset is reasonable (prevent potential abuse)
    if ($offset < 0 || $offset > 10000) {
        wp_send_json_error('Invalid offset value', 400);
        return;
    }

    // Build query arguments - only show current and upcoming events
    $today = current_time('Ymd'); // Use WordPress timezone
    $args = array(
        'post_type'       => 'simple-events',
        'post_status'     => 'publish',
        'posts_per_page'  => 6,
        'offset'          => $offset,
        'orderby'         => 'meta_value',
        'order'           => 'ASC',
        'meta_key'        => 'event_date',
        'meta_query'      => array(
            array(
                'key'       => 'event_date',
                'compare'   => '>=',
                'value'     => $today,
                'type'      => 'DATE'
            )
        ),
        // Ensure we don't cache this query
        'no_found_rows'   => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    );

    // Execute query
    $the_query = new WP_Query($args);

    // Start output buffering
    ob_start();

    // Check if there are any posts
    if ($the_query->have_posts()) {
        // Loop through the posts and render the template for each one
        while ($the_query->have_posts()) {
            $the_query->the_post();

            // Prepare post data with proper sanitization
            $post_data = array(
                'title'      => get_the_title(),
                'permalink'  => get_permalink(),
                'thumbnail'  => get_the_post_thumbnail_url(get_the_ID(), 'medium_large'),
                'excerpt'    => wp_trim_words(get_the_excerpt(), 30, '...'),
                'date'       => get_field('event_date'),
                'start_time' => get_field('event_start_time'),
                'end_time'   => get_field('event_end_time')
            );

            // Validate that we have required data
            if (empty($post_data['title']) || empty($post_data['date'])) {
                continue; // Skip invalid events
            }

            // Include the template
            $template_path = PLUGIN_DIR . '/template-parts/content-event-card.php';
            if (file_exists($template_path)) {
                include $template_path;
            } else {
                // Fallback if template is missing
                simple_events_render_fallback_card($post_data);
            }
        }
    } else {
        // Return a special response indicating no more events (not an error)
        wp_reset_postdata();
        echo 'NO_MORE_EVENTS'; // Special response code for JavaScript to handle
        wp_die();
    }

    // Reset the post data
    wp_reset_postdata();

    // Get the output and clean buffer
    $output = ob_get_clean();

    // Check if we have actual content (not just the no-more message)
    if (empty($output) || trim($output) === '') {
        echo 'NO_MORE_EVENTS';
    } else {
        echo $output;
    }

    // Terminate the AJAX request properly
    wp_die();
}

/**
 * Fallback card renderer if template file is missing
 *
 * @param array $post_data Event data array
 * @return void
 */
function simple_events_render_fallback_card($post_data)
{
?>
    <article class="simple-events-calendar__post simple-events-fallback">
        <div class="simple-events-calendar__post__description">
            <h3 class="simple-events-calendar__post__title">
                <a href="<?php echo esc_url($post_data['permalink']); ?>">
                    <?php echo esc_html($post_data['title']); ?>
                </a>
            </h3>
            <div class="simple-events-calendar__post__meta">
                <span class="simple-events-calendar__post__date">
                    <?php echo esc_html($post_data['date']); ?>
                </span>
                <?php if (!empty($post_data['start_time'])): ?>
                    <span class="simple-events-calendar__post__time">
                        | <?php echo esc_html($post_data['start_time']); ?>
                        <?php if (!empty($post_data['end_time'])): ?>
                            - <?php echo esc_html($post_data['end_time']); ?>
                        <?php endif; ?>
                    </span>
                <?php endif; ?>
            </div>
            <?php if (!empty($post_data['excerpt'])): ?>
                <div class="simple-events-calendar__post__excerpt">
                    <p><?php echo esc_html($post_data['excerpt']); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </article>
<?php
}

/**
 * Handle AJAX errors gracefully
 */
function simple_events_ajax_error_handler()
{
    if (defined('DOING_AJAX') && DOING_AJAX) {
        // Log the error for debugging
        error_log('Simple Events Calendar AJAX Error: ' . print_r($_POST, true));

        // Return user-friendly error
        wp_die('An error occurred while loading events. Please refresh the page and try again.', 'Loading Error', array('response' => 500));
    }
}

// Register AJAX handlers for logged-in and non-logged-in users
add_action('wp_ajax_load_more_events', 'ajax_load_more_events');
add_action('wp_ajax_nopriv_load_more_events', 'ajax_load_more_events');
