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
    <!-- Output an article for each event post -->
    <article class="simple-events-post events type-events status-publish has-post-thumbnail hentry entry has-media">
        <?php // Output the thumbnail image of the event post if it has one ?>
        <?php if ($post_data['thumbnail']) : ?>
            <a class="simple-events-post__thumbnail__link" href="<?php echo esc_url($post_data['permalink']); ?>">
                <div class="simple-events-post__thumbnail">
                    <!-- Output the thumbnail image of the event post -->
                    <img src="<?php echo esc_url($post_data['thumbnail']); ?>" 
                         alt="<?php echo esc_attr($post_data['title']); ?>" />
                </div>
            </a>
        <?php endif; ?>
        
        <div class="simple-events-post__text">
            <h3 class="simple-events-post__title">
                <!-- Output a link to the event post -->
                <a href="<?php echo esc_url($post_data['permalink']); ?>">
                    <?php echo esc_html($post_data['title']); ?>
                </a>
            </h3>
            
            <div class="simple-events-post__meta-data">
                <!-- Output the date of the event -->
                <span class="simple-events-post-date">
                    <?php echo esc_html($post_data['date']); ?>
                </span>
                <!-- Output the start and end time of the event if they are provided -->
                <span class="simple-events-post-time">
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
            
            <div class="simple-events-post__excerpt">
                <!-- Output the excerpt of the event -->
                <p><?php echo esc_html($post_data['excerpt']); ?></p>
            </div>
        </div>
    </article>
<?php endif; ?>
