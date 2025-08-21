<?php

/**
 * Event Card Template for Simple Events Calendar
 *
 * Outputs an event card for a single event post with improved accessibility
 * and error handling.
 *
 * @param array $post_data An associative array containing the event post data.
 *                        Required keys:
 *                        - title: The title of the event
 *                        - permalink: The permalink of the event
 *                        - date: The date of the event
 *                        Optional keys:
 *                        - thumbnail: The URL of the thumbnail image
 *                        - start_time: The start time of the event
 *                        - end_time: The end time of the event
 *                        - excerpt: The excerpt of the event
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Validate that we have the required post data
if (!isset($post_data) || !is_array($post_data)) {
    return;
}

// Validate required fields
$required_fields = ['title', 'permalink', 'date'];
foreach ($required_fields as $field) {
    if (empty($post_data[$field])) {
        return; // Skip rendering if required data is missing
    }
}

// Sanitize and prepare data
$title = esc_html($post_data['title']);
$permalink = esc_url($post_data['permalink']);
$thumbnail = !empty($post_data['thumbnail']) ? esc_url($post_data['thumbnail']) : '';
$date = esc_html($post_data['date']);
$start_time = !empty($post_data['start_time']) ? esc_html($post_data['start_time']) : '';
$end_time = !empty($post_data['end_time']) ? esc_html($post_data['end_time']) : '';
$excerpt = !empty($post_data['excerpt']) ? esc_html($post_data['excerpt']) : '';

// Generate CSS classes
$css_classes = ['simple-events-calendar__post'];
$css_classes[] = 'post-id-' . get_the_ID();
if ($thumbnail) {
    $css_classes[] = 'has-thumbnail';
}

// Generate time display
$time_display = '';
if ($start_time) {
    $time_display = ' | ' . $start_time;
    if ($end_time) {
        $time_display .= ' - ' . $end_time;
    }
}

// Generate structured data for better SEO
$event_schema = array(
    '@context' => 'https://schema.org',
    '@type' => 'Event',
    'name' => $title,
    'url' => $permalink,
    'startDate' => date('c', strtotime($post_data['date'] . ' ' . $post_data['start_time'])),
);

if ($end_time) {
    $event_schema['endDate'] = date('c', strtotime($post_data['date'] . ' ' . $post_data['end_time']));
}

if ($excerpt) {
    $event_schema['description'] = $excerpt;
}
?>

<article class="<?php echo implode(' ', $css_classes); ?>" itemscope itemtype="https://schema.org/Event">

    <!-- Structured Data -->
    <script type="application/ld+json">
        <?php echo wp_json_encode($event_schema); ?>
    </script>

    <?php if ($thumbnail) : ?>
        <div class="simple-events-calendar__post__thumbnail">
            <a href="<?php echo $permalink; ?>"
                class="simple-events-calendar__post__link"
                aria-label="<?php printf(__('View event: %s', PLUGIN_TEXT_DOMAIN), $title); ?>">
                <img src="<?php echo $thumbnail; ?>"
                    alt="<?php echo esc_attr(sprintf(__('Image for event: %s'), $title)); ?>"
                    itemprop="image"
                    loading="lazy"
                    decoding="async" />
            </a>
        </div>
    <?php endif; ?>

    <div class="simple-events-calendar__post__description">

        <header class="simple-events-calendar__post__header">
            <h3 class="simple-events-calendar__post__title" itemprop="name">
                <a href="<?php echo $permalink; ?>"
                    itemprop="url"
                    rel="bookmark">
                    <?php echo $title; ?>
                </a>
            </h3>
        </header>

        <div class="simple-events-calendar__post__meta">
            <time class="simple-events-calendar__post__date"
                datetime="<?php echo date('c', strtotime($post_data['date'] . ' ' . $post_data['start_time'])); ?>"
                itemprop="startDate">
                <span class="simple-events-calendar__date-text" aria-label="<?php printf(__('Event date: %s', PLUGIN_TEXT_DOMAIN), $date); ?>">
                    <?php echo $date; ?>
                </span>
            </time>

            <?php if ($time_display) : ?>
                <span class="simple-events-calendar__post__time" aria-label="<?php printf(__('Event time: %s', PLUGIN_TEXT_DOMAIN), trim($time_display, ' |')); ?>">
                    <?php echo $time_display; ?>
                </span>
            <?php endif; ?>
        </div>

        <?php if ($excerpt) : ?>
            <div class="simple-events-calendar__post__excerpt" itemprop="description">
                <p><?php echo $excerpt; ?></p>
            </div>
        <?php endif; ?>

        <!-- Event location if available -->
        <?php
        $location = get_field('event_location');
        if ($location) :
        ?>
            <div class="simple-events-calendar__post__location" itemprop="location" itemscope itemtype="https://schema.org/Place">
                <span class="simple-events-calendar__location-label" aria-label="<?php _e('Event location:', PLUGIN_TEXT_DOMAIN); ?>">
                    <svg class="simple-events-calendar__location-icon" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" />
                    </svg>
                </span>
                <span itemprop="name"><?php echo esc_html($location); ?></span>
            </div>
        <?php endif; ?>

    </div>

    <!-- Call to action footer -->
    <footer class="simple-events-calendar__post__footer">
        <a href="<?php echo $permalink; ?>"
            class="simple-events-calendar__read-more"
            aria-label="<?php printf(__('Read more about %s', PLUGIN_TEXT_DOMAIN), $title); ?>">
            <?php _e('Learn More', PLUGIN_TEXT_DOMAIN); ?>
            <svg class="simple-events-calendar__arrow-icon" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z" />
            </svg>
        </a>
    </footer>

</article>