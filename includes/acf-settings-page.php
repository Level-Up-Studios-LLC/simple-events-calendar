<?php
/**
 * ACF demo Options Page: "Site Settings".
 *
 * @link https://www.advancedcustomfields.com/resources/options-page/
 */

if ( function_exists( 'acf_add_local_field_group' ) ) {
	add_action( 'acf/init', 'register_event_details_fields' );
}

/**
 * Registers the event details fields in the ACF field group.
 *
 * This function is hooked to the 'acf/init' action, so it will be
 * executed when ACF is initialized.
 */
function register_event_details_fields() {
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
function create_date_field($name) {
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
        'required' => 1,
        'display_format' => 'm/d/Y',
        'return_format' => 'F j, Y',
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
function create_time_field($name) {
    // Create the field key using the sanitized title of the name parameter.
    $fieldKey = 'field_' . sanitize_title($name);
    
    // Create the field label by capitalizing each word in the name parameter
    // and replacing underscores with spaces.
    $fieldLabel = ucwords(str_replace('_', ' ', $name));
    
    // Create the associative array with the necessary keys and values
    // to define a time field in ACF.
    return [
        'key' => $fieldKey,
        'label' => $fieldLabel,
        'name' => $name,
        'type' => 'time_picker',
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
function create_text_field($name) {
    // Create the field key using the sanitized title of the name parameter.
    $fieldKey = 'field_' . sanitize_title($name);
    
    // Create the field label by capitalizing each word in the name parameter
    // and replacing underscores with spaces.
    $fieldLabel = ucwords(str_replace('_', ' ', $name));
    
    // Create the associative array with the necessary keys and values
    // to define a text field in ACF.
    return [
        'key' => $fieldKey,
        'label' => $fieldLabel,
        'name' => $name,
        'type' => 'text',
    ];
}

/**
 * Creates an array that represents the location for a post type in
 * Advanced Custom Fields (ACF).
 *
 * @param string $postType The name of the post type.
 * @return array The location array for the post type.
 */
function create_post_type_location($postType) {
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
