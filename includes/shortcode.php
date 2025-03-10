<?php
/**
 * Shortcode functionality for All Trips Plugin
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add shortcode support for All Trips
 *
 * @param array $atts Shortcode attributes
 * @return string Rendered HTML
 */
function all_trips_shortcode($atts) {
    // Define default attributes
    $default_atts = array(
        'src'           => get_option('all_trips_src', ''),
        'slug'          => get_option('all_trips_slug', ''),
        'env'           => get_option('all_trips_env', 'https://pre.wetravel.to'),
        'display_type'  => get_option('all_trips_display_type', 'vertical'),
        'button_type'   => get_option('all_trips_button_type', 'book_now'),
        'button_text'   => '',
        'button_color'  => get_option('all_trips_button_color', '#33ae3f'),
        'items_per_page' => get_option('all_trips_items_per_page', 10),
        'load_more_text' => get_option('all_trips_load_more_text', 'Load More'),
        'trip_type'     => 0,
        'date_range'    => '',
    );

    // Parse incoming attributes into an array and merge it with defaults
    $atts = shortcode_atts($default_atts, $atts, 'all_trips');

    // Convert shortcode attribute names to match block attribute names
    $block_atts = array(
        'src'          => $atts['src'],
        'slug'         => $atts['slug'],
        'env'          => $atts['env'],
        'displayType'  => $atts['display_type'],
        'buttonType'   => $atts['button_type'],
        'buttonText'   => $atts['button_text'],
        'buttonColor'  => $atts['button_color'],
        'itemsPerPage' => intval($atts['items_per_page']),
        'loadMoreText' => $atts['load_more_text'],
        'tripType'     => intval($atts['trip_type']),
        'dateRange'    => $atts['date_range'],
    );

    // Set default buttonText based on buttonType if not provided
    if (empty($block_atts['buttonText'])) {
        $block_atts['buttonText'] = $block_atts['buttonType'] === 'book_now' ? 'Book Now' : 'View Trip';
    }

    // Use the existing block render function to maintain consistency
    return all_trips_block_render($block_atts);
}
