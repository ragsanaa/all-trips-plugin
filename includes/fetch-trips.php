<?php
/**
 * Backend API endpoint for fetching trips from WeTravel with detailed information.
 *
 * @package WordPress
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Register AJAX handlers.
add_action( 'wp_ajax_fetch_wetravel_trips', 'wtwidget_fetch_trips_handler' );
add_action( 'wp_ajax_nopriv_fetch_wetravel_trips', 'wtwidget_fetch_trips_handler' );

/**
 * AJAX handler for fetching trips from WeTravel API.
 */
function wtwidget_fetch_trips_handler() {
	// Validate nonce for security.
	if ( ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'wetravel_trips_nonce' ) ) {
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
		if ( ! empty( $date_start ) ) {
				$query_params['from_date'] = $date_start;
		}

		if ( ! empty( $date_end ) ) {
			$query_params['to_date']   = $date_end;
		}
	}

	// Build the final URL with parameters.
	$api_url = add_query_arg( $query_params, $api_url );

	// Get trips data with caching.
	$trips = wtwidget_get_trips_data( $api_url, $env );

	if ( 'recurring' === $trip_type ) {
		// Filter trips where 'all_year' is true.
		$trips = array_filter(
			$trips,
			function ( $trip ) {
				return ! empty( $trip['all_year'] ) && true === $trip['all_year'];
			}
		);
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
function wtwidget_get_trips_data( $api_url, $env = '' ) {
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
	$trips = wtwidget_fetch_trip_seo_config( $trips, $env );

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
function wtwidget_fetch_trip_seo_config( $trips, $env ) {
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

		$body            = wp_remote_retrieve_body( $response );
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
			$formatted_price = (int) $trip_details['price'];

			// If trip already has price, update it with the detailed format.
			if ( isset( $trip['price'] ) && is_array( $trip['price'] ) ) {
				$trip['price']['amount']     = number_format( $formatted_price, 2 );
				$trip['price']['raw_amount'] = $formatted_price;
			} else {
				// Create a price object if it doesn't exist.
				$trip['price'] = array(
					'amount'         => $formatted_price,
					'raw_amount'     => $formatted_price,
					'currencySymbol' => isset( $trip_details['currency'] ) ? wtwidget_get_currency_symbol( $trip_details['currency'] ) : '$',
				);
			}
		}

		$enhanced_trips[] = $trip;
	}

	return $enhanced_trips;
}

/**
 * Get currency symbol for a given currency code
 *
 * @param string $currency_code The currency code.
 * @return string The currency symbol.
 */
function wtwidget_get_currency_symbol( $currency_code ) {
	// Reference: https://github.com/bengourley/currency-symbol-map/blob/master/map.js.
	// use currencies json file to get the symbols.
	$currencies_file = __DIR__ . '/../assets/constant/currencies.json';
	$currencies_json = file_exists( $currencies_file ) ? file_get_contents( $currencies_file ) : '{}';
	$currencies      = json_decode( $currencies_json, true );

	return isset( $currencies[ $currency_code ] ) ? $currencies[ $currency_code ] : $currency_code;
}

/**
 * Build WeTravel API URL with parameters
 *
 * @param string $env Environment URL.
 * @param string $slug WeTravel slug.
 * @param array  $params Additional query parameters.
 * @return string Complete API URL.
 */
function wtwidget_build_api_url($env, $slug, $params = array()) {
    $api_url = rtrim($env, '/') . '/api/v2/embeds/all_trips';
    $query_params = array_merge(array('slug' => $slug), $params);

    // Format dates if they exist
    if (!empty($params['date_start'])) {
        $date_obj = date_create($params['date_start']);
        if ($date_obj) {
            $query_params['from_date'] = date_format($date_obj, 'Y-m-d');
        }
    }

    if (!empty($params['date_end'])) {
        $date_obj = date_create($params['date_end']);
        if ($date_obj) {
            $query_params['to_date'] = date_format($date_obj, 'Y-m-d');
        }
    }

    // Set recurring/one-time parameters
    if (isset($params['trip_type']) && 'one-time' === $params['trip_type']) {
        $query_params['all_year'] = 'false';
    }

    return add_query_arg($query_params, $api_url);
}

/**
 * Get unique trip locations from trips data
 *
 * @param array<int|string, mixed> $trips Array of trip data.
 * @return array<string> Array of unique locations.
 */
function wtwidget_get_trip_locations(array $trips): array {
    // Extract all locations using array_column and filter out empty ones
    $locations = array_filter(
        array_column($trips, 'location'),
        function(mixed $location): bool {
            return !empty($location) && is_string($location);
        }
    );

    // Get unique values and sort them
    $unique_locations = array_unique($locations);
    sort($unique_locations, SORT_STRING);

    return $unique_locations;
}

/**
 * Fetch trips data from WeTravel API
 *
 * @param string $env Environment URL.
 * @param string $slug WeTravel slug.
 * @param array  $params Additional query parameters.
 * @return array Array of trip data.
 */
function wtwidget_fetch_trips_data($env, $slug, $params = array()) {
    $api_url = wtwidget_build_api_url($env, $slug, $params);

    // Use the existing get_trips_data function
    $trips = wtwidget_get_trips_data($api_url, $env);

    // Return empty array if the result is false
    return $trips !== false ? $trips : array();
}
