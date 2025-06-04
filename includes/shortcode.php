<?php
/**
 * Shortcode functionality for WeTravel Widgets Plugin
 *
 * @package WordPress
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Add shortcode support for WeTravel Trips
 *
 * @param array $atts Shortcode attributes.
 * @return string Rendered HTML
 */
function wtwidget_trips_shortcode( $atts ) {
	// Store the original unmerged attributes
	$original_atts = (array) $atts;

	// Define default attributes.
	$default_atts = array(
		'widget'                 => '',  // Widget ID or keyword.
		'slug'                   => get_option( 'wetravel_trips_slug', '' ),
		'env'                    => get_option( 'wetravel_trips_env', 'https://pre.wetravel.to' ),
		'wetravel_trips_user_id' => get_option( 'wetravel_trips_user_id', '' ),
		'display_type'           => get_option( 'wetravel_trips_display_type', 'vertical' ),
		'button_type'            => get_option( 'wetravel_trips_button_type', 'book_now' ),
		'button_text'            => '',
		'button_color'           => get_option( 'wetravel_trips_button_color', '#33ae3f' ),
		'items_per_page'         => get_option( 'wetravel_trips_items_per_page', 10 ),
		'items_per_row'          => get_option( 'wetravel_trips_items_per_row', 3 ),
		'items_per_slide'        => get_option( 'wetravel_trips_items_per_slide', 3 ),
		'load_more_text'         => get_option( 'wetravel_trips_load_more_text', 'Load More' ),
		'trip_type'              => 'all',
		'date_start'             => '',
		'date_end'               => '',
		'search_visibility'      => get_option( 'wetravel_trips_search_visibility', false ),
	);

	// First, get the design if specified
	$design = null;
	if (!empty($original_atts['widget'])) {
		$designs = get_option('wetravel_trips_designs', array());
		$design_id = $original_atts['widget'];

		// First try to find design by keyword
		foreach ($designs as $id => $design_data) {
			if (isset($design_data['keyword']) && $design_data['keyword'] === $design_id) {
				$design = $design_data;
				break;
			}
		}

		// If not found by keyword, try to find by design ID
		if (null === $design && isset($designs[$design_id])) {
			$design = $designs[$design_id];
		}

		// If we found a design, update default attributes with design values
		if ($design) {
			if (!empty($design['displayType'])) {
				$default_atts['display_type'] = $design['displayType'];
			}
			if (!empty($design['buttonType'])) {
				$default_atts['button_type'] = $design['buttonType'];
			}
			if (!empty($design['buttonText'])) {
				$default_atts['button_text'] = $design['buttonText'];
			}
			if (!empty($design['buttonColor'])) {
				$default_atts['button_color'] = $design['buttonColor'];
			}
			if (!empty($design['tripType'])) {
				$default_atts['trip_type'] = $design['tripType'];
			}
			if (!empty($design['dateRangeStart'])) {
				$default_atts['date_start'] = $design['dateRangeStart'];
			}
			if (!empty($design['dateRangeEnd'])) {
				$default_atts['date_end'] = $design['dateRangeEnd'];
			}
			if (!empty($design['searchVisibility'])) {
				$default_atts['search_visibility'] = $design['searchVisibility'];
			}
		}
	}

	// Now merge with shortcode attributes, allowing them to override both defaults and design values
	$atts = shortcode_atts($default_atts, $original_atts, 'wetravel_trips');

	// Convert to block attributes format
	$block_atts = array(
		'slug'           => $atts['slug'],
		'env'            => $atts['env'],
		'wetravelUserID' => $atts['wetravel_trips_user_id'],
		'displayType'    => $atts['display_type'],
		'buttonType'     => $atts['button_type'],
		'buttonText'     => $atts['button_text'],
		'buttonColor'    => $atts['button_color'],
		'itemsPerPage'   => intval($atts['items_per_page']),
		'itemsPerRow'    => intval($atts['items_per_row']),
		'itemsPerSlide'  => intval($atts['items_per_slide']),
		'loadMoreText'   => $atts['load_more_text'],
		'tripType'       => $atts['trip_type'],
		'dateStart'      => $atts['date_start'],
		'dateEnd'        => $atts['date_end'],
		'searchVisibility' => $atts['search_visibility'],
	);

	// Add the selected design ID if a widget was specified
	if (!empty($original_atts['widget'])) {
		$block_atts['selectedDesignID'] = $original_atts['widget'];
	}

	// Use the existing block render function to maintain consistency
	if (function_exists('wtwidget_trips_block_render')) {
		return wtwidget_trips_block_render($block_atts);
	} else {
		// Fallback if block render function doesn't exist
		return wtwidget_render_trips_fallback($block_atts);
	}
}
add_shortcode( 'wetravel_trips', 'wtwidget_trips_shortcode' );

/**
 * Load trips data with AJAX for shortcode or block
 * This makes the shortcode behave the same as the block renderer
 */
function wtwidget_register_trips_ajax_handlers() {
	// Ensure that the AJAX handler from trips-loader.js works correctly.
	if ( ! function_exists( 'wtwidget_get_trips_ajax' ) ) {
		/**
		 * AJAX handler for fetching trips data.
		 *
		 * This function handles the AJAX request to fetch trips data based on the provided parameters.
		 * It checks the nonce, retrieves the parameters, formats the environment URL, builds the API URL,
		 * and retrieves the trips data.
		 */
		function wtwidget_get_trips_ajax() {
			// Security check.
			check_ajax_referer( 'wetravel_trips_nonce', 'nonce' );

			// Get parameters from the request.
			$slug       = isset( $_POST['slug'] ) ? sanitize_text_field( wp_unslash( $_POST['slug'] ) ) : '';
			$env        = isset( $_POST['env'] ) ? sanitize_text_field( wp_unslash( $_POST['env'] ) ) : 'https://pre.wetravel.to';
			$trip_type  = isset( $_POST['tripType'] ) ? sanitize_text_field( wp_unslash( $_POST['tripType'] ) ) : 'all';
			$date_start = isset( $_POST['dateStart'] ) ? sanitize_text_field( wp_unslash( $_POST['dateStart'] ) ) : '';
			$date_end   = isset( $_POST['dateEnd'] ) ? sanitize_text_field( wp_unslash( $_POST['dateEnd'] ) ) : '';

			// Build API URL with parameters
			$api_url = wtwidget_build_api_url($env, $slug, array(
				'trip_type' => $trip_type,
				'date_start' => $date_start,
				'date_end' => $date_end
			));

			// Get trips data
			$trips = wtwidget_get_trips_data($api_url);

			if ( 'recurring' === $trip_type ) {
				// Filter trips where 'all_year' is true.
				$trips = array_filter(
					$trips,
					function ( $trip ) {
						return ! empty( $trip['all_year'] ) && true === $trip['all_year'];
					}
				);
			}

			// Enhance trips with details since we need them for display
			if (!empty($trips)) {
				$trips = wtwidget_enhance_trips_with_details($trips, $env);
			}

			// Return the trips data as JSON.
			wp_send_json_success( $trips );
		}

		add_action( 'wp_ajax_wetravel_trips_get_trips', 'wtwidget_get_trips_ajax' );
		add_action( 'wp_ajax_nopriv_wetravel_trips_get_trips', 'wtwidget_get_trips_ajax' );
	}
}
add_action( 'init', 'wtwidget_register_trips_ajax_handlers' );
