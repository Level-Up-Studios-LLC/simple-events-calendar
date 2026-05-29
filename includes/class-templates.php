<?php

/**
 * Front-end template loader for Simple Events Calendar
 *
 * Provides default single / archive / taxonomy templates for events, but only
 * as a fallback. It yields to:
 *   - theme templates (single-simple-events.php, archive-simple-events.php,
 *     taxonomy-simple-events-cat.php) found via locate_template();
 *   - block (FSE) themes, which manage their own templates;
 *   - Elementor Pro Theme Builder, by running before Elementor's own
 *     template_include filter so Elementor wins whenever it has a matching
 *     template (and passes ours through when it doesn't).
 *
 * A site can disable the defaults entirely via the
 * `simple_events_use_default_template` filter.
 *
 * @package Simple_Events_Calendar
 * @since 5.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Simple_Events_Templates class
 */
class Simple_Events_Templates {

    /**
     * Constructor.
     */
    public function __construct() {
        // Low priority so page builders (Elementor) and other late filters can
        // override our value; we only set a fallback for the resolved template.
        add_filter('template_include', array($this, 'template_include'), 5);
    }

    /**
     * Substitute a plugin template only when nothing else handles the view.
     *
     * @param string $template Resolved template path.
     * @return string
     */
    public function template_include($template) {
        // Determine which event view (if any) this is.
        if (is_singular('simple-events')) {
            $file       = 'single-simple-events.php';
            $theme_cand = array('single-simple-events.php');
        } elseif (is_post_type_archive('simple-events')) {
            $file       = 'archive-simple-events.php';
            $theme_cand = array('archive-simple-events.php');
        } elseif (is_tax('simple-events-cat')) {
            $file       = 'taxonomy-simple-events-cat.php';
            $theme_cand = array('taxonomy-simple-events-cat.php', 'archive-simple-events.php');
        } else {
            return $template;
        }

        /**
         * Allow disabling the plugin's default templates entirely.
         *
         * @param bool   $use      Whether to use the plugin default template.
         * @param string $file     Plugin template filename.
         * @param string $template Currently resolved template.
         */
        if (!apply_filters('simple_events_use_default_template', true, $file, $template)) {
            return $template;
        }

        // Defer to a block (FSE) theme — it manages templates via the editor.
        if (function_exists('wp_is_block_theme') && wp_is_block_theme()) {
            return $template;
        }

        // Defer to a theme-provided template. locate_template() returns the
        // path to the first matching candidate; return that rather than the
        // already-resolved $template, so a theme's archive-simple-events.php
        // overrides the taxonomy view (which WP's hierarchy resolves to a
        // generic archive.php/taxonomy.php, not our candidate).
        $located = locate_template($theme_cand);
        if ($located) {
            return $located;
        }

        $plugin_template = PLUGIN_DIR . '/templates/' . $file;
        return file_exists($plugin_template) ? $plugin_template : $template;
    }
}
