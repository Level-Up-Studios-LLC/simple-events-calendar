<?php

/**
 * AJAX functionality class for Simple Events Calendar
 *
 * @package Simple_Events_Calendar
 * @since 3.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Simple Events AJAX class
 */
class Simple_Events_Ajax {

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
        add_action('wp_ajax_load_more_events', array($this, 'load_more_events'));
        add_action('wp_ajax_nopriv_load_more_events', array($this, 'load_more_events'));
    }

    /**
     * Handle AJAX request to load more events
     *
     * @return void
     */
    public function load_more_events() {
        if (!$this->verify_nonce()) {
            wp_send_json_error(
                array('message' => __('Security check failed.', PLUGIN_TEXT_DOMAIN)),
                403
            );
        }

        $request_data = $this->sanitize_request_data();
        if (!$request_data) {
            wp_send_json_error(
                array('message' => __('Invalid request data.', PLUGIN_TEXT_DOMAIN)),
                400
            );
        }

        $args  = $this->build_query_args($request_data);
        $query = new WP_Query($args);

        if (!$query->have_posts()) {
            wp_send_json_success(array(
                'html'     => '',
                'has_more' => false,
            ));
        }

        ob_start();
        $this->render_events($query, $request_data['display_options']);
        $html = ob_get_clean();
        wp_reset_postdata();

        $html = (string) $html;

        wp_send_json_success(array(
            'html'     => $html,
            'has_more' => trim($html) !== '',
        ));
    }

    /**
     * Verify AJAX nonce
     *
     * @return bool
     */
    private function verify_nonce() {
        if (!isset($_POST['nonce'])) {
            return false;
        }
        $nonce = sanitize_text_field(wp_unslash($_POST['nonce']));
        return (bool) wp_verify_nonce($nonce, SIMPLE_EVENTS_NONCE_ACTION);
    }

    /**
     * Sanitize and validate request data
     *
     * @return array|false Sanitized data or false on failure
     */
    private function sanitize_request_data() {
        $offset = isset($_POST['offset']) ? absint($_POST['offset']) : 0;

        if ($offset < 0 || $offset > 10000) {
            return false;
        }

        $display_options = array(
            'show_time'     => isset($_POST['show_time']) ? ($_POST['show_time'] === 'true') : true,
            'show_excerpt'  => isset($_POST['show_excerpt']) ? ($_POST['show_excerpt'] === 'true') : true,
            'show_location' => isset($_POST['show_location']) ? ($_POST['show_location'] === 'true') : true,
            'show_footer'   => isset($_POST['show_footer']) ? ($_POST['show_footer'] === 'true') : true
        );

        return array(
            'offset' => $offset,
            'display_options' => $display_options
        );
    }

    /**
     * Build query arguments for AJAX request
     *
     * @param array $request_data Sanitized request data
     * @return array Query arguments
     */
    private function build_query_args($request_data) {
        return array(
            'post_type'       => 'simple-events',
            'post_status'     => 'publish',
            'posts_per_page'  => 6,
            'offset'          => $request_data['offset'],
            'orderby'         => 'meta_value',
            'order'           => 'ASC',
            'meta_key'        => 'event_date',
            'meta_type'       => 'DATE',
            'meta_query'      => array(
                'relation' => 'AND',
                array(
                    'key'       => 'event_date',
                    'compare'   => '>=',
                    'value'     => current_time('Ymd'),
                    'type'      => 'DATE'
                )
            ),
            'no_found_rows'   => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'suppress_filters' => false,
        );
    }

    /**
     * Render events for AJAX response
     *
     * @param WP_Query $query The query object
     * @param array $display_options Display options
     */
    private function render_events($query, $display_options) {
        while ($query->have_posts()) {
            $query->the_post();

            $post_data = $this->prepare_post_data($display_options);

            if (empty($post_data['title']) || empty($post_data['date'])) {
                continue;
            }

            $this->render_event_card($post_data);
        }
    }

    /**
     * Prepare post data for rendering
     *
     * @param array $display_options Display options
     * @return array Post data
     */
    private function prepare_post_data($display_options) {
        return array(
            'title'      => get_the_title(),
            'permalink'  => get_permalink(),
            'thumbnail'  => get_the_post_thumbnail_url(get_the_ID(), 'medium_large'),
            'excerpt'    => wp_trim_words(get_the_excerpt(), 30, '...'),
            'date'       => get_field('event_date'),
            'start_time' => get_field('event_start_time'),
            'end_time'   => get_field('event_end_time'),
            'location'   => get_field('event_location'),
            'show_time'     => $display_options['show_time'],
            'show_excerpt'  => $display_options['show_excerpt'],
            'show_location' => $display_options['show_location'],
            'show_footer'   => $display_options['show_footer']
        );
    }

    /**
     * Render individual event card
     *
     * @param array $post_data Event data
     */
    private function render_event_card($post_data) {
        $template_path = PLUGIN_DIR . '/template-parts/content-event-card.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            $this->render_fallback_card($post_data);
        }
    }

    /**
     * Render fallback event card if template is missing
     *
     * @param array $post_data Event data
     */
    private function render_fallback_card($post_data) {
        simple_events_render_fallback_card($post_data);
    }

    /**
     * Handle AJAX errors gracefully
     *
     * @param string $message Error message
     * @param int $code Error code
     */
    private function handle_error($message, $code = 500) {
        if (defined('DOING_AJAX') && DOING_AJAX) {
            error_log('Simple Events Calendar AJAX Error: ' . $message);
            wp_die($message, 'Loading Error', array('response' => $code));
        }
    }

    /**
     * Get AJAX URL for frontend use
     *
     * @return string AJAX URL
     */
    public static function get_ajax_url() {
        return admin_url('admin-ajax.php');
    }

    /**
     * Get nonce for AJAX requests
     *
     * @return string Nonce
     */
    public static function get_nonce() {
        return wp_create_nonce(SIMPLE_EVENTS_NONCE_ACTION);
    }

    /**
     * Get AJAX parameters for frontend scripts
     *
     * @return array AJAX parameters
     */
    public static function get_ajax_params() {
        return array(
            'ajaxurl' => self::get_ajax_url(),
            'nonce'   => self::get_nonce(),
            'initial_offset' => 6,
            'load_increment' => 6,
            'loading_text' => __('Loading more events...', PLUGIN_TEXT_DOMAIN),
            'error_text'   => __('Error loading events. Please try again.', PLUGIN_TEXT_DOMAIN),
            'no_more_text' => __('No more events to load.', PLUGIN_TEXT_DOMAIN)
        );
    }
}