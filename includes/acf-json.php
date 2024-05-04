<?php

/**
 * ACF Set custom load and save JSON points.
 *
 * @link https://www.advancedcustomfields.com/resources/local-json/
 */

add_filter('acf/settings/load_json', 'my_acf_json_load_point');
add_filter('acf/settings/save_json/type=acf-acf-field-group', 'acf_json_save_path_for_field_group');
add_filter('acf/settings/save_json/type=acf-acf-ui-options-page', 'acf_json_save_path_for_options');
add_filter('acf/settings/save_json/type=acf-post-type', 'acf_json_save_path_for_post_type');
add_filter('acf/settings/save_json/type=acf-taxonomy', 'acf_json_save_path_for_taxonomy');
add_filter('acf/json/save_file_name', 'custom_acf_json_filename', 10, 3);

/**
 * Custom load and save JSON points for ACF.
 *
 * This function is a filter that modifies the ACF load and save JSON points.
 *
 * @param array $paths An array of paths.
 * @return array An array of modified paths.
 */
function my_acf_json_load_point($paths)
{
    // Remove the original path (optional).
    unset($paths[0]);

    // Append the new path for ACF Field Groups and return it.
    $paths[] = PLUGIN_DIR . '/includes/acf-json/field-groups';
    
    // Append the new path for ACF Options Pages and return it.
    $paths[] = PLUGIN_DIR . '/includes/acf-json/options-pages';
    
    // Append the new path for ACF Post Types and return it.
    $paths[] = PLUGIN_DIR . '/includes/acf-json/post-types';
    
    // Append the new path for ACF Taxonomies and return it.
    $paths[] = PLUGIN_DIR . '/includes/acf-json/taxonomies';

    return $paths;
}


/**
 * Returns the path to save ACF Field Groups json files.
 *
 * @return string The path to save ACF Field Groups json files.
 */
function acf_json_save_path_for_field_group()
{
    // The path to save ACF Field Groups json files.
    return PLUGIN_DIR . '/includes/acf-json/field-groups';
}


/**
 * Returns the path to save ACF Options Pages json files.
 *
 * This function returns the path where ACF Options Pages json files will be saved.
 *
 * @return string The path to save ACF Options Pages json files.
 */
function acf_json_save_path_for_options()
{
    // The path to save ACF Options Pages json files.
    return PLUGIN_DIR . '/includes/acf-json/options-pages';
}


/**
 * Returns the path to save ACF Post Types json files.
 *
 * This function returns the path where ACF Post Types json files will be saved.
 *
 * @return string The path to save ACF Post Types json files.
 */
function acf_json_save_path_for_post_type()
{
    // The path to save ACF Post Types json files.
    return PLUGIN_DIR . '/includes/acf-json/post-types';
}


/**
 * Returns the path to save ACF Taxonomy json files.
 *
 * This function returns the path where ACF Taxonomy json files will be saved.
 *
 * @return string The path to save ACF Taxonomy json files.
 */
function acf_json_save_path_for_taxonomy()
{
    // The path to save ACF Taxonomy json files.
    return PLUGIN_DIR . '/includes/acf-json/taxonomies';
}


/**
 * Customizes the filename of the ACF json files.
 *
 * This function modifies the filename of the ACF json files based on the
 * provided post title. The spaces and underscores in the title are replaced
 * with hyphens, and the filename is converted to lowercase.
 *
 * @param string $filename The original filename.
 * @param array $post The post array containing the title.
 * @param string $load_path The load path.
 * @return string The modified filename.
 */
function custom_acf_json_filename($filename, $post, $load_path)
{
    // Replace spaces and underscores in the title with hyphens.
    $filename = str_replace(
        array(
            ' ',
            '_',
        ),
        array(
            '-',
            '-'
        ),
        $post['title']
    );

    // Convert the filename to lowercase.
    $filename = strtolower($filename) . '.json';

    // Return the modified filename.
    return $filename;
}
