<?php
/**
 * Backend API endpoint for fetching trips from WeTravel with detailed information.
 *
 * @package WordPress
 */

// Register AJAX handlers.
add_action( 'wp_ajax_fetch_wetravel_trips', 'fetch_wetravel_trips_handler' );
add_action( 'wp_ajax_nopriv_fetch_wetravel_trips', 'fetch_wetravel_trips_handler' );

/**
 * AJAX handler for fetching trips from WeTravel API.
 */
function fetch_wetravel_trips_handler() {
	// Validate nonce for security.
	if ( ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'all_trips_nonce' ) ) {
		wp_send_json_error( 'Invalid security token' );
		return;
	}

	// Get required parameters.
	$slug       = isset( $_GET['slug'] ) ? sanitize_text_field( wp_unslash( $_GET['slug'] ) ) : '';
	$env        = isset( $_GET['env'] ) ? sanitize_text_field( wp_unslash( $_GET['env'] ) ) : '';
	$trip_type  = isset( $_GET['trip_type'] ) ? sanitize_text_field( wp_unslash( $_GET['trip_type'] ) ) : 'all';
	$date_start = isset( $_GET['date_start'] ) ? sanitize_text_field( wp_unslash( $_GET['date_start'] ) ) : '';
	$date_end   = isset( $_GET['date_end'] ) ? sanitize_text_field( wp_unslash( $_GET['date_end'] ) ) : '';

	// Validate required parameters.
	if ( empty( $slug ) || empty( $env ) ) {
		wp_send_json_error( 'Missing required parameters' );
		return;
	}

	// Clean up the environment URL if needed.
	$env = rtrim( $env, '/' );

	// Build API endpoint.
	$api_url = "{$env}/api/v2/embeds/all_trips";

	// Add query parameters.
	$query_params = array(
		'slug' => $slug,
	);

	// Format dates if they exist (ensure YYYY-MM-DD format).
	if ( ! empty( $date_start ) ) {
		// Parse and reformat the date to ensure correct format.
		$date_obj = date_create( $date_start );
		if ( $date_obj ) {
				$date_start = date_format( $date_obj, 'Y-m-d' );
		}
	}

	if ( ! empty( $date_end ) ) {
			// Parse and reformat the date to ensure correct format.
			$date_obj = date_create( $date_end );
		if ( $date_obj ) {
				$date_end = date_format( $date_obj, 'Y-m-d' );
		}
	}

	// Set recurring/one-time parameters.
	if ( 'one-time' === $trip_type ) {
			$query_params['all_year'] = 'false';

			// Add date range for one-time trips.
		if ( ! empty( $date_start ) && ! empty( $date_end ) ) {
				$query_params['from_date'] = $date_start;
				$query_params['to_date']   = $date_end;
		}
	}

	// Build the final URL with parameters.
	$api_url = add_query_arg( $query_params, $api_url );

	// Get trips data with caching.
	$trips = get_wetravel_trips_data( $api_url, $env );

	if ( 'recurring' === $trip_type ) {
		// Filter trips where 'all_year' is true.
    $trips = array_filter( $trips, function( $trip ) {
			return ! empty( $trip['all_year'] ) && $trip['all_year'] === true;
		} );
	}

	if ( empty( $trips ) ) {
		wp_send_json_error( 'No trips found or error fetching trips' );
		return;
	}

	wp_send_json_success( $trips );
}

/**
 * Get trips data from WeTravel API with caching
 *
 * @param string $api_url The API URL to fetch data from.
 * @param string $env The environment URL base.
 * @return array|false The trips data or false on error.
 */
function get_wetravel_trips_data( $api_url, $env = '' ) {
	// Try to get cached data first (1 minute cache).
	$cache_key   = 'wetravel_trips_' . md5( $api_url . '_details' );
	$cached_data = get_transient( $cache_key );

	if ( false !== $cached_data ) {
		return $cached_data;
	}

	// No cache, fetch from API.
	$response = wp_remote_get(
		$api_url,
		array(
			'timeout' => 15,
			'headers' => array(
				'Accept' => 'application/json',
			),
		)
	);

	if ( is_wp_error( $response ) ) {
		return false;
	}

	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );

	// Check if we have valid data.
	if ( ! isset( $data['trips'] ) || ! is_array( $data['trips'] ) ) {
		return false;
	}

	$trips = $data['trips'];

	// Enhance trips data with detailed information.
	$trips = fetch_trip_seo_config( $trips, $env );

	// Cache for 1 minute (60 seconds).
	set_transient( $cache_key, $trips, 60 );

	return $trips;
}

/**
 * Fetch detailed information for each trip
 *
 * @param array  $trips The basic trips data.
 * @param string $env The environment URL base.
 * @return array Enhanced trips data with details.
 */
function fetch_trip_seo_config( $trips, $env ) {
	$enhanced_trips = array();

	foreach ( $trips as $trip ) {
		// Skip if no UUID.
		if ( empty( $trip['uuid'] ) ) {
			$enhanced_trips[] = $trip;
			continue;
		}

		// Build detail endpoint URL.
		$seo_config_url = "{$env}/api/v2/user/trips/{$trip['uuid']}/seo_config";

		// Fetch trip seo_configs.
		$response = wp_remote_get(
			$seo_config_url,
			array(
				'timeout' => 15,
				'headers' => array(
					'Accept' => 'application/json',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			$enhanced_trips[] = $trip;
			continue;
		}

		$body        = wp_remote_retrieve_body( $response );
		$seo_config_data = json_decode( $body, true );

		// Check if we have valid detailed data.
		if ( ! isset( $seo_config_data['data'] ) ) {
			$enhanced_trips[] = $trip;
			continue;
		}

		// Extract trip seo_configs.
		$trip_details = $seo_config_data['data'];

		// Find specific paragraphs.
		$full_description = isset( $seo_config_data['data']['description'] ) ? $seo_config_data['data']['description'] : array();

		// Enhance trip data with detailed information.
		$trip['full_description'] = $full_description;
		$trip['custom_duration']  = $trip['trip_length'] ?? '';

		// If banner image is available in details, use it.
		if ( ! empty( $trip_details['image'] ) ) {
			$trip['banner_image'] = $trip_details['image'];
		}

		// If detailed price is available, use it.
		if ( isset( $trip_details['price'] ) ) {
			// Price might be in cents, convert to dollars for display.
			$formatted_price = (float) $trip_details['price'];

			// If trip already has price, update it with the detailed format.
			if ( isset( $trip['price'] ) && is_array( $trip['price'] ) ) {
				$trip['price']['amount']     = number_format( $formatted_price, 2 );
				$trip['price']['raw_amount'] = $formatted_price;
			} else {
				// Create a price object if it doesn't exist.
				$trip['price'] = array(
					'amount'         => number_format( $formatted_price, 2 ),
					'raw_amount'     => $formatted_price,
					'currencySymbol' => isset( $trip_details['currency'] ) ? get_currency_symbol( $trip_details['currency'] ) : '$',
				);
			}
		}

		$enhanced_trips[] = $trip;
	}

	return $enhanced_trips;
}

/**
 * Helper function to get currency symbol from currency code
 *
 * @param string $currency Currency code (e.g., USD, EUR).
 * @return string Currency symbol
 */
function get_currency_symbol( $currency ) {
	$symbols = array(
		'USD' => '$',
		'EUR' => '€',
		'GBP' => '£',
		'JPY' => '¥',
		'CAD' => 'C$',
		'AUD' => 'A$',
		'PEN' => 'S/.',
	);

	return isset( $symbols[ $currency ] ) ? $symbols[ $currency ] : '$';
}
