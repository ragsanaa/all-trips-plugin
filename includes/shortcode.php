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
		'locations'              => '', // Semicolon-separated list of locations to filter by
		'search_visibility'      => get_option( 'wetravel_trips_search_visibility', false ),
		'border_radius'          => get_option( 'wetravel_trips_border_radius', 6 ),
		'wt_widget_type'            => get_option( 'wetravel_trips_wt_widget_type', 'all-trips' ),
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
			if (!empty($design['wtWidgetType'])) {
				$default_atts['wt_widget_type'] = $design['wtWidgetType'];
			}

			// Fix: Use shortcode attributes as fallback before global options
			$default_atts['items_per_slide'] = isset($design['itemsPerSlide']) ? $design['itemsPerSlide'] : $default_atts['items_per_slide'];
			$default_atts['items_per_row'] = isset($design['itemsPerRow']) ? $design['itemsPerRow'] : $default_atts['items_per_row'];
			$default_atts['items_per_page'] = isset($design['itemsPerPage']) ? $design['itemsPerPage'] : $default_atts['items_per_page'];
			$default_atts['border_radius'] = isset($design['borderRadius']) ? $design['borderRadius'] : $default_atts['border_radius'];
		}
	}

	// Now merge with shortcode attributes, allowing them to override both defaults and design values
	$atts = shortcode_atts($default_atts, $original_atts, 'wetravel_trips');

	// Add validation for shortcode attributes
	$atts['items_per_page'] = max(1, min(50, intval($atts['items_per_page'])));
	$atts['items_per_row'] = max(1, min(4, intval($atts['items_per_row'])));
	$atts['items_per_slide'] = max(1, min(4, intval($atts['items_per_slide'])));
	$atts['display_type'] = in_array($atts['display_type'], ['vertical', 'grid', 'carousel']) ? $atts['display_type'] : 'vertical';
	$atts['button_type'] = in_array($atts['button_type'], ['book_now', 'trip_link']) ? $atts['button_type'] : 'book_now';
	$atts['button_color'] = sanitize_hex_color($atts['button_color']) ?: '#33ae3f';
	$atts['trip_type'] = in_array($atts['trip_type'], ['all', 'one-time', 'recurring']) ? $atts['trip_type'] : 'all';
	$atts['border_radius'] = max(0, min(100, intval($atts['border_radius'])));
	$atts['search_visibility'] = (bool) $atts['search_visibility'];

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
		'tripType'       => $atts['trip_type'],
		'dateStart'      => $atts['date_start'],
		'dateEnd'        => $atts['date_end'],
		'locations'      => $atts['locations'],
		'searchVisibility' => $atts['search_visibility'],
		'borderRadius'   => $atts['border_radius'],
		'integrationType' => 'shortcode',
		'wtWidgetType'     => $atts['wt_widget_type'],
	);

	// Add the selected design ID if a widget was specified
	if (!empty($original_atts['widget'])) {
		$block_atts['selectedDesignID'] = $original_atts['widget'];
	}

	// Use the existing block render function to maintain consistency
	if (function_exists('wtwidget_trips_block_render')) {
		return wtwidget_trips_block_render($block_atts);
	}
}
add_shortcode( 'wetravel_trips', 'wtwidget_trips_shortcode' );

