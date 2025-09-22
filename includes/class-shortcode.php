<?php

/**
 * Shortcode functionality class for Simple Events Calendar
 *
 * @package Simple_Events_Calendar
 * @since 3.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Simple Events Shortcode class
 */
class Simple_Events_Shortcode {

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_shortcode('simple_events_calendar', array($this, 'render_shortcode'));
        add_action('save_post', array($this, 'clear_cache'));
        add_action('delete_post', array($this, 'clear_cache'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('transition_post_status', array($this, 'clear_cache_on_status_change'), 10, 3);
    }

    /**
     * Render the shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function render_shortcode($atts) {
        $atts = shortcode_atts(array(
            'posts_per_page' => 6,
            'category'       => '',
            'show_past'      => 'no',
            'order'          => 'ASC',
            'orderby'        => 'event_date',
            'show_time'      => 'yes',
            'show_excerpt'   => 'yes',
            'show_location'  => 'yes',
            'show_footer'    => 'yes'
        ), $atts, 'simple_events_calendar');

        $sanitized_atts = $this->sanitize_attributes($atts);

        $cache_key = 'simple_events_shortcode_' . md5(serialize($sanitized_atts));
        $cached_result = get_transient($cache_key);

        if (!is_admin() && $cached_result !== false) {
            return $cached_result;
        }

        $output = $this->generate_output($sanitized_atts);

        if (!is_admin() && !empty($output) && strpos($output, 'simple-events-no-events') === false) {
            set_transient($cache_key, $output, 15 * MINUTE_IN_SECONDS);
        }

        return $output;
    }

    /**
     * Sanitize shortcode attributes
     *
     * @param array $atts Raw attributes
     * @return array Sanitized attributes
     */
    private function sanitize_attributes($atts) {
        $posts_per_page = absint($atts['posts_per_page']);
        $posts_per_page = ($posts_per_page > 0 && $posts_per_page <= 50) ? $posts_per_page : 6;

        return array(
            'posts_per_page' => $posts_per_page,
            'category'       => sanitize_text_field($atts['category']),
            'show_past'      => ($atts['show_past'] === 'yes'),
            'order'          => in_array(strtoupper($atts['order']), ['ASC', 'DESC']) ? strtoupper($atts['order']) : 'ASC',
            'orderby'        => sanitize_text_field($atts['orderby']),
            'show_time'      => ($atts['show_time'] !== 'no'),
            'show_excerpt'   => ($atts['show_excerpt'] !== 'no'),
            'show_location'  => ($atts['show_location'] !== 'no'),
            'show_footer'    => ($atts['show_footer'] !== 'no')
        );
    }

    /**
     * Generate shortcode output
     *
     * @param array $atts Sanitized attributes
     * @return string HTML output
     */
    private function generate_output($atts) {
        $args = $this->build_query_args($atts);
        $the_query = new WP_Query($args);

        ob_start();

        if ($the_query->have_posts()) {
            $this->render_events_container($the_query, $atts);
        } else {
            $this->render_no_events_message($atts);
        }

        wp_reset_postdata();

        return ob_get_clean();
    }

    /**
     * Build WP_Query arguments
     *
     * @param array $atts Sanitized attributes
     * @return array Query arguments
     */
    private function build_query_args($atts) {
        $args = array(
            'post_type'         => 'simple-events',
            'post_status'       => 'publish',
            'posts_per_page'    => $atts['posts_per_page'],
            'orderby'           => 'meta_value',
            'order'             => $atts['order'],
            'meta_key'          => 'event_date',
            'meta_type'         => 'DATE',
            'no_found_rows'     => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'suppress_filters'  => false,
        );

        if (!$atts['show_past']) {
            $args['meta_query'] = array(
                'relation' => 'AND',
                array(
                    'key'       => 'event_date',
                    'compare'   => '>=',
                    'value'     => current_time('Ymd'),
                    'type'      => 'DATE'
                )
            );
        }

        if (!empty($atts['category'])) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'simple-events-cat',
                    'field'    => 'slug',
                    'terms'    => $atts['category'],
                ),
            );
        }

        return $args;
    }

    /**
     * Render events container
     *
     * @param WP_Query $query The query object
     * @param array $atts Sanitized attributes
     */
    private function render_events_container($query, $atts) {
        $data_attrs = sprintf(
            'data-show-time="%s" data-show-excerpt="%s" data-show-location="%s" data-show-footer="%s"',
            $atts['show_time'] ? 'true' : 'false',
            $atts['show_excerpt'] ? 'true' : 'false',
            $atts['show_location'] ? 'true' : 'false',
            $atts['show_footer'] ? 'true' : 'false'
        );

        echo '<div class="simple-events-calendar" data-shortcode="true" ' . $data_attrs . '>';

        while ($query->have_posts()) {
            $query->the_post();
            $this->render_event_card($atts);
        }

        echo '</div>';

        $this->render_load_more_hint($query, $atts);
    }

    /**
     * Render individual event card
     *
     * @param array $atts Display options
     */
    private function render_event_card($atts) {
        $post_data = array(
            'title'      => get_the_title(),
            'permalink'  => get_permalink(),
            'thumbnail'  => get_the_post_thumbnail_url(get_the_ID(), 'medium_large'),
            'excerpt'    => wp_trim_words(get_the_excerpt(), 30, '...'),
            'date'       => get_field('event_date'),
            'start_time' => get_field('event_start_time'),
            'end_time'   => get_field('event_end_time'),
            'location'   => get_field('event_location')
        );

        if (empty($post_data['title']) || empty($post_data['date'])) {
            return;
        }

        $template_path = PLUGIN_DIR . '/template-parts/content-event-card.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            $this->render_fallback_card($post_data, $atts);
        }
    }

    /**
     * Render fallback event card
     *
     * @param array $post_data Event data
     * @param array $atts Display options
     */
    private function render_fallback_card($post_data, $atts) {
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
                    <?php if ($atts['show_time'] && !empty($post_data['start_time'])): ?>
                        <span class="simple-events-calendar__post__time">
                            | <?php echo esc_html($post_data['start_time']); ?>
                            <?php if (!empty($post_data['end_time'])): ?>
                                - <?php echo esc_html($post_data['end_time']); ?>
                            <?php endif; ?>
                        </span>
                    <?php endif; ?>
                </div>
                <?php if ($atts['show_location'] && !empty($post_data['location'])): ?>
                    <div class="simple-events-calendar__post__location">
                        <span><?php echo esc_html($post_data['location']); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($atts['show_excerpt'] && !empty($post_data['excerpt'])): ?>
                    <div class="simple-events-calendar__post__excerpt">
                        <p><?php echo esc_html($post_data['excerpt']); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </article>
        <?php
    }

    /**
     * Render load more hint
     *
     * @param WP_Query $query The query object
     * @param array $atts Sanitized attributes
     */
    private function render_load_more_hint($query, $atts) {
        // Removed hint message - events load automatically on scroll
    }

    /**
     * Render no events message
     *
     * @param array $atts Sanitized attributes
     */
    private function render_no_events_message($atts) {
        echo '<div class="simple-events-calendar simple-events-no-events">';
        echo '<div class="simple-events-empty-state">';
        echo '<h3>No Events Found</h3>';

        if (!empty($atts['category'])) {
            echo '<p>No events found in the "' . esc_html($atts['category']) . '" category.</p>';
        } elseif (!$atts['show_past']) {
            echo '<p>No upcoming events scheduled. Check back soon!</p>';
        } else {
            echo '<p>No events have been created yet.</p>';
        }

        if (current_user_can('edit_posts')) {
            $admin_url = admin_url('post-new.php?post_type=simple-events');
            echo '<p><a href="' . esc_url($admin_url) . '" class="button">Add New Event</a></p>';
        }

        echo '</div>';
        echo '</div>';
    }

    /**
     * Clear shortcode cache
     *
     * @param int $post_id Post ID
     */
    public function clear_cache($post_id) {
        if (get_post_type($post_id) !== 'simple-events') {
            return;
        }

        global $wpdb;
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_simple_events_shortcode_%'));
    }

    /**
     * Clear cache on post status change
     *
     * @param string $new_status New post status
     * @param string $old_status Old post status
     * @param WP_Post $post Post object
     */
    public function clear_cache_on_status_change($new_status, $old_status, $post) {
        if ($post->post_type === 'simple-events' && $new_status !== $old_status) {
            $this->clear_cache($post->ID);
        }
    }

    /**
     * Enqueue shortcode-specific scripts
     */
    public function enqueue_scripts() {
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
}