<?php

/**
 * Admin columns functionality for Simple Events Calendar
 *
 * Adds custom columns to the admin events list with proper sorting
 * and filtering capabilities.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('register_simple_events_admin_columns')) {

    /**
     * Register custom columns for the 'simple-events' post type in admin
     *
     * @param array $columns An array of column names
     * @return array The modified array of column names
     */
    function register_simple_events_admin_columns($columns)
    {
        // Create new columns array with proper ordering
        $new_columns = array();

        // Add checkbox and title first
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];

        // Add event-specific columns
        $new_columns['event_thumbnail'] = __('Image', PLUGIN_TEXT_DOMAIN);
        $new_columns['event_date'] = __('Event Date', PLUGIN_TEXT_DOMAIN);
        $new_columns['event_time'] = __('Time', PLUGIN_TEXT_DOMAIN);
        $new_columns['event_location'] = __('Location', PLUGIN_TEXT_DOMAIN);
        $new_columns['taxonomy-simple-events-cat'] = __('Categories', PLUGIN_TEXT_DOMAIN);

        // Add remaining default columns
        if (isset($columns['date'])) {
            $new_columns['date'] = $columns['date'];
        }

        return $new_columns;
    }

    /**
     * Fill the custom columns with content
     *
     * @param string $column The name of the column being filled
     * @param int $post_id The ID of the post being displayed
     */
    function fill_simple_events_admin_columns($column, $post_id)
    {
        switch ($column) {
            case 'event_thumbnail':
                $thumbnail = get_the_post_thumbnail($post_id, array(60, 60));
                if ($thumbnail) {
                    echo '<div class="simple-events-admin-thumbnail">' . $thumbnail . '</div>';
                } else {
                    echo '<div class="simple-events-admin-no-thumbnail">—</div>';
                }
                break;

            case 'event_date':
                $event_date = get_field('event_date', $post_id);
                if ($event_date) {
                    $timestamp = strtotime($event_date);
                    $formatted_date = date_i18n(get_option('date_format'), $timestamp);
                    $iso_date = date('Y-m-d', $timestamp);

                    echo '<time datetime="' . esc_attr($iso_date) . '">' . esc_html($formatted_date) . '</time>';

                    // Add status indicator
                    $today = date('Y-m-d');
                    if ($iso_date < $today) {
                        echo ' <span class="simple-events-status simple-events-past">(' . __('Past', PLUGIN_TEXT_DOMAIN) . ')</span>';
                    } elseif ($iso_date === $today) {
                        echo ' <span class="simple-events-status simple-events-today">(' . __('Today', PLUGIN_TEXT_DOMAIN) . ')</span>';
                    } else {
                        echo ' <span class="simple-events-status simple-events-upcoming">(' . __('Upcoming', PLUGIN_TEXT_DOMAIN) . ')</span>';
                    }
                } else {
                    echo '<span class="simple-events-missing-data">—</span>';
                }
                break;

            case 'event_time':
                $start_time = get_field('event_start_time', $post_id);
                $end_time = get_field('event_end_time', $post_id);

                if ($start_time) {
                    echo '<div class="simple-events-time-display">';
                    echo '<span class="simple-events-start-time">' . esc_html($start_time) . '</span>';

                    if ($end_time) {
                        echo '<span class="simple-events-time-separator"> - </span>';
                        echo '<span class="simple-events-end-time">' . esc_html($end_time) . '</span>';
                    }
                    echo '</div>';
                } else {
                    echo '<span class="simple-events-missing-data">—</span>';
                }
                break;

            case 'event_location':
                $location = get_field('event_location', $post_id);
                if ($location) {
                    echo '<div class="simple-events-location">' . esc_html($location) . '</div>';
                } else {
                    echo '<span class="simple-events-missing-data">—</span>';
                }
                break;

            case 'taxonomy-simple-events-cat':
                $terms = get_the_terms($post_id, 'simple-events-cat');
                if ($terms && !is_wp_error($terms)) {
                    $term_links = array();
                    foreach ($terms as $term) {
                        $term_links[] = '<a href="' . esc_url(add_query_arg(array('simple-events-cat' => $term->slug), admin_url('edit.php?post_type=simple-events'))) . '">' . esc_html($term->name) . '</a>';
                    }
                    echo implode(', ', $term_links);
                } else {
                    echo '<span class="simple-events-missing-data">—</span>';
                }
                break;
        }
    }

    /**
     * Make columns sortable
     *
     * @param array $columns An array of sortable column names
     * @return array The modified array of sortable column names
     */
    function sortable_simple_events_admin_columns($columns)
    {
        $columns['event_date'] = 'event_date';
        $columns['event_time'] = 'event_start_time';
        $columns['event_location'] = 'event_location';

        return $columns;
    }

    /**
     * Handle sorting for custom columns
     *
     * @param WP_Query $query The current WP_Query object
     */
    function order_simple_events_admin_columns($query)
    {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }

        $orderby = $query->get('orderby');

        switch ($orderby) {
            case 'event_date':
                $query->set('meta_key', 'event_date');
                $query->set('orderby', 'meta_value');
                break;

            case 'event_start_time':
                $query->set('meta_key', 'event_start_time');
                $query->set('orderby', 'meta_value');
                break;

            case 'event_location':
                $query->set('meta_key', 'event_location');
                $query->set('orderby', 'meta_value');
                break;
        }
    }

    /**
     * Add filtering dropdown for event categories and date status
     */
    function simple_events_admin_filter_dropdown()
    {
        global $typenow;

        if ($typenow === 'simple-events') {
            // Category filter
            $taxonomy = 'simple-events-cat';
            $selected_cat = isset($_GET[$taxonomy]) ? $_GET[$taxonomy] : '';

            $terms = get_terms(array(
                'taxonomy' => $taxonomy,
                'hide_empty' => true
            ));

            if (!empty($terms)) {
                echo '<select name="' . $taxonomy . '" id="' . $taxonomy . '" class="postform">';
                echo '<option value="">' . __('All Categories', PLUGIN_TEXT_DOMAIN) . '</option>';

                foreach ($terms as $term) {
                    printf(
                        '<option value="%s"%s>%s (%d)</option>',
                        $term->slug,
                        selected($selected_cat, $term->slug, false),
                        $term->name,
                        $term->count
                    );
                }

                echo '</select>';
            }

            // Date status filter
            $selected_status = isset($_GET['event_status']) ? $_GET['event_status'] : '';

            echo '<select name="event_status" id="event_status" class="postform">';
            echo '<option value="">' . __('All Events', PLUGIN_TEXT_DOMAIN) . '</option>';
            echo '<option value="upcoming"' . selected($selected_status, 'upcoming', false) . '>' . __('Upcoming Events', PLUGIN_TEXT_DOMAIN) . '</option>';
            echo '<option value="today"' . selected($selected_status, 'today', false) . '>' . __('Today\'s Events', PLUGIN_TEXT_DOMAIN) . '</option>';
            echo '<option value="past"' . selected($selected_status, 'past', false) . '>' . __('Past Events', PLUGIN_TEXT_DOMAIN) . '</option>';
            echo '</select>';
        }
    }

    /**
     * Handle the custom event status filter
     */
    function simple_events_admin_filter_by_status($query)
    {
        global $pagenow, $typenow;

        if ($pagenow === 'edit.php' && $typenow === 'simple-events' && isset($_GET['event_status']) && !empty($_GET['event_status'])) {
            $status = $_GET['event_status'];
            $today = date('Ymd');

            $meta_query = array();

            switch ($status) {
                case 'upcoming':
                    $meta_query[] = array(
                        'key' => 'event_date',
                        'value' => $today,
                        'compare' => '>',
                        'type' => 'DATE'
                    );
                    break;

                case 'today':
                    $meta_query[] = array(
                        'key' => 'event_date',
                        'value' => $today,
                        'compare' => '=',
                        'type' => 'DATE'
                    );
                    break;

                case 'past':
                    $meta_query[] = array(
                        'key' => 'event_date',
                        'value' => $today,
                        'compare' => '<',
                        'type' => 'DATE'
                    );
                    break;
            }

            if (!empty($meta_query)) {
                $query->set('meta_query', $meta_query);
                $query->set('meta_key', 'event_date');
                $query->set('orderby', 'meta_value');
                $query->set('order', $status === 'past' ? 'DESC' : 'ASC');
            }
        }
    }

    /**
     * Add custom CSS for admin columns
     */
    function simple_events_admin_column_styles()
    {
        global $typenow;

        if ($typenow === 'simple-events') {
?>
            <style>
                .wp-list-table .column-event_thumbnail {
                    width: 80px;
                    text-align: center;
                }

                .wp-list-table .column-event_date {
                    width: 140px;
                }

                .wp-list-table .column-event_time {
                    width: 120px;
                }

                .wp-list-table .column-event_location {
                    width: 150px;
                }

                .simple-events-admin-thumbnail img {
                    border-radius: 4px;
                    display: block;
                    margin: 0 auto;
                }

                .simple-events-admin-no-thumbnail {
                    color: #666;
                    font-style: italic;
                }

                .simple-events-missing-data {
                    color: #999;
                }

                .simple-events-status {
                    font-size: 11px;
                    font-weight: bold;
                }

                .simple-events-past {
                    color: #999;
                }

                .simple-events-today {
                    color: #d63384;
                }

                .simple-events-upcoming {
                    color: #198754;
                }

                .simple-events-time-display {
                    white-space: nowrap;
                }

                .simple-events-time-separator {
                    color: #666;
                }

                .simple-events-location {
                    max-width: 150px;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    white-space: nowrap;
                }
            </style>
            <?php
        }
    }

    /**
     * Add quick edit support for custom fields
     */
    function simple_events_quick_edit_fields($column_name, $post_type)
    {
        if ($post_type !== 'simple-events') {
            return;
        }

        switch ($column_name) {
            case 'event_date':
            ?>
                <fieldset class="inline-edit-col-left">
                    <div class="inline-edit-col">
                        <label>
                            <span class="title"><?php _e('Event Date', PLUGIN_TEXT_DOMAIN); ?></span>
                            <input type="date" name="event_date" value="" />
                        </label>
                    </div>
                </fieldset>
<?php
                break;
        }
    }

    // Register all the hooks
    add_filter('manage_simple-events_posts_columns', 'register_simple_events_admin_columns');
    add_action('manage_simple-events_posts_custom_column', 'fill_simple_events_admin_columns', 10, 2);
    add_filter('manage_edit-simple-events_sortable_columns', 'sortable_simple_events_admin_columns');
    add_action('pre_get_posts', 'order_simple_events_admin_columns');
    add_action('restrict_manage_posts', 'simple_events_admin_filter_dropdown');
    add_action('parse_query', 'simple_events_admin_filter_by_status');
    add_action('admin_head', 'simple_events_admin_column_styles');
    add_action('quick_edit_custom_box', 'simple_events_quick_edit_fields', 10, 2);
}
