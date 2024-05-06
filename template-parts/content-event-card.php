<?php

/**
 * Outputs an event card for a single event post.
 *
 * @param array $post_data An associative array containing the event post data.
 *                        Must have the following keys:
 *                        - title: The title of the event.
 *                        - permalink: The permalink of the event.
 *                        - thumbnail: The URL of the thumbnail image of the event.
 *                        - date: The date of the event.
 *                        - start_time: The start time of the event (optional).
 *                        - end_time: The end time of the event (optional).
 *                        - excerpt: The excerpt of the event.
 */
if (isset($post_data)) : ?>
    <article class="simple-events-calendar__post post-id-<?php echo get_the_ID(); ?> <?php echo !empty($post_data['thumbnail']) ? 'has-thumbnail' : ''; ?>">
        <?php if ($post_data['thumbnail']) : ?>
            <a href="<?php echo esc_url($post_data['permalink']); ?>" class="simple-events-calendar__post__link">
                <div class="simple-events-calendar__post__thumbnail">
                    <img src="<?php echo esc_url($post_data['thumbnail']); ?>" alt="<?php echo esc_attr($post_data['title']); ?>" />
                </div>
            </a>
        <?php endif; ?>

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
                <span class="simple-events-calendar__post__time">
                    <?php
                    if ($post_data['start_time']) {
                        echo ' | ' . esc_html($post_data['start_time']);
                    }
                    if ($post_data['end_time']) {
                        echo ' - ' . esc_html($post_data['end_time']);
                    }
                    ?>
                </span>
            </div>

            <div class="simple-events-calendar__post__excerpt">
                <p><?php echo esc_html($post_data['excerpt']); ?></p>
            </div>
        </div>
    </article>
<?php endif; ?>