<?php
// Shortcode file
if (!defined('ABSPATH')) {
    exit;
}

function all_trips_shortcode($atts) {
    $atts = shortcode_atts(
        array(
            'design' => '',
        ),
        $atts,
        'all_trips'
    );

    // Check if design attribute is provided
    if (empty($atts['design'])) {
        return '<div class="all-trips-error">Please specify a design ID or keyword.</div>';
    }

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

    // Get trip parameters based on design settings
    $trip_params = array();

    // Add trip type filter if specified
    if (isset($design['tripType']) && $design['tripType'] !== 'all') {
        $trip_params['type'] = $design['tripType'];

        // Add date range for one-time trips if both dates are set
        if ($design['tripType'] === 'one-time' &&
            !empty($design['dateRangeStart']) &&
            !empty($design['dateRangeEnd'])) {
            $trip_params['dateStart'] = $design['dateRangeStart'];
            $trip_params['dateEnd'] = $design['dateRangeEnd'];
        }
    }

    // Get the necessary settings
    $slug = get_option('all_trips_slug', '');
    $env = get_option('all_trips_env', 'https://pre.wetravel.to');

    // Build the trips display
    $output = '<div class="all-trips-container"
        data-display-type="' . esc_attr($design['displayType']) . '"
        data-button-type="' . esc_attr($design['buttonType']) . '"
        data-button-text="' . esc_attr($design['buttonText']) . '"
        data-button-color="' . esc_attr($design['buttonColor']) . '"
        data-slug="' . esc_attr($slug) . '"
        data-env="' . esc_attr($env) . '"
        data-trip-type="' . (isset($design['tripType']) ? esc_attr($design['tripType']) : 'all') . '"';

    // Add date range attributes if present
    if (isset($design['tripType']) && $design['tripType'] === 'one-time' &&
        !empty($design['dateRangeStart']) && !empty($design['dateRangeEnd'])) {
        $output .= ' data-date-start="' . esc_attr($design['dateRangeStart']) . '"';
        $output .= ' data-date-end="' . esc_attr($design['dateRangeEnd']) . '"';
    }

    $output .= '>';

    // Loading state
    $output .= '<div class="all-trips-loading">Loading trips...</div>';

    // Container for trips
    $output .= '<div class="all-trips-list"></div>';

    $output .= '</div>';

    // Enqueue specific JS based on display type
    if ($design['displayType'] === 'carousel') {
        wp_enqueue_script('all-trips-carousel', ALL_TRIPS_PLUGIN_URL . 'assets/js/carousel.js', array('jquery'), null, true);
    }

    // Enqueue the main trips loader script
    wp_enqueue_script('all-trips-loader', ALL_TRIPS_PLUGIN_URL . 'assets/js/trips-loader.js', array('jquery'), null, true);
    wp_localize_script('all-trips-loader', 'allTripsData', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'env' => $env,
        'slug' => $slug
    ));

    return $output;
}
