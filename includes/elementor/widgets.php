<?php

/**
 * Elementor widgets for Simple Events Calendar element fields.
 *
 * Required only from Simple_Events_Elementor::register_widgets(), so the
 * Elementor base class is guaranteed to exist here.
 *
 * @package Simple_Events_Calendar
 * @since 5.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Base widget: shared category, event resolution, render.
 */
abstract class Simple_Events_Widget_Base extends \Elementor\Widget_Base {

    /**
     * Renderer element key (title|image|date|time|location|excerpt|content|categories|button).
     *
     * @return string
     */
    abstract protected function sec_key();

    /**
     * Widget categories.
     *
     * @return array
     */
    public function get_categories() {
        return array('simple-events');
    }

    /**
     * Search keywords.
     *
     * @return array
     */
    public function get_keywords() {
        return array('event', 'events', 'calendar', 'simple events');
    }

    /**
     * Register controls (content + style).
     */
    protected function register_controls() {
        $this->start_controls_section(
            'sec_content',
            array(
                'label' => __('Event', 'simple_events'),
                'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
            )
        );

        $this->sec_content_controls();

        $this->end_controls_section();

        $this->sec_style_controls();
    }

    /**
     * Element-specific content controls. Override as needed.
     */
    protected function sec_content_controls() {}

    /**
     * Common style controls (alignment, color, typography).
     */
    protected function sec_style_controls() {
        $this->start_controls_section(
            'sec_style',
            array(
                'label' => __('Style', 'simple_events'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_responsive_control(
            'sec_align',
            array(
                'label'     => __('Alignment', 'simple_events'),
                'type'      => \Elementor\Controls_Manager::CHOOSE,
                'options'   => array(
                    'left'   => array('title' => __('Left', 'simple_events'), 'icon' => 'eicon-text-align-left'),
                    'center' => array('title' => __('Center', 'simple_events'), 'icon' => 'eicon-text-align-center'),
                    'right'  => array('title' => __('Right', 'simple_events'), 'icon' => 'eicon-text-align-right'),
                ),
                'selectors' => array('{{WRAPPER}}' => 'text-align: {{VALUE}};'),
            )
        );

        $this->add_control(
            'sec_color',
            array(
                'label'     => __('Text Color', 'simple_events'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => array('{{WRAPPER}}, {{WRAPPER}} a' => 'color: {{VALUE}};'),
            )
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            array(
                'name'     => 'sec_typography',
                'selector' => '{{WRAPPER}}',
            )
        );

        $this->end_controls_section();
    }

    /**
     * Render arguments passed to the renderer method. Override as needed.
     *
     * @return array
     */
    protected function sec_render_args() {
        return array();
    }

    /**
     * Render the widget. Gated to event contexts: outside one, render nothing
     * on the front end and a hint in the Elementor editor.
     */
    protected function render() {
        $post_id = Simple_Events_Elementor::resolve_event_id();

        if (!$post_id) {
            if (Simple_Events_Elementor::is_edit_hint_allowed()) {
                echo '<span class="sec-elementor-hint" style="display:block;padding:10px 12px;border:1px dashed #c3c4c7;border-radius:4px;color:#646970;font-size:12px;">'
                    . esc_html__('Displays the current event. Use this element inside a single-event template, an event archive, or a Loop Grid.', 'simple_events')
                    . '</span>';
            }
            return;
        }

        $method = array('Simple_Events_Renderer', $this->sec_key());
        if (!is_callable($method)) {
            return;
        }

        // Renderer output is escaped within the renderer.
        echo call_user_func($method, $post_id, $this->sec_render_args()); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}

/**
 * Event Title widget.
 */
class Simple_Events_Widget_Title extends Simple_Events_Widget_Base {
    protected function sec_key() { return 'title'; }
    public function get_name() { return 'sec-event-title'; }
    public function get_title() { return __('Event Title', 'simple_events'); }
    public function get_icon() { return 'eicon-heading'; }

    protected function sec_content_controls() {
        $this->add_control('sec_link', array(
            'label'        => __('Link to event', 'simple_events'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'default'      => '',
        ));
        $this->add_control('sec_tag', array(
            'label'   => __('HTML tag', 'simple_events'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => array('h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'span' => 'span'),
            'default' => 'h2',
        ));
    }

    protected function sec_render_args() {
        $s = $this->get_settings_for_display();
        return array(
            'link' => 'yes' === ($s['sec_link'] ?? ''),
            'tag'  => $s['sec_tag'] ?? 'h2',
        );
    }
}

/**
 * Event Image widget.
 */
class Simple_Events_Widget_Image extends Simple_Events_Widget_Base {
    protected function sec_key() { return 'image'; }
    public function get_name() { return 'sec-event-image'; }
    public function get_title() { return __('Event Image', 'simple_events'); }
    public function get_icon() { return 'eicon-image'; }

    protected function sec_content_controls() {
        $this->add_control('sec_size', array(
            'label'   => __('Image size', 'simple_events'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => array(
                'thumbnail'    => __('Thumbnail', 'simple_events'),
                'medium'       => __('Medium', 'simple_events'),
                'medium_large' => __('Medium Large', 'simple_events'),
                'large'        => __('Large', 'simple_events'),
                'full'         => __('Full', 'simple_events'),
            ),
            'default' => 'large',
        ));
        $this->add_control('sec_link', array(
            'label'   => __('Link to event', 'simple_events'),
            'type'    => \Elementor\Controls_Manager::SWITCHER,
            'default' => '',
        ));
    }

    protected function sec_render_args() {
        $s = $this->get_settings_for_display();
        return array(
            'size' => $s['sec_size'] ?? 'large',
            'link' => 'yes' === ($s['sec_link'] ?? ''),
        );
    }
}

/**
 * Event Date widget.
 */
class Simple_Events_Widget_Date extends Simple_Events_Widget_Base {
    protected function sec_key() { return 'date'; }
    public function get_name() { return 'sec-event-date'; }
    public function get_title() { return __('Event Date', 'simple_events'); }
    public function get_icon() { return 'eicon-calendar'; }

    protected function sec_content_controls() {
        $this->add_control('sec_format', array(
            'label'       => __('Date format override', 'simple_events'),
            'description' => __('PHP date format. Leave blank to use the plugin setting.', 'simple_events'),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => '',
        ));
    }

    protected function sec_render_args() {
        $s = $this->get_settings_for_display();
        return array('format' => $s['sec_format'] ?? '');
    }
}

/**
 * Event Time widget.
 */
class Simple_Events_Widget_Time extends Simple_Events_Widget_Base {
    protected function sec_key() { return 'time'; }
    public function get_name() { return 'sec-event-time'; }
    public function get_title() { return __('Event Time', 'simple_events'); }
    public function get_icon() { return 'eicon-clock-o'; }
}

/**
 * Event Location widget.
 */
class Simple_Events_Widget_Location extends Simple_Events_Widget_Base {
    protected function sec_key() { return 'location'; }
    public function get_name() { return 'sec-event-location'; }
    public function get_title() { return __('Event Location', 'simple_events'); }
    public function get_icon() { return 'eicon-map-pin'; }

    protected function sec_content_controls() {
        $this->add_control('sec_icon', array(
            'label'   => __('Show icon', 'simple_events'),
            'type'    => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
        ));
    }

    protected function sec_render_args() {
        $s = $this->get_settings_for_display();
        return array('icon' => 'yes' === ($s['sec_icon'] ?? 'yes'));
    }
}

/**
 * Event Excerpt widget.
 */
class Simple_Events_Widget_Excerpt extends Simple_Events_Widget_Base {
    protected function sec_key() { return 'excerpt'; }
    public function get_name() { return 'sec-event-excerpt'; }
    public function get_title() { return __('Event Excerpt', 'simple_events'); }
    public function get_icon() { return 'eicon-text'; }

    protected function sec_content_controls() {
        $this->add_control('sec_words', array(
            'label'   => __('Word limit', 'simple_events'),
            'type'    => \Elementor\Controls_Manager::NUMBER,
            'default' => 30,
            'min'     => 0,
        ));
    }

    protected function sec_render_args() {
        $s = $this->get_settings_for_display();
        return array('words' => isset($s['sec_words']) ? (int) $s['sec_words'] : 30);
    }
}

/**
 * Event Content widget.
 */
class Simple_Events_Widget_Content extends Simple_Events_Widget_Base {
    protected function sec_key() { return 'content'; }
    public function get_name() { return 'sec-event-content'; }
    public function get_title() { return __('Event Content', 'simple_events'); }
    public function get_icon() { return 'eicon-post-content'; }
}

/**
 * Event Categories widget.
 */
class Simple_Events_Widget_Categories extends Simple_Events_Widget_Base {
    protected function sec_key() { return 'categories'; }
    public function get_name() { return 'sec-event-categories'; }
    public function get_title() { return __('Event Categories', 'simple_events'); }
    public function get_icon() { return 'eicon-folder'; }

    protected function sec_content_controls() {
        $this->add_control('sec_link', array(
            'label'   => __('Link to category', 'simple_events'),
            'type'    => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
        ));
    }

    protected function sec_render_args() {
        $s = $this->get_settings_for_display();
        return array('link' => 'yes' === ($s['sec_link'] ?? 'yes'));
    }
}

/**
 * Event Button widget.
 */
class Simple_Events_Widget_Button extends Simple_Events_Widget_Base {
    protected function sec_key() { return 'button'; }
    public function get_name() { return 'sec-event-button'; }
    public function get_title() { return __('Event Button', 'simple_events'); }
    public function get_icon() { return 'eicon-button'; }

    protected function sec_content_controls() {
        $this->add_control('sec_text', array(
            'label'   => __('Button text', 'simple_events'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => __('View Event', 'simple_events'),
        ));
    }

    protected function sec_render_args() {
        $s = $this->get_settings_for_display();
        return array('text' => $s['sec_text'] ?? '');
    }
}
