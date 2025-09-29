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

// Register REST API endpoints for hybrid SSR + hydration.
add_action( 'rest_api_init', 'wtwidget_register_rest_endpoints' );

// Note: Legacy AJAX handlers have been removed as they were unused.
// All frontend functionality now uses the REST API endpoint '/wp-json/wetravel/v1/trips'

/**
 * Get trips data from WeTravel API with caching (legacy function)
 *
 * Note: This function is kept for backward compatibility but the optimized
 * version wtwidget_get_fresh_trips_data() is now preferred for better performance.
 *
 * @param string $api_url The API URL to fetch data from.
 * @return array|false The trips data or false on error.
 */
function wtwidget_get_trips_data( $api_url ) {
	// For backward compatibility, just call the fresh data function
	// The caching is now handled at a higher level with enhanced data
	return wtwidget_get_fresh_trips_data( $api_url );
}

/**
 * Enhance trips with detailed information including SEO config
 *
 * @param array  $trips The basic trips data.
 * @param string $env The environment URL base.
 * @return array Enhanced trips data with details.
 */
function wtwidget_enhance_trips_with_details( $trips, $env ) {
	$enhanced_trips = array();

	foreach ( $trips as $trip ) {
		// Skip if no UUID.
		if ( empty( $trip['uuid'] ) ) {
			$enhanced_trips[] = $trip;
			continue;
		}

		// Build detail endpoint URL.
		$seo_config_url = "{$env}/api/v2/user/trips/{$trip['uuid']}/seo_config";

		// Try to get cached seo config first
		$cache_key = 'wetravel_trip_seo_' . $trip['uuid'];
		$seo_config_data = get_transient($cache_key);

		if (false === $seo_config_data) {
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

			$body = wp_remote_retrieve_body( $response );
			$seo_config_data = json_decode( $body, true );

			// Cache individual trip SEO data for 15 minutes (trip details rarely change)
			set_transient($cache_key, $seo_config_data, 900);
		}

		// Check if we have valid detailed data.
		if ( ! isset( $seo_config_data['data'] ) ) {
			$enhanced_trips[] = $trip;
			continue;
		}

		// Extract trip seo_configs.
		$trip_details = $seo_config_data['data'];

		// Find specific paragraphs.
		$full_description = isset( $trip_details['description'] ) ? $trip_details['description'] : array();

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
function wtwidget_get_currency_symbol($currency_code) {
	// Default currency symbols for common currencies
	$default_symbols = array(
		'USD' => '$',
		'EUR' => '€',
		'GBP' => '£',
		'JPY' => '¥',
		'AUD' => 'A$',
		'CAD' => 'C$',
		'CHF' => 'Fr',
		'CNY' => '¥',
		'INR' => '₹',
		'NZD' => 'NZ$'
	);

	// First try to get from default symbols
	if (isset($default_symbols[$currency_code])) {
		return $default_symbols[$currency_code];
	}

	// Then try to get from JSON file
	$currencies_file = __DIR__ . '/../assets/constant/currencies.json';
	if (file_exists($currencies_file)) {
		$currencies_json = file_get_contents($currencies_file);
		if ($currencies_json !== false) {
			$currencies = json_decode($currencies_json, true);
			if (isset($currencies[$currency_code])) {
				return $currencies[$currency_code];
			}
		}
	}

	// If all else fails, return the currency code itself
	return $currency_code;
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
    if (isset($params['trip_type'])) {
        if ('recurring' === $params['trip_type']) {
            $query_params['all_year'] = 1;
        } elseif ('one-time' === $params['trip_type']) {
            $query_params['all_year'] = 0;
        }
        // For 'all' trip type, no all_year parameter is set
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
        function($location) {
            return !empty($location) && is_string($location);
        }
    );

    // Get unique values and sort them
    $unique_locations = array_unique($locations);
    sort($unique_locations, SORT_STRING);

    return $unique_locations;
}

/**
 * Register REST API endpoints for hybrid SSR + hydration
 */
function wtwidget_register_rest_endpoints() {
    register_rest_route('wetravel/v1', '/trips', array(
        'methods' => 'GET',
        'callback' => 'wtwidget_rest_get_fresh_trips',
        'permission_callback' => '__return_true',
        'args' => array(
            'block_id' => array(
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'slug' => array(
                'required' => false,
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'env' => array(
                'required' => false,
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'trip_type' => array(
                'required' => false,
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'all',
            ),
            'date_start' => array(
                'required' => false,
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'date_end' => array(
                'required' => false,
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'locations' => array(
                'required' => false,
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'display_type' => array(
                'required' => false,
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'vertical',
            ),
            'button_type' => array(
                'required' => false,
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'book_now',
            ),
            'button_text' => array(
                'required' => false,
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'button_color' => array(
                'required' => false,
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '#33ae3f',
            ),
            'items_per_page' => array(
                'required' => false,
                'sanitize_callback' => 'absint',
                'default' => 10,
            ),
            'items_per_row' => array(
                'required' => false,
                'sanitize_callback' => 'absint',
                'default' => 3,
            ),
            'wetravel_user_id' => array(
                'required' => false,
                'sanitize_callback' => 'sanitize_text_field',
            ),
        ),
    ));
}

/**
 * REST API callback to get fresh trips data and return rendered HTML
 *
 * @param WP_REST_Request $request The REST request object.
 * @return WP_REST_Response|WP_Error The response.
 */
function wtwidget_rest_get_fresh_trips( $request ) {
    $block_id = $request->get_param('block_id');
    $slug = $request->get_param('slug') ?: get_option('wetravel_trips_slug', '');
    $env = $request->get_param('env') ?: get_option('wetravel_trips_env', 'https://pre.wetravel.to');
    $trip_type = $request->get_param('trip_type');
    $date_start = $request->get_param('date_start');
    $date_end = $request->get_param('date_end');
    $locations_param = $request->get_param('locations');
    $display_type = $request->get_param('display_type');
    $button_type = $request->get_param('button_type');
    $button_text = $request->get_param('button_text');
    $button_color = $request->get_param('button_color');
    $items_per_page = $request->get_param('items_per_page');
    $items_per_row = $request->get_param('items_per_row');
    $wetravel_user_id = $request->get_param('wetravel_user_id') ?: get_option('wetravel_trips_user_id', '');

    // Validate required parameters
    if (empty($slug) || empty($env)) {
        return new WP_Error(
            'missing_params',
            'Missing required parameters: slug and env are required',
            array('status' => 400)
        );
    }

    // Build API URL with parameters
    $api_url = wtwidget_build_api_url($env, $slug, array(
        'trip_type' => $trip_type,
        'date_start' => $date_start,
        'date_end' => $date_end
    ));

    // Get fresh trips data (bypassing cache by using a different function)
    $trips = wtwidget_get_fresh_trips_data($api_url);

    if (false === $trips) {
        return new WP_Error(
            'api_error',
            'Failed to fetch trips from WeTravel API',
            array('status' => 500)
        );
    }

    // Filter trips by location if locations are specified
    if (!empty($locations_param)) {
        $locations = array_map('trim', explode(';', $locations_param));
        $locations = array_filter($locations);

        if (!empty($locations)) {
            $trips = array_filter($trips, function($trip) use ($locations) {
                return !empty($trip['location']) && in_array($trip['location'], $locations);
            });
        }
    }

    // Filter by trip type
    if ('recurring' === $trip_type) {
        $trips = array_filter($trips, function($trip) {
            return !empty($trip['all_year']) && true === $trip['all_year'];
        });
    }

    // Enhance trips with detailed information
    $enhanced_trips = array();
    if (!empty($trips)) {
        $enhanced_trips = wtwidget_enhance_trips_with_details($trips, $env);
    }

    // Set default button text based on button type if not provided
    if (empty($button_text)) {
        $button_text = 'book_now' === $button_type ? 'Book Now' : 'View Trip';
    }

    // Render the trips HTML
    $html = wtwidget_render_trips_html($enhanced_trips, array(
        'block_id' => $block_id,
        'env' => $env,
        'wetravelUserID' => $wetravel_user_id,
        'displayType' => $display_type,
        'buttonType' => $button_type,
        'buttonText' => $button_text,
        'buttonColor' => $button_color,
        'itemsPerPage' => $items_per_page,
        'itemsPerRow' => $items_per_row,
    ));

    // Cache the fresh results for next server render using same key format as SSR
    $cache_key = 'wetravel_enhanced_' . md5($api_url . serialize($locations_param) . $trip_type);
    set_transient($cache_key, $enhanced_trips, 300); // Cache for 5 minutes

    return rest_ensure_response(array(
        'success' => true,
        'html' => $html,
        'trips_count' => count($enhanced_trips),
    ));
}

/**
 * Get fresh trips data without caching (for hydration)
 *
 * @param string $api_url The API URL to fetch data from.
 * @return array|false The trips data or false on error.
 */
function wtwidget_get_fresh_trips_data( $api_url ) {
    // Fetch from API without caching
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

    // Check if we have valid data
    if ( ! isset( $data['trips'] ) || ! is_array( $data['trips'] ) ) {
        return false;
    }

    return $data['trips'];
}

/**
 * Render trips HTML for hydration
 *
 * @param array $trips Enhanced trips data.
 * @param array $options Display options.
 * @return string Rendered HTML.
 */
function wtwidget_render_trips_html( $trips, $options ) {
    if ( empty( $trips ) ) {
        return '<div class="no-trips">No trips found</div>';
    }

    $html = '';
    $display_type = $options['displayType'];
    $items_per_page = $options['itemsPerPage'];

    $allowed_html_tags = array(
        'div' => array(
            'class' => true,
            'data-env' => true,
            'data-version' => true,
            'data-uid' => true,
            'data-uuid' => true,
            'href' => true,
            'style' => true,
        ),
        'img' => array(
            'src' => true,
            'alt' => true,
            'class' => true,
            'loading' => true,
            'decoding' => true,
            'width' => true,
            'height' => true,
        ),
        'h3' => array(),
        'p' => array(),
        'span' => array(
            'class' => true,
            'style' => true,
        ),
        'button' => array(
            'class' => true,
            'style' => true,
            'href' => true,
            'data-uuid' => true,
            'data-uid' => true,
            'data-env' => true,
            'data-version' => true,
        ),
        'a' => array(
            'class' => true,
            'style' => true,
            'href' => true,
            'target' => true,
        ),
    );

    if ( 'carousel' === $display_type ) {
        $html .= '<div class="wetravel-carousel-wrapper">';
        $html .= '<div class="swiper-container-wrapper">';
        $html .= '<div class="swiper-button-prev"></div>';
        $html .= '<div class="swiper">';
        $html .= '<div class="swiper-wrapper">';

        foreach ( $trips as $trip ) {
            $html .= '<div class="swiper-slide">';
            $html .= wp_kses( wtwidget_render_trip_item( $trip, $options ), $allowed_html_tags );
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '<div class="swiper-pagination"></div>';
        $html .= '</div>';
        $html .= '<div class="swiper-button-next"></div>';
        $html .= '</div>';
        $html .= '</div>';
    } else {
        $counter = 0;
        foreach ( $trips as $trip ) {
            $visibility_class = $counter < $items_per_page ? 'visible-item' : 'hidden-item';
            $html .= wp_kses( wtwidget_render_trip_item( $trip, $options, $visibility_class ), $allowed_html_tags );
            ++$counter;
        }
    }

    return $html;
}

