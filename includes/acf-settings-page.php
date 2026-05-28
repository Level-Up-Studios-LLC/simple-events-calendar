<?php

/**
 * ACF Event Details Field Group for Simple Events Calendar
 *
 * This file creates the custom fields needed for the events:
 * - Event Date
 * - Event Start Time  
 * - Event End Time
 * - Event Location
 *
 * @link https://www.advancedcustomfields.com/resources/register-fields-via-php/
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Only register if ACF is available
if (function_exists('acf_add_local_field_group')) {
    add_action('acf/init', 'register_event_details_fields');
}

/**
 * Registers the event details fields in the ACF field group.
 *
 * This function is hooked to the 'acf/init' action, so it will be
 * executed when ACF is initialized.
 */
function register_event_details_fields()
{
    // Check if ACF function exists
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    // Define the field group array
    $fieldGroup = [
        // Unique key for the field group
        'key' => 'group_event_details',
        // Title of the field group
        'title' => 'Event Details',
        // Array of fields for the field group
        'fields' => array_merge(
            [
                create_date_field('event_date'),
                create_time_field('event_start_time'),
                create_time_field('event_end_time'),
                create_text_field('event_location'),
            ],
            get_recurrence_fields()
        ),
        // Specify the location of the field group
        'location' => [
            // Create a post type location for the simple-events post type
            create_post_type_location('simple-events'),
        ],
        // Additional settings
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen' => [],
        'active' => true,
        'description' => 'Custom fields for Simple Events Calendar plugin',
    ];

    // Add the local field group to ACF
    acf_add_local_field_group($fieldGroup);
}

/**
 * Create a date field array for ACF.
 *
 * This function creates an array that can be used to add a date field
 * to an ACF field group. The function takes a single parameter, `$name`,
 * which is the name of the field. The function returns an associative array
 * with the necessary keys and values to define a date field in ACF.
 *
 * @param string $name The name of the field.
 * @return array The ACF date field array.
 */
function create_date_field($name)
{
    // Create the field key using the sanitized title of the name parameter.
    $fieldKey = 'field_' . sanitize_title($name);

    // Create the field label by capitalizing each word in the name parameter
    // and replacing underscores with spaces.
    $fieldLabel = ucwords(str_replace('_', ' ', $name));

    // Create the associative array with the necessary keys and values
    // to define a date field in ACF.
    return [
        'key' => $fieldKey,
        'label' => $fieldLabel,
        'name' => $name,
        'type' => 'date_picker',
        'instructions' => 'Select the date when this event will take place.',
        'required' => 1,
        'conditional_logic' => 0,
        'wrapper' => [
            'width' => '',
            'class' => '',
            'id' => '',
        ],
        'display_format' => 'm/d/Y',
        'return_format' => 'l, F j, Y',
        'first_day' => 1, // Monday
    ];
}

/**
 * Create a time field array for ACF.
 *
 * This function creates an array that can be used to add a time field
 * to an ACF field group. The function takes a single parameter, `$name`,
 * which is the name of the field. The function returns an associative array
 * with the necessary keys and values to define a time field in ACF.
 *
 * @param string $name The name of the field.
 * @return array The ACF time field array.
 */
function create_time_field($name)
{
    // Create the field key using the sanitized title of the name parameter.
    $fieldKey = 'field_' . sanitize_title($name);

    // Create the field label by capitalizing each word in the name parameter
    // and replacing underscores with spaces.
    $fieldLabel = ucwords(str_replace('_', ' ', $name));

    // Create instructions based on field name
    $instructions = '';
    if (strpos($name, 'start') !== false) {
        $instructions = 'What time does the event start?';
    } elseif (strpos($name, 'end') !== false) {
        $instructions = 'What time does the event end? (Optional)';
    }

    // Determine if field is required
    $required = (strpos($name, 'end') === false) ? 1 : 0;

    // Create the associative array with the necessary keys and values
    // to define a time field in ACF.
    return [
        'key' => $fieldKey,
        'label' => $fieldLabel,
        'name' => $name,
        'type' => 'time_picker',
        'instructions' => $instructions,
        'required' => $required,
        'conditional_logic' => 0,
        'wrapper' => [
            'width' => '50',
            'class' => '',
            'id' => '',
        ],
        'display_format' => 'g:i a',
        'return_format' => 'g:i a',
    ];
}

/**
 * Create a text field array for ACF.
 *
 * This function creates an array that can be used to add a text field
 * to an ACF field group. The function takes a single parameter, `$name`,
 * which is the name of the field. The function returns an associative array
 * with the necessary keys and values to define a text field in ACF.
 *
 * @param string $name The name of the field.
 * @return array The ACF text field array.
 */
function create_text_field($name)
{
    // Create the field key using the sanitized title of the name parameter.
    $fieldKey = 'field_' . sanitize_title($name);

    // Create the field label by capitalizing each word in the name parameter
    // and replacing underscores with spaces.
    $fieldLabel = ucwords(str_replace('_', ' ', $name));

    // Create instructions based on field name
    $instructions = '';
    $placeholder = '';
    if (strpos($name, 'location') !== false) {
        $instructions = 'Where will this event take place? (Optional)';
        $placeholder = 'e.g., Conference Room A, 123 Main St, or Online';
    }

    // Create the associative array with the necessary keys and values
    // to define a text field in ACF.
    return [
        'key' => $fieldKey,
        'label' => $fieldLabel,
        'name' => $name,
        'type' => 'text',
        'instructions' => $instructions,
        'required' => 0,
        'conditional_logic' => 0,
        'wrapper' => [
            'width' => '',
            'class' => '',
            'id' => '',
        ],
        'default_value' => '',
        'placeholder' => $placeholder,
        'prepend' => '',
        'append' => '',
        'maxlength' => 255,
    ];
}

/**
 * Creates an array that represents the location for a post type in
 * Advanced Custom Fields (ACF).
 *
 * @param string $postType The name of the post type.
 * @return array The location array for the post type.
 */
function create_post_type_location($postType)
{
    // The array that represents the location for a post type in ACF.
    // It contains a single element with the 'param', 'operator', and 'value'
    // keys. The 'param' key represents the parameter to compare, the
    // 'operator' key represents the operator to use in the comparison, and
    // the 'value' key represents the value to compare against.
    return [
        [
            'param' => 'post_type',
            'operator' => '==',
            'value' => $postType,
        ],
    ];
}

/**
 * Create a true/false field array for ACF.
 *
 * @param string $name         Field name.
 * @param string $label        Optional. Overrides the auto-generated label.
 * @param string $instructions Optional. Help text shown under the field label.
 * @param int    $default      Optional. Default value (0 or 1).
 * @return array
 */
function create_true_false_field($name, $label = '', $instructions = '', $default = 0)
{
    $field_key   = 'field_' . sanitize_title($name);
    $field_label = !empty($label) ? $label : ucwords(str_replace('_', ' ', $name));

    return [
        'key'              => $field_key,
        'label'            => $field_label,
        'name'             => $name,
        'type'             => 'true_false',
        'instructions'     => $instructions,
        'required'         => 0,
        'conditional_logic' => 0,
        'wrapper'          => ['width' => '', 'class' => '', 'id' => ''],
        'message'          => '',
        'default_value'    => (int) $default,
        'ui'               => 1,
        'ui_on_text'       => '',
        'ui_off_text'      => '',
    ];
}

/**
 * Create a select field array for ACF.
 *
 * @param string $name         Field name.
 * @param array  $choices      Associative array of value => label.
 * @param string $label        Optional. Overrides the auto-generated label.
 * @param string $instructions Optional. Help text.
 * @param string $default      Optional. Default selected value (key from $choices).
 * @param string $width        Optional. Wrapper width (percent).
 * @return array
 */
function create_select_field($name, $choices, $label = '', $instructions = '', $default = '', $width = '')
{
    $field_key   = 'field_' . sanitize_title($name);
    $field_label = !empty($label) ? $label : ucwords(str_replace('_', ' ', $name));

    return [
        'key'              => $field_key,
        'label'            => $field_label,
        'name'             => $name,
        'type'             => 'select',
        'instructions'     => $instructions,
        'required'         => 0,
        'conditional_logic' => 0,
        'wrapper'          => ['width' => $width, 'class' => '', 'id' => ''],
        'choices'          => $choices,
        'default_value'    => $default,
        'allow_null'       => 0,
        'multiple'         => 0,
        'ui'               => 0,
        'ajax'             => 0,
        'return_format'    => 'value',
        'placeholder'      => '',
    ];
}

/**
 * Create a number field array for ACF.
 *
 * @param string $name         Field name.
 * @param string $label        Optional. Overrides the auto-generated label.
 * @param string $instructions Optional. Help text.
 * @param int    $default      Optional. Default value.
 * @param int    $min          Optional. Minimum allowed value.
 * @param string $max          Optional. Maximum allowed value (empty for none).
 * @param string $width        Optional. Wrapper width (percent).
 * @return array
 */
function create_number_field($name, $label = '', $instructions = '', $default = 1, $min = 1, $max = '', $width = '')
{
    $field_key   = 'field_' . sanitize_title($name);
    $field_label = !empty($label) ? $label : ucwords(str_replace('_', ' ', $name));

    return [
        'key'              => $field_key,
        'label'            => $field_label,
        'name'             => $name,
        'type'             => 'number',
        'instructions'     => $instructions,
        'required'         => 0,
        'conditional_logic' => 0,
        'wrapper'          => ['width' => $width, 'class' => '', 'id' => ''],
        'default_value'    => $default,
        'placeholder'      => '',
        'prepend'          => '',
        'append'           => '',
        'min'              => $min,
        'max'              => $max,
        'step'             => 1,
    ];
}

/**
 * Build the recurrence-related field definitions for the Event Details group.
 *
 * Conditional logic only references already-registered fields by key. Toggling
 * `event_repeats` off after a save deletes future unmodified occurrences and
 * detaches modified ones — handled in Simple_Events_Recurrence on save_post.
 *
 * @return array
 */
function get_recurrence_fields()
{
    $repeats = create_true_false_field(
        'event_repeats',
        __('This event repeats', PLUGIN_TEXT_DOMAIN),
        __('Disabling recurrence on a saved series deletes future unmodified occurrences. Children with per-occurrence edits become standalone events.', PLUGIN_TEXT_DOMAIN),
        0
    );

    $interval = create_number_field(
        'event_repeat_interval',
        __('Repeat Every', PLUGIN_TEXT_DOMAIN),
        '',
        1,
        1,
        '',
        '25'
    );
    $interval['conditional_logic'] = [
        [
            ['field' => 'field_event_repeats', 'operator' => '==', 'value' => '1'],
        ],
    ];

    $frequency = create_select_field(
        'event_repeat_frequency',
        [
            'daily'   => __('Day(s)', PLUGIN_TEXT_DOMAIN),
            'weekly'  => __('Week(s)', PLUGIN_TEXT_DOMAIN),
            'monthly' => __('Month(s)', PLUGIN_TEXT_DOMAIN),
            'yearly'  => __('Year(s)', PLUGIN_TEXT_DOMAIN),
        ],
        __('Frequency', PLUGIN_TEXT_DOMAIN),
        '',
        'weekly',
        '25'
    );
    $frequency['conditional_logic'] = [
        [
            ['field' => 'field_event_repeats', 'operator' => '==', 'value' => '1'],
        ],
    ];

    $end_type = create_select_field(
        'event_repeat_end_type',
        [
            'never' => __('Never', PLUGIN_TEXT_DOMAIN),
            'count' => __('After a number of occurrences', PLUGIN_TEXT_DOMAIN),
            'until' => __('On a specific date', PLUGIN_TEXT_DOMAIN),
        ],
        __('Ends', PLUGIN_TEXT_DOMAIN),
        '',
        'count',
        '50'
    );
    $end_type['conditional_logic'] = [
        [
            ['field' => 'field_event_repeats', 'operator' => '==', 'value' => '1'],
        ],
    ];

    $count = create_number_field(
        'event_repeat_count',
        __('Number of Occurrences', PLUGIN_TEXT_DOMAIN),
        __('Total occurrences including the first event.', PLUGIN_TEXT_DOMAIN),
        10,
        1,
        '',
        '50'
    );
    $count['conditional_logic'] = [
        [
            ['field' => 'field_event_repeats', 'operator' => '==', 'value' => '1'],
            ['field' => 'field_event_repeat_end_type', 'operator' => '==', 'value' => 'count'],
        ],
    ];

    $until = create_date_field('event_repeat_until');
    $until['label']             = __('End Date', PLUGIN_TEXT_DOMAIN);
    $until['instructions']      = __('Final date on which a recurrence may fall.', PLUGIN_TEXT_DOMAIN);
    $until['required']          = 0;
    $until['wrapper']['width']  = '50';
    $until['conditional_logic'] = [
        [
            ['field' => 'field_event_repeats', 'operator' => '==', 'value' => '1'],
            ['field' => 'field_event_repeat_end_type', 'operator' => '==', 'value' => 'until'],
        ],
    ];

    return [$repeats, $frequency, $interval, $end_type, $count, $until];
}

/**
 * Force field group registration if it's missing
 * This is a backup function that can be called manually
 */
function simple_events_force_register_fields()
{
    if (function_exists('acf_add_local_field_group')) {
        register_event_details_fields();


        return true;
    }

    return false;
}

/**
 * Check if the field group exists and create it if missing
 * This function can be called from other parts of the plugin
 */
function simple_events_ensure_field_group_exists()
{
    // Check if field group exists
    if (function_exists('acf_get_field_group')) {
        $field_group = acf_get_field_group('group_event_details');
        if ($field_group) {
            return true; // Field group exists
        }
    }

    // Field group doesn't exist, try to create it
    return simple_events_force_register_fields();
}
