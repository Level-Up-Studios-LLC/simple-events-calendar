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
        'fields' => [
            // Create a date field for the event date
            create_date_field('event_date'),
            // Create a time field for the event start time
            create_time_field('event_start_time'),
            // Create a time field for the event end time
            create_time_field('event_end_time'),
            // Create a text field for the event location
            create_text_field('event_location'),
        ],
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
