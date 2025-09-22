<?php

/**
 * Main plugin class for Simple Events Calendar
 *
 * @package Simple_Events_Calendar
 * @since 3.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main Simple Events Calendar class
 */
class Simple_Events_Calendar {

    /**
     * Plugin version
     *
     * @var string
     */
    public $version = '3.0.0';

    /**
     * Plugin instance
     *
     * @var Simple_Events_Calendar
     */
    private static $instance = null;

    /**
     * Post type handler
     *
     * @var Simple_Events_Post_Type
     */
    public $post_type;

    /**
     * Shortcode handler
     *
     * @var Simple_Events_Shortcode
     */
    public $shortcode;

    /**
     * AJAX handler
     *
     * @var Simple_Events_Ajax
     */
    public $ajax;

    /**
     * Admin columns handler
     *
     * @var Simple_Events_Admin_Columns
     */
    public $admin_columns;

    /**
     * Get plugin instance
     *
     * @return Simple_Events_Calendar
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('plugins_loaded', array($this, 'init'), 20);
        add_action('acf/init', array($this, 'init'));
        add_action('admin_init', array($this, 'admin_init'));

        // Activation/Deactivation hooks
        register_activation_hook(SIMPLE_EVENTS_PLUGIN_FILE, array($this, 'activation_check'));
        register_deactivation_hook(SIMPLE_EVENTS_PLUGIN_FILE, array($this, 'deactivation'));
        register_uninstall_hook(SIMPLE_EVENTS_PLUGIN_FILE, array('Simple_Events_Calendar', 'uninstall'));
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Runtime dependency check
        if (!$this->runtime_dependency_check()) {
            return;
        }

        // Load components
        $this->load_components();

        // Ensure field groups are registered after a short delay
        add_action('wp_loaded', array($this, 'ensure_field_groups'));

        // Initialize query modifications
        add_action('pre_get_posts', array($this, 'modify_archive_query'));
        add_action('init', array($this, 'ensure_public_access'));

        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

        // Add action links
        add_filter('plugin_action_links_' . plugin_basename(SIMPLE_EVENTS_PLUGIN_FILE), array($this, 'action_links'));
    }

    /**
     * Admin initialization
     */
    public function admin_init() {
        if (is_admin() && current_user_can('activate_plugins')) {
            $this->runtime_dependency_check();
        }
    }

    /**
     * Load plugin components
     */
    private function load_components() {
        // Load utility functions first
        require_once PLUGIN_DIR . '/includes/functions.php';

        // Load class files
        require_once PLUGIN_DIR . '/includes/class-post-type.php';
        require_once PLUGIN_DIR . '/includes/class-shortcode.php';
        require_once PLUGIN_DIR . '/includes/class-ajax.php';
        require_once PLUGIN_DIR . '/includes/class-admin-columns.php';

        // Initialize component classes
        $this->post_type = new Simple_Events_Post_Type();
        $this->shortcode = new Simple_Events_Shortcode();
        $this->ajax = new Simple_Events_Ajax();
        $this->admin_columns = new Simple_Events_Admin_Columns();

        // Load existing files for now (will be phased out)
        $legacy_components = array(
            'includes/acf-json.php',
            'includes/acf-settings-page.php',
        );

        foreach ($legacy_components as $component) {
            $file_path = PLUGIN_DIR . '/' . $component;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
    }

    /**
     * Check ACF dependency
     *
     * @return bool
     */
    public function check_acf_dependency() {
        return function_exists('acf_get_setting') ||
               function_exists('acf_add_local_field_group') ||
               defined('ACF_VERSION') ||
               class_exists('ACF');
    }

    /**
     * Runtime dependency check
     *
     * @return bool
     */
    private function runtime_dependency_check() {
        if (!$this->check_acf_dependency()) {
            deactivate_plugins(plugin_basename(SIMPLE_EVENTS_PLUGIN_FILE));
            add_action('admin_notices', array($this, 'acf_dependency_notice'));
            return false;
        }
        return true;
    }

    /**
     * Plugin activation check
     */
    public function activation_check() {
        if (function_exists('wp_clean_plugins_cache')) {
            wp_clean_plugins_cache();
        }

        if (!$this->check_acf_dependency()) {
            deactivate_plugins(plugin_basename(SIMPLE_EVENTS_PLUGIN_FILE));
            $this->activation_error();
        }

        $this->create_acf_json_directory();
        $this->init();
        flush_rewrite_rules();
        wp_cache_flush();
    }

    /**
     * Plugin deactivation
     */
    public function deactivation() {
        flush_rewrite_rules();
    }

    /**
     * Plugin uninstall
     */
    public static function uninstall() {
        // Delete all simple-events posts
        $events = get_posts(array(
            'post_type' => 'simple-events',
            'numberposts' => -1,
            'post_status' => 'any'
        ));

        foreach ($events as $event) {
            wp_delete_post($event->ID, true);
        }

        // Delete taxonomies
        $terms = get_terms(array(
            'taxonomy' => 'simple-events-cat',
            'hide_empty' => false
        ));

        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                wp_delete_term($term->term_id, 'simple-events-cat');
            }
        }

        // Clean up transients and options
        global $wpdb;
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_simple_events_%'));
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_timeout_simple_events_%'));

        flush_rewrite_rules();
    }

    /**
     * Create ACF JSON directory
     */
    private function create_acf_json_directory() {
        $json_dir = PLUGIN_DIR . '/includes/acf-json';

        if (!file_exists($json_dir)) {
            wp_mkdir_p($json_dir);

            $index_file = $json_dir . '/index.php';
            if (!file_exists($index_file)) {
                file_put_contents($index_file, '<?php // Silence is golden.');
            }
        }
    }

    /**
     * Ensure field groups are properly registered
     */
    public function ensure_field_groups() {
        if (function_exists('acf_add_local_field_group') && function_exists('register_event_details_fields')) {
            register_event_details_fields();
        }
    }

    /**
     * Ensure ACF fields are accessible to non-logged-in users
     */
    public function ensure_public_access() {
        if (function_exists('acf_add_options_page')) {
            add_filter('acf/settings/show_admin', '__return_true');
        }

        global $wp_post_types;
        if (isset($wp_post_types['simple-events'])) {
            $wp_post_types['simple-events']->public = true;
            $wp_post_types['simple-events']->publicly_queryable = true;
            $wp_post_types['simple-events']->show_in_nav_menus = true;
        }
    }

    /**
     * Modify archive query
     *
     * @param WP_Query $query
     */
    public function modify_archive_query($query) {
        if (is_admin() || !$query->is_main_query()) {
            return;
        }

        if (!is_post_type_archive('simple-events') && !is_tax('simple-events-cat')) {
            return;
        }

        $today = current_time('Ymd');

        $query->set('orderby', 'meta_value');
        $query->set('order', 'ASC');
        $query->set('meta_key', 'event_date');
        $query->set('meta_type', 'DATE');
        $query->set('suppress_filters', false);

        $meta_query = array(
            array(
                'key'       => 'event_date',
                'compare'   => '>=',
                'value'     => $today,
                'type'      => 'DATE'
            )
        );

        $existing_meta_query = $query->get('meta_query');
        if (!empty($existing_meta_query)) {
            $meta_query['relation'] = 'AND';
            $meta_query = array_merge(array('relation' => 'AND'), $existing_meta_query, $meta_query);
        }

        $query->set('meta_query', $meta_query);
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        if (!$this->check_acf_dependency()) {
            return;
        }

        global $post;
        $should_load = false;

        if (is_post_type_archive('simple-events') ||
            is_singular('simple-events') ||
            is_tax('simple-events-cat')) {
            $should_load = true;
        }

        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'simple_events_calendar')) {
            $should_load = true;
        }

        if (!$should_load && is_active_widget(false, false, 'text')) {
            $should_load = true;
        }

        if (!$should_load) {
            return;
        }

        wp_enqueue_style(
            'simple-events-style',
            PLUGIN_ASSETS . '/css/simple-events.css',
            array(),
            $this->version
        );

        wp_enqueue_script(
            'simple-events-script',
            PLUGIN_ASSETS . '/js/simple-events.js',
            array('jquery'),
            $this->version,
            true
        );

        wp_localize_script(
            'simple-events-script',
            'ajax_params',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('load_more_events_nonce'),
                'initial_offset' => 6,
                'load_increment' => 6
            )
        );
    }

    /**
     * Add action links to plugin page
     *
     * @param array $links
     * @return array
     */
    public function action_links($links) {
        if (!$this->check_acf_dependency()) {
            return $links;
        }

        $plugin_links = array(
            '<a href="' . admin_url('edit.php?post_type=simple-events') . '">Events</a>',
            '<a href="' . admin_url('edit.php?post_type=acf-field-group') . '">Field Groups</a>',
        );

        return array_merge($plugin_links, $links);
    }

    /**
     * Display ACF dependency notice
     */
    public function acf_dependency_notice() {
        if (!current_user_can('activate_plugins')) {
            return;
        }

        $status = simple_events_get_acf_status();
        $install_url = admin_url('plugin-install.php?s=advanced+custom+fields&tab=search&type=term');
        $plugins_url = admin_url('plugins.php');

        ?>
        <div class="notice notice-error">
            <h3>Simple Events Calendar - Dependency Error</h3>

            <?php if (!$status['pro_installed'] && !$status['free_installed']): ?>
                <p><strong>Advanced Custom Fields is not installed.</strong></p>
                <p>Simple Events Calendar requires ACF to manage event data. Please install either the free or pro version.</p>
                <p>
                    <a href="<?php echo esc_url($install_url); ?>" class="button button-primary">Install ACF Free</a>
                    <a href="https://www.advancedcustomfields.com/pro/" class="button" target="_blank">Get ACF Pro</a>
                </p>

            <?php elseif (($status['pro_installed'] && !$status['pro_active']) || ($status['free_installed'] && !$status['free_active'])): ?>
                <p><strong>Advanced Custom Fields is installed but not activated.</strong></p>
                <p>Please activate ACF to use Simple Events Calendar.</p>
                <p>
                    <a href="<?php echo esc_url($plugins_url); ?>" class="button button-primary">Go to Plugins</a>
                </p>

            <?php else: ?>
                <p><strong>Advanced Custom Fields appears to be installed but is not loading properly.</strong></p>
                <p>This could be due to a plugin conflict or loading order issue.</p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Activation error message
     */
    private function activation_error() {
        $status = simple_events_get_acf_status();

        $error_message = '<h1>Plugin Activation Error</h1>';
        $error_message .= '<p><strong>Simple Events Calendar</strong> requires Advanced Custom Fields to function.</p>';

        if (!$status['pro_installed'] && !$status['free_installed']) {
            $error_message .= '<p>ACF is not installed. Please install ACF first:</p>';
            $error_message .= '<ul>';
            $error_message .= '<li><a href="' . admin_url('plugin-install.php?s=advanced+custom+fields&tab=search&type=term') . '">Install ACF Free</a></li>';
            $error_message .= '<li><a href="https://www.advancedcustomfields.com/pro/" target="_blank">Get ACF Pro</a></li>';
            $error_message .= '</ul>';
        } elseif (($status['pro_installed'] && !$status['pro_active']) || ($status['free_installed'] && !$status['free_active'])) {
            $error_message .= '<p>ACF is installed but not activated. Please <a href="' . admin_url('plugins.php') . '">activate ACF</a> first.</p>';
        } else {
            $error_message .= '<p>ACF appears to be installed but is not loading properly. Please check for plugin conflicts.</p>';
        }

        $error_message .= '<p><a href="' . admin_url('plugins.php') . '">Return to Plugins</a></p>';

        wp_die(
            $error_message,
            'Plugin Dependency Error',
            array('back_link' => true)
        );
    }
}