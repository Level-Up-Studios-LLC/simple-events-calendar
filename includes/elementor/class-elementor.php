<?php

/**
 * Elementor integration loader for Simple Events Calendar
 *
 * Registers a "Simple Events" widget category, one drag-and-drop widget per
 * event element, and Dynamic Tags for binding native Elementor widgets to
 * event fields. Everything is gated on Elementor being active, so Elementor is
 * an optional enhancement, never a dependency.
 *
 * This file is safe to require unconditionally: it references no Elementor
 * classes at load time. The widget and tag class files (which extend Elementor
 * base classes) are required only from inside Elementor's own hooks, by which
 * point the base classes exist.
 *
 * @package Simple_Events_Calendar
 * @since 5.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Simple_Events_Elementor class
 */
class Simple_Events_Elementor {

    /**
     * Wire Elementor hooks if Elementor is present.
     */
    public static function init() {
        // 'elementor/loaded' fires when Elementor's core has loaded; if it has
        // not fired yet we still register the listeners below, which only run
        // when Elementor invokes them.
        add_action('elementor/elements/categories_registered', array(__CLASS__, 'register_category'));
        add_action('elementor/widgets/register', array(__CLASS__, 'register_widgets'));
        add_action('elementor/dynamic_tags/register', array(__CLASS__, 'register_tags'));
    }

    /**
     * Register the "Simple Events" widget category.
     *
     * @param \Elementor\Elements_Manager $elements_manager Elementor manager.
     */
    public static function register_category($elements_manager) {
        $elements_manager->add_category(
            'simple-events',
            array(
                'title' => __('Simple Events', 'simple_events'),
                'icon'  => 'eicon-calendar',
            )
        );
    }

    /**
     * Register the element widgets.
     *
     * @param \Elementor\Widgets_Manager $widgets_manager Elementor manager.
     */
    public static function register_widgets($widgets_manager) {
        require_once __DIR__ . '/widgets.php';

        $widgets_manager->register(new Simple_Events_Widget_Title());
        $widgets_manager->register(new Simple_Events_Widget_Image());
        $widgets_manager->register(new Simple_Events_Widget_Date());
        $widgets_manager->register(new Simple_Events_Widget_Time());
        $widgets_manager->register(new Simple_Events_Widget_Location());
        $widgets_manager->register(new Simple_Events_Widget_Excerpt());
        $widgets_manager->register(new Simple_Events_Widget_Content());
        $widgets_manager->register(new Simple_Events_Widget_Categories());
        $widgets_manager->register(new Simple_Events_Widget_Button());
    }

    /**
     * Register the Dynamic Tags.
     *
     * @param \Elementor\Core\DynamicTags\Manager $dynamic_tags Tags manager.
     */
    public static function register_tags($dynamic_tags) {
        require_once __DIR__ . '/dynamic-tags.php';

        // Ensure our group exists.
        $dynamic_tags->register_group(
            'simple-events',
            array('title' => __('Simple Events', 'simple_events'))
        );

        $dynamic_tags->register(new Simple_Events_Tag_Date());
        $dynamic_tags->register(new Simple_Events_Tag_Time());
        $dynamic_tags->register(new Simple_Events_Tag_Location());
        $dynamic_tags->register(new Simple_Events_Tag_Title());
        $dynamic_tags->register(new Simple_Events_Tag_Excerpt());
        $dynamic_tags->register(new Simple_Events_Tag_Categories());
        $dynamic_tags->register(new Simple_Events_Tag_Image_Url());
    }

    /**
     * Resolve the event ID to render for in a widget/tag.
     *
     * Falls back to the current loop/queried post, then to an explicit
     * preview-event selection (used inside the Elementor editor).
     *
     * @param int $preview_id Optional explicit preview ID.
     * @return int
     */
    public static function resolve_event_id($preview_id = 0) {
        $id = get_the_ID();
        if (!$id) {
            $id = get_queried_object_id();
        }
        if (!$id && $preview_id) {
            $id = (int) $preview_id;
        }
        return (int) $id;
    }

    /**
     * Options list of recent events for the editor preview pickers.
     *
     * @return array id => title
     */
    public static function event_options() {
        $options = array('' => __('— Current event —', 'simple_events'));
        $events = get_posts(array(
            'post_type'        => 'simple-events',
            'post_status'      => 'publish',
            'numberposts'      => 50,
            'orderby'          => 'title',
            'order'            => 'ASC',
            'suppress_filters' => false,
        ));
        foreach ($events as $event) {
            $options[$event->ID] = $event->post_title;
        }
        return $options;
    }
}
