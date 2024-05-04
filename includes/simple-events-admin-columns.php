<?php

if (!function_exists('register_simple_events_admin_columns')) {
    /**
     * Registers the custom columns for the 'simple-events' post type in the admin dashboard.
     *
     * @param array $columns An array of column names.
     * @return array The modified array of column names.
     */
    function register_simple_events_admin_columns($columns)
    {
        // Add the 'event_category' and 'event_date' columns
        return array_merge(
            $columns,
            array(
                'taxonomy-simple-events-cat' => __('Event Categories', PLUGIN_TEXT_DOMAIN),
                'event_date'                 => __('Event Date', PLUGIN_TEXT_DOMAIN)
            )
        );
    }

    /**
     * Fills the custom columns for the 'simple-events' post type in the admin dashboard.
     *
     * @param string $column The name of the column being filled.
     * @param int $post_id The ID of the post being displayed.
     * @return void
     */
    function fill_simple_events_admin_columns($column, $post_id)
    {
        // Fill the 'event_category' and 'event_date' columns
        switch ($column) {
            case 'event_date':
                $time = strtotime(get_field($column)); // ACF custom date field
                $date = date("F d, Y", $time);
                echo $date;
                break;
            case 'taxonomy-simple-events-cat':
                echo get_post_meta($post_id, $column, true);
                break;
        }
    }

    /**
     * Makes the 'event_date' column sortable in the 'simple-events' post type admin dashboard.
     *
     * @param array $columns An array of column names.
     * @return array The modified array of column names.
     */
    function sortable_simple_events_admin_columns($columns)
    {
        // Make the 'event_date' column sortable
        $columns['event_date'] = 'event_date';
        return $columns;
    }

    /**
     * Orders the 'simple-events' post type posts by the 'event_date' meta key.
     *
     * @param WP_Query $query The current WP_Query object.
     * @return void
     */
    function order_simple_events_admin_columns($query)
    {
        // Order the posts by the 'event_date' meta key
        $orderby = $query->get('orderby');

        switch ($orderby) {
            case 'event_date':
                $query->set('meta_key', 'event_date');
                $query->set('orderby', 'meta_value_num');
                break;
            default:
                break;
        }
    }

    // Register the custom columns
    add_filter('manage_simple-events_posts_columns', 'register_simple_events_admin_columns');

    // Fill the custom columns
    add_action('manage_simple-events_posts_custom_column', 'fill_simple_events_admin_columns', 10, 2);

    // Make the 'event_date' column sortable
    add_filter('manage_edit-simple-events_sortable_columns', 'sortable_simple_events_admin_columns');

    // Order the posts by the 'event_date' meta key
    add_action('pre_get_posts', 'order_simple_events_admin_columns');
}
