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
        'design'        => '',  // Design ID or keyword
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

    // Check if using a design configuration
    if (!empty($atts['design'])) {
        // Get all designs
        $designs = get_option('all_trips_designs', array());
        $design_id = $atts['design'];
        $design = null;

        // First try to find design by keyword
        foreach ($designs as $id => $design_data) {
            if (isset($design_data['keyword']) && $design_data['keyword'] === $design_id) {
                $design = $design_data;
                break;
            }
        }

        // If not found by keyword, try to find by design ID
        if ($design === null) {
            if (isset($designs[$design_id])) {
                $design = $designs[$design_id];
            } else {
                return '<div class="all-trips-error">Design not found: ' . esc_html($design_id) . '</div>';
            }
        }

        // Override attributes with design values
        if (!empty($design['displayType'])) $atts['display_type'] = $design['displayType'];
        if (!empty($design['buttonType'])) $atts['button_type'] = $design['buttonType'];
        if (!empty($design['buttonText'])) $atts['button_text'] = $design['buttonText'];
        if (!empty($design['buttonColor'])) $atts['button_color'] = $design['buttonColor'];
        if (!empty($design['tripType'])) $atts['trip_type'] = $design['tripType'];

        // Handle date range for one-time trips
        if (!empty($design['tripType']) && $design['tripType'] === 'one-time' &&
            !empty($design['dateRangeStart']) && !empty($design['dateRangeEnd'])) {
            $atts['date_range'] = $design['dateRangeStart'] . ',' . $design['dateRangeEnd'];
        }
    }

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
        'tripType'     => is_numeric($atts['trip_type']) ? intval($atts['trip_type']) : $atts['trip_type'],
        'dateRange'    => $atts['date_range'],
    );

    // Set default buttonText based on buttonType if not provided
    if (empty($block_atts['buttonText'])) {
        $block_atts['buttonText'] = $block_atts['buttonType'] === 'book_now' ? 'Book Now' : 'View Trip';
    }

    // Use the existing block render function to maintain consistency
    if (function_exists('all_trips_block_render')) {
        return all_trips_block_render($block_atts);
    } else {
        // Fallback if block render function doesn't exist
        return render_all_trips_display($block_atts);
    }
}

/**
 * Fallback render function in case block render function doesn't exist
 *
 * @param array $atts Trip display attributes
 * @return string Rendered HTML
 */
function render_all_trips_display($atts) {
    // Build the trips display
    $output = '<div class="all-trips-container"
        data-display-type="' . esc_attr($atts['displayType']) . '"
        data-button-type="' . esc_attr($atts['buttonType']) . '"
        data-button-text="' . esc_attr($atts['buttonText']) . '"
        data-button-color="' . esc_attr($atts['buttonColor']) . '"
        data-slug="' . esc_attr($atts['slug']) . '"
        data-env="' . esc_attr($atts['env']) . '"
        data-trip-type="' . esc_attr($atts['tripType']) . '"
        data-items-per-page="' . esc_attr($atts['itemsPerPage']) . '"
        data-load-more-text="' . esc_attr($atts['loadMoreText']) . '"';

    // Add date range attributes if present
    if (!empty($atts['dateRange'])) {
        $date_parts = explode(',', $atts['dateRange']);
        if (count($date_parts) === 2) {
            $output .= ' data-date-start="' . esc_attr(trim($date_parts[0])) . '"';
            $output .= ' data-date-end="' . esc_attr(trim($date_parts[1])) . '"';
        }
    }

    $output .= '>';

    // Loading state
    $output .= '<div class="all-trips-loading">Loading trips...</div>';

    // Container for trips
    $output .= '<div class="all-trips-list"></div>';

    $output .= '</div>';

    // Enqueue specific JS based on display type
    if ($atts['displayType'] === 'carousel') {
        wp_enqueue_script('all-trips-carousel', ALL_TRIPS_PLUGIN_URL . 'assets/js/carousel.js', array('jquery'), null, true);
    }

    // Enqueue the main trips loader script
    wp_enqueue_script('all-trips-loader', ALL_TRIPS_PLUGIN_URL . 'assets/js/trips-loader.js', array('jquery'), null, true);
    wp_localize_script('all-trips-loader', 'allTripsData', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'env' => $atts['env'],
        'slug' => $atts['slug']
    ));

    return $output;
}
