<?php

/**
 * Documentation page for Simple Events Calendar.
 *
 * Adds a read-only "Documentation" screen under the Events menu that lists the
 * available shortcodes (and their attributes) and the Elementor widgets (and
 * where each can be used). Static reference content only — no options.
 *
 * @package Simple_Events_Calendar
 * @since 5.1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Simple_Events_Docs class
 */
class Simple_Events_Docs {

    /**
     * Documentation page slug.
     */
    const PAGE = 'simple-events-docs';

    /**
     * Constructor.
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu'));
    }

    /**
     * Register the Documentation submenu page under the Events menu.
     */
    public function add_menu() {
        add_submenu_page(
            'edit.php?post_type=simple-events',
            __('Simple Events Documentation', 'simple_events'),
            __('Documentation', 'simple_events'),
            'edit_posts',
            self::PAGE,
            array($this, 'render_page')
        );
    }

    /**
     * Render a single shortcode reference block.
     *
     * @param string $usage       Example usage (rendered inside <code>, escaped).
     * @param string $description  What the shortcode does.
     * @param array  $atts         Map of attribute name => description.
     * @return void
     */
    private function render_shortcode_block($usage, $description, $atts = array()) {
        ?>
        <div class="sec-docs__item">
            <p class="sec-docs__code"><code><?php echo esc_html($usage); ?></code></p>
            <p class="sec-docs__desc"><?php echo esc_html($description); ?></p>
            <?php if (!empty($atts)) : ?>
                <table class="widefat striped sec-docs__atts">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Attribute', 'simple_events'); ?></th>
                            <th><?php echo esc_html__('Description', 'simple_events'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($atts as $name => $desc) : ?>
                            <tr>
                                <td><code><?php echo esc_html($name); ?></code></td>
                                <td><?php echo esc_html($desc); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render the Documentation page.
     */
    public function render_page() {
        if (!current_user_can('edit_posts')) {
            return;
        }
        ?>
        <div class="wrap sec-docs">
            <h1><?php echo esc_html__('Simple Events Documentation', 'simple_events'); ?></h1>
            <p class="sec-docs__intro"><?php echo esc_html__('Reference for the shortcodes and Elementor widgets this plugin provides. Use shortcodes in the block/classic editor or widgets; use the Elementor widgets when building with Elementor.', 'simple_events'); ?></p>

            <h2><?php echo esc_html__('Shortcodes', 'simple_events'); ?></h2>

            <h3><?php echo esc_html__('Events grid — [sec_events]', 'simple_events'); ?></h3>
            <?php
            $this->render_shortcode_block(
                '[sec_events posts_per_page="6" category="" order="ASC"]',
                __('Displays a responsive grid of events. Past events are hidden by default and more events load as the visitor scrolls. All attributes are optional and default to your settings on Events → Settings.', 'simple_events'),
                array(
                    'posts_per_page' => __('How many events to show initially (1–50).', 'simple_events'),
                    'category'       => __('Limit to one category by its slug. Empty shows all.', 'simple_events'),
                    'show_past'      => __('"yes" to include past events; "no" (default) shows upcoming only.', 'simple_events'),
                    'order'          => __('"ASC" (soonest first) or "DESC" (latest first).', 'simple_events'),
                    'show_time'      => __('"yes"/"no" — show the event time on each card.', 'simple_events'),
                    'show_excerpt'   => __('"yes"/"no" — show the excerpt on each card.', 'simple_events'),
                    'show_location'  => __('"yes"/"no" — show the location on each card.', 'simple_events'),
                    'show_footer'    => __('"yes"/"no" — show the "Learn More" footer link.', 'simple_events'),
                )
            );
            ?>

            <h3><?php echo esc_html__('Single event — [sec_event]', 'simple_events'); ?></h3>
            <?php
            $this->render_shortcode_block(
                '[sec_event id="123" layout="card"]',
                __('Displays one specific event by its ID, as a card or an image-left list row. The "id" attribute is required — find the ID in the URL when editing the event (post=123).', 'simple_events'),
                array(
                    'id'            => __('Required. The event\'s post ID.', 'simple_events'),
                    'layout'        => __('"card" (default) or "list" (image left, details right).', 'simple_events'),
                    'show_time'     => __('"yes"/"no" — show the event time.', 'simple_events'),
                    'show_excerpt'  => __('"yes"/"no" — show the excerpt.', 'simple_events'),
                    'show_location' => __('"yes"/"no" — show the location.', 'simple_events'),
                    'show_footer'   => __('"yes"/"no" — show the "Learn More" footer link.', 'simple_events'),
                )
            );
            ?>

            <h3><?php echo esc_html__('Single element shortcodes', 'simple_events'); ?></h3>
            <p><?php echo esc_html__('Render one piece of an event — handy for custom layouts. Inside an event (single page or loop) they use the current event automatically; elsewhere, pass an "id" attribute to target a specific event. All of them also accept "class", "before", and "after".', 'simple_events'); ?></p>
            <?php
            $this->render_shortcode_block(
                '[sec_event_title id="123" tag="h2" link="yes"]',
                __('Available element shortcodes and their main attributes:', 'simple_events'),
                array(
                    '[sec_event_title]'      => __('Event title. Attributes: tag (h1–h6/span), link (yes/no).', 'simple_events'),
                    '[sec_event_image]'      => __('Featured image. Attributes: size, link (yes/no).', 'simple_events'),
                    '[sec_event_date]'       => __('Event date. Attribute: format (PHP date format override).', 'simple_events'),
                    '[sec_event_time]'       => __('Start (and end) time. Attribute: separator.', 'simple_events'),
                    '[sec_event_location]'   => __('Location. Attribute: icon (yes/no).', 'simple_events'),
                    '[sec_event_excerpt]'    => __('Excerpt. Attribute: words (word limit).', 'simple_events'),
                    '[sec_event_content]'    => __('Full event content.', 'simple_events'),
                    '[sec_event_categories]' => __('Category links. Attributes: link (yes/no), separator.', 'simple_events'),
                    '[sec_event_button]'     => __('A link/button to the event. Attribute: text.', 'simple_events'),
                )
            );
            ?>

            <h2><?php echo esc_html__('Elementor widgets', 'simple_events'); ?></h2>
            <p><?php echo esc_html__('When Elementor is active, the widgets below appear in the editor under the "Simple Events" category (just below "Basic").', 'simple_events'); ?></p>

            <h3><?php echo esc_html__('Display widgets — use anywhere', 'simple_events'); ?></h3>
            <p><?php echo esc_html__('Drag these onto any page, post, or template. They include their own event query, so they do not need an event context.', 'simple_events'); ?></p>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Widget', 'simple_events'); ?></th>
                        <th><?php echo esc_html__('Purpose', 'simple_events'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong><?php echo esc_html__('Events Grid', 'simple_events'); ?></strong></td>
                        <td><?php echo esc_html__('A grid (with a configurable column count) or an image-left list of events, with controls for category, order, count, the show/hide toggles, and an optional "Load more on scroll" (infinite scroll).', 'simple_events'); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php echo esc_html__('Single Event', 'simple_events'); ?></strong></td>
                        <td><?php echo esc_html__('Displays one event you pick from a searchable list, as a card or a list row.', 'simple_events'); ?></td>
                    </tr>
                </tbody>
            </table>

            <h3><?php echo esc_html__('Event element widgets — use inside an event context', 'simple_events'); ?></h3>
            <p><?php echo esc_html__('These render a single piece of "the current event": Event Title, Event Image, Event Date, Event Time, Event Location, Event Excerpt, Event Content, Event Categories, and Event Button.', 'simple_events'); ?></p>
            <p><?php echo esc_html__('Because they depend on a current event, they only output on the front end when used in an event context, namely:', 'simple_events'); ?></p>
            <ul class="sec-docs__list">
                <li><?php echo esc_html__('A single-event template (Elementor Theme Builder).', 'simple_events'); ?></li>
                <li><?php echo esc_html__('An event archive / category template.', 'simple_events'); ?></li>
                <li><?php echo esc_html__('Inside a Loop Grid whose query is set to events.', 'simple_events'); ?></li>
            </ul>
            <p><?php echo esc_html__('If you place one on an ordinary page, the Elementor editor previews it with a sample event so you can style it, but it renders nothing on the live page. To show a specific event on an ordinary page, use the Single Event widget or the [sec_event] shortcode instead.', 'simple_events'); ?></p>

            <h3><?php echo esc_html__('Dynamic Tags', 'simple_events'); ?></h3>
            <p><?php echo esc_html__('In an event template or Loop Grid, you can also bind a native Elementor widget (Heading, Text, Image, etc.) to an event field via its dynamic-tags picker, under the "Simple Events" group: Event Date, Event Time, Event Location, Event Title, Event Excerpt, Event Categories, and Event Image URL.', 'simple_events'); ?></p>

            <h3><?php echo esc_html__('Loop Grid — sort by event date', 'simple_events'); ?></h3>
            <p><?php echo esc_html__('Elementor\'s Loop Grid orders by post date by default, not by the event date. To sort a Loop Grid of events by their event date, set the widget\'s Query ID (Loop Grid → Query → Query ID) to one of these built-in IDs:', 'simple_events'); ?></p>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Query ID', 'simple_events'); ?></th>
                        <th><?php echo esc_html__('Result', 'simple_events'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>sec_events_by_date</code></td>
                        <td><?php echo esc_html__('Orders by event date and follows the global "Show past events" setting (Events → Settings) — past events are hidden unless that setting is turned on.', 'simple_events'); ?></td>
                    </tr>
                    <tr>
                        <td><code>sec_events_by_date_all</code></td>
                        <td><?php echo esc_html__('Orders by event date and always includes past events (ignores the global setting).', 'simple_events'); ?></td>
                    </tr>
                </tbody>
            </table>
            <p><?php echo esc_html__('Set the Loop Grid\'s source to the Events post type, and use its own Order control for ascending (soonest first) or descending. No code snippet needed — the plugin wires these query IDs for you.', 'simple_events'); ?></p>

            <hr />
            <p>
                <a class="button button-secondary" href="<?php echo esc_url(admin_url('edit.php?post_type=simple-events&page=' . Simple_Events_Settings::PAGE)); ?>"><?php echo esc_html__('Go to Settings', 'simple_events'); ?></a>
            </p>
        </div>

        <style>
            .sec-docs__intro { font-size: 14px; max-width: 50em; }
            .sec-docs h3 { margin-top: 1.6em; }
            .sec-docs__item { max-width: 60em; margin-bottom: 8px; }
            .sec-docs__code code { display: inline-block; padding: 6px 10px; background: #f6f7f7; border: 1px solid #dcdcde; border-radius: 4px; font-size: 13px; }
            .sec-docs__desc { max-width: 60em; }
            .sec-docs__atts { max-width: 60em; margin-top: 6px; }
            .sec-docs__atts td code { background: #f6f7f7; padding: 1px 5px; border-radius: 3px; }
            .sec-docs__list { list-style: disc; margin-left: 2em; max-width: 60em; }
        </style>
        <?php
    }
}
