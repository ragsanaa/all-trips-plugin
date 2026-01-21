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

// All frontend functionality now uses the REST API endpoint '/wp-json/wetravel/v1/trips'

/**
 * Get trips data from WeTravel API with caching (legacy function)
 *
 * Note: This function is kept for backward compatibility but the optimized
 * version wtwidget_get_fresh_trips_data() is now preferred for better performance.
 *
 * @param string $api_url The API URL to fetch data from.
 * @return array|false Array with 'trips' and 'pagination' keys, or false on error.
 */
function wtwidget_get_trips_data( $api_url ) {
	// For backward compatibility, just call the fresh data function
	// The caching is now handled at a higher level with enhanced data
	return wtwidget_get_fresh_trips_data( $api_url );
}


/**
 * Get currency configuration including symbol and exponent (decimal places)
 *
 * @param string $currency_code The currency code.
 * @return array Configuration with 'symbol', 'exponent', 'format', and 'enabled' keys.
 */
function wtwidget_get_currency_config($currency_code) {
	// Default configuration
	$default_config = array(
		'currency' => $currency_code,
		'symbol'   => $currency_code,
		'exponent' => 2,
		'format'   => '%s%a',
		'enabled'  => true,
	);

	// Get configuration from JSON file
	$currencies_file = __DIR__ . '/../assets/constant/currencies.json';

	if (file_exists($currencies_file)) {
		$currencies_json = file_get_contents($currencies_file);
		if ($currencies_json !== false) {
			$currencies = json_decode($currencies_json, true);
			if (isset($currencies[$currency_code]) && is_array($currencies[$currency_code])) {
				// Return the currency config from JSON
				return $currencies[$currency_code];
			}
		}
	}

	// If not found in JSON, return default config
	return $default_config;
}

/**
 * Convert price from cents to decimal value based on currency exponent
 * Similar to fromCents function in TypeScript
 *
 * @param int|float $value_in_cents The value in cents (smallest currency unit).
 * @param string $currency_code The currency code.
 * @return float The converted decimal value.
 */
function wtwidget_from_cents($value_in_cents, $currency_code) {
	if (empty($value_in_cents) || empty($currency_code)) {
		return 0;
	}

	$config = wtwidget_get_currency_config($currency_code);
	$exponent = $config['exponent'];

	// Convert from cents: divide by 10^exponent
	$factor = pow(10, $exponent);
	return $value_in_cents / $factor;
}

/**
 * Format price from cents with proper decimal places based on currency
 * Similar to formatPrice function in TypeScript
 *
 * @param int|float $value_in_cents The value in cents (smallest currency unit).
 * @param string $currency_code The currency code.
 * @param bool $show_cents Whether to show decimal places.
 * @return string Formatted price string.
 */
function wtwidget_format_price($value_in_cents, $currency_code, $show_cents = false) {
	// Do not format if both rawAmount and currency are not provided
	if (empty($value_in_cents) && empty($currency_code)) {
		return '';
	}

	if (empty($currency_code)) {
		return '';
	}

	if (empty($value_in_cents) && $value_in_cents !== 0) {
		return '';
	}

	$config = wtwidget_get_currency_config($currency_code);
	$exponent = $config['exponent'];
	$symbol = $config['symbol'];
	$format = $config['format'] ?? '%s%a';

	$is_negative = $value_in_cents < 0;
	$absolute_value = abs($value_in_cents);

	// Convert from cents
	$factor = pow(10, $exponent);
	$amount = $absolute_value / $factor;

	// Round based on whether we show cents
	if (!$show_cents) {
		$amount = floor($amount);
	}

	// Format with appropriate decimal places
	$decimal_places = $show_cents ? $exponent : 0;
	$formatted_amount = number_format($amount, $decimal_places, '.', ',');

	// Remove trailing zeros if all decimal places are zero (.00, .000, etc.)
	if ($show_cents && $exponent > 0) {
		// Check if all decimal places are zeros
		$parts = explode('.', $formatted_amount);
		if (count($parts) === 2 && preg_match('/^0+$/', $parts[1])) {
			// All decimals are zero, remove them
			$formatted_amount = $parts[0];
		}
	}

	// Replace %s with currency symbol and %a with formatted amount
	$final_format = str_replace('%s', $symbol, $format);
	$final_format = str_replace('%a', $formatted_amount, $final_format);

	// Return formatted price with negative sign if needed
	return ($is_negative ? '-' : '') . $final_format;
}

/**
 * Convert trip_type to recurring format for API
 *
 * @param string $trip_type The trip type value.
 * @return string|null The recurring value ('true', 'false', or null for all).
 */
function wtwidget_convert_trip_type_to_recurring($trip_type) {
    if (empty($trip_type) || $trip_type === '') {
        return null; // No filter - show all trips
    }

    switch ($trip_type) {
        case 'recurring':
            return 'true';
        case 'one-time':
            return 'false';
        case 'all':
        default:
            return null; // Show all trips
    }
}

/**
 * Build WeTravel API URL with parameters
 *
 * @param string $env Environment URL.
 * @param string $wetravel_user_id WeTravel user ID.
 * @param array  $params Additional query parameters.
 * @return string Complete API URL.
 */
function wtwidget_build_api_url($env, $wetravel_user_id, $params = array()) {
    // New API endpoint: v1/trips/{wetravel_user_id}/public
    $api_url = rtrim($env, '/') . '/v1/trips/' . $wetravel_user_id . '/public';
    $query_params = array();

    // Visibility filter (new parameter)
    // Options: 'public', 'private' (default: 'public')
    if (!empty($params['visibility'])) {
        $query_params['visibility'] = $params['visibility'];
    }

    // Departure date range filter (nested object: gte, lte)
    if (!empty($params['departure_date'])) {
        if (!empty($params['departure_date']['gte'])) {
            $date_obj = date_create($params['departure_date']['gte']);
            if ($date_obj) {
                $query_params['departure_date[gte]'] = date_format($date_obj, 'Y-m-d');
            }
        }
        if (!empty($params['departure_date']['lte'])) {
            $date_obj = date_create($params['departure_date']['lte']);
            if ($date_obj) {
                $query_params['departure_date[lte]'] = date_format($date_obj, 'Y-m-d');
            }
        }
    }

    // Trip type filter (transform to recurring boolean for API)
    if (isset($params['trip_type']) && $params['trip_type'] !== '') {
        $recurring_value = wtwidget_convert_trip_type_to_recurring($params['trip_type']);
        if ($recurring_value !== null) {
            $query_params['recurring'] = $recurring_value;
        }
    }

    // Locations filter (transform to destinations for API)
    // Note: We'll handle locations separately to use [] notation without indices
    $locations_array = array();
    if (!empty($params['locations']) && is_array($params['locations'])) {
        $locations_array = $params['locations'];
    }

    // Search/query parameter for searching by title
    if (!empty($params['query'])) {
        $query_params['query'] = urlencode($params['query']);
    }

    // Sorting parameters (new)
    // sort_by: 'departure_date', 'date_created', 'title'
    if (!empty($params['sort_by'])) {
        $query_params['sort_by'] = $params['sort_by'];
    }

    // sort_order: 'asc', 'desc'
    if (!empty($params['sort_order'])) {
        $query_params['sort_order'] = $params['sort_order'];
    }

    // Pagination parameters
    if (!empty($params['page']) && is_numeric($params['page'])) {
        $query_params['page'] = intval($params['page']);
    }

    if (!empty($params['per_page']) && is_numeric($params['per_page'])) {
        $query_params['per_page'] = intval($params['per_page']);
    }

    // Build base URL with non-array parameters
    if (!empty($query_params)) {
        $api_url = add_query_arg($query_params, $api_url);
    }

    // Manually append locations as destinations array parameters with [] notation (no indices)
    if (!empty($locations_array)) {
        $separator = strpos($api_url, '?') !== false ? '&' : '?';
        foreach ($locations_array as $location) {
            $api_url .= $separator . 'destinations[]=' . rawurlencode($location);
            $separator = '&';
        }
    }

    return $api_url;
}

/**
 * Helper function to get trip image URL from images array
 *
 * @param array $trip Trip data from API.
 * @return string Image URL or empty string.
 */
function wtwidget_get_trip_image($trip) {
    if (!empty($trip['images']) && is_array($trip['images']) && !empty($trip['images'][0]['url'])) {
        return $trip['images'][0]['url'];
    }
    return '';
}

/**
 * Helper function to get trip location from destination object
 *
 * @param array $trip Trip data from API.
 * @return string Location title or empty string.
 */
function wtwidget_get_trip_location($trip) {
    if (!empty($trip['destination']['title'])) {
        return $trip['destination']['title'];
    }
    return '';
}

/**
 * Helper function to get minimum price from trip_options
 *
 * @param array $trip Trip data from API.
 * @return array|null Price array with amount, raw_amount, currencySymbol or null.
 */
function wtwidget_get_trip_price($trip) {
    if (empty($trip['trip_options']) || !is_array($trip['trip_options'])) {
        return null;
    }

    $prices = array_column($trip['trip_options'], 'price');
    $prices = array_filter($prices, function($p) {
        return is_numeric($p) && $p > 0;
    });

    if (empty($prices)) {
        return null;
    }

    // Get minimum price in cents
    $min_price_cents = min($prices);
    $currency_code = $trip['currency'] ?? 'USD';

    // Get currency configuration to determine decimal places
    $config = wtwidget_get_currency_config($currency_code);
    $exponent = $config['exponent'];

    // Convert from cents to decimal value
    $min_price_decimal = wtwidget_from_cents($min_price_cents, $currency_code);

    // Format the amount with appropriate decimal places
    // For zero-decimal currencies (like JPY), don't show decimals
    // For others, show the appropriate number of decimal places
    $formatted_amount = wtwidget_format_price($min_price_cents, $currency_code);

    return array(
        'amount'         => $formatted_amount,
        'raw_amount'     => $min_price_cents,          // Keep original cents value
        'raw_decimal'    => $min_price_decimal,        // Decimal value for calculations
        'currencySymbol' => $config['symbol'],
        'currency'       => $currency_code,
        'exponent'       => $exponent,
    );
}

/**
 * Helper function to format trip dates for display
 *
 * @param array $trip Trip data from API.
 * @return string Formatted date string.
 */
function wtwidget_get_trip_dates($trip) {
    // For one-time trips: start_date and end_date
    if (!empty($trip['start_date']) && !empty($trip['end_date'])) {
        $start = date_create($trip['start_date']);
        $end = date_create($trip['end_date']);
        if ($start && $end) {
            return date_format($start, 'M j') . ' - ' . date_format($end, 'M j, Y');
        }
    }

    // For recurring trips: next_departure_date
    if (!empty($trip['next_departure_date'])) {
        $next = date_create($trip['next_departure_date']);
        if ($next) {
            return 'Next: ' . date_format($next, 'M j, Y');
        }
    }

    return '';
}

/**
 * Helper function to check if trip is recurring
 *
 * @param array $trip Trip data from API.
 * @return bool True if recurring, false otherwise.
 */
function wtwidget_is_trip_recurring($trip) {
    return isset($trip['recurring']) && (bool) $trip['recurring'];
}

/**
 * Helper function to get trip duration/length
 *
 * @param array $trip Trip data from API.
 * @return string|int Trip length or empty string.
 */
function wtwidget_get_trip_length($trip) {
    return $trip['length'] ?? '';
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
            'wetravel_user_id' => array(
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'env' => array(
                'required' => false,
                'sanitize_callback' => 'sanitize_text_field',
            ),
            // New filtering parameters
            'visibility' => array(
                'required' => false,
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'public',
            ),
            'date_start' => array(
                'required' => false,
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'date_end' => array(
                'required' => false,
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'trip_type' => array(
                'required' => false,
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'locations' => array(
                'required' => false,
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'query' => array(
                'required' => false,
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'sort_by' => array(
                'required' => false,
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'departure_date',
            ),
            'sort_order' => array(
                'required' => false,
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'asc',
            ),
            // Display parameters
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
            'page' => array(
                'required' => false,
                'sanitize_callback' => 'absint',
                'default' => 1,
            ),
            'per_page' => array(
                'required' => false,
                'sanitize_callback' => 'absint',
                'default' => 25,
            )
        ),
    ));

    // Register destinations search endpoint
    register_rest_route('wetravel/v1', '/destinations/search', array(
        'methods' => 'GET',
        'callback' => 'wtwidget_rest_search_destinations',
        'permission_callback' => '__return_true',
        'args' => array(
            'query' => array(
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function($param) {
                    return strlen($param) >= 3 && strlen($param) <= 1000;
                }
            ),
            'organizer_id' => array(
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field',
            ),
            // Optional filters (mirrors trip filtering options)
            'visibility' => array(
                'required' => false,
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'public',
            ),
            'date_start' => array(
                'required' => false,
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'date_end' => array(
                'required' => false,
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'trip_type' => array(
                'required' => false,
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'destinations' => array(
                'required' => false,
                'sanitize_callback' => 'sanitize_text_field',
                'description' => 'Semicolon-separated list of destinations to filter results by (from design)',
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
    $wetravel_user_id = $request->get_param('wetravel_user_id') ?: get_option('wetravel_trips_user_id', '');
    $env = $request->get_param('env') ?: get_option('wetravel_trips_env', 'https://pre.wetravel.to');

    // New filtering parameters
    $visibility = $request->get_param('visibility');
    $date_start = $request->get_param('date_start');
    $date_end = $request->get_param('date_end');
    $trip_type = $request->get_param('trip_type');
    $locations_param = $request->get_param('locations');
    $search_query = $request->get_param('query');
    $sort_by = $request->get_param('sort_by');
    $sort_order = $request->get_param('sort_order');

    // Display parameters
    $display_type = $request->get_param('display_type');
    $button_type = $request->get_param('button_type');
    $button_text = $request->get_param('button_text');
    $button_color = $request->get_param('button_color');
    $page = $request->get_param('page');
    $per_page = $request->get_param('per_page');
    $items_per_row = $request->get_param('items_per_row');

    // Validate required parameters
    if (empty($wetravel_user_id) || empty($env)) {
        return new WP_Error(
            'missing_params',
            'Missing required parameters: wetravel_user_id and env are required',
            array('status' => 400)
        );
    }

    // Parse locations from semicolon-separated string to array
    $locations_array = array();
    if (!empty($locations_param)) {
        $locations_array = array_filter(array_map('trim', explode(';', $locations_param)));
    }

    // Build departure_date object for API (transform dateStart -> gte, dateEnd -> lte)
    $departure_date = array();
    if (!empty($date_start)) {
        $departure_date['gte'] = $date_start;
    }
    if (!empty($date_end)) {
        $departure_date['lte'] = $date_end;
    }

    // Build API URL with all parameters including pagination
    $api_params = array(
        'page'       => $page,
        'per_page'   => $per_page,
    );

    // Add new filtering parameters
    if (!empty($visibility)) {
        $api_params['visibility'] = $visibility;
    }
    if (!empty($departure_date)) {
        $api_params['departure_date'] = $departure_date;
    }
    // Transform trip_type to recurring for API
    if (isset($trip_type) && $trip_type !== '') {
        $api_params['trip_type'] = $trip_type;
    }
    // Transform locations to destinations for API
    if (!empty($locations_array)) {
        $api_params['locations'] = $locations_array;
    }
    // Add search query for title search
    if (!empty($search_query)) {
        $api_params['query'] = $search_query;
    }
    if (!empty($sort_by)) {
        $api_params['sort_by'] = $sort_by;
    }
    if (!empty($sort_order)) {
        $api_params['sort_order'] = $sort_order;
    }

    $api_url = wtwidget_build_api_url($env, $wetravel_user_id, $api_params);

    // Get fresh trips data with pagination
    $api_response = wtwidget_get_fresh_trips_data($api_url);

    if (false === $api_response) {
        return new WP_Error(
            'api_error',
            'Failed to fetch trips from WeTravel API',
            array('status' => 500)
        );
    }

    // Extract trips and pagination from response
    $trips = $api_response['trips'];
    $pagination = $api_response['pagination'];

    // Set default button text based on button type if not provided
    if (empty($button_text)) {
        $button_text = 'book_now' === $button_type ? 'Book Now' : 'View Trip';
    }

    // Render the trips HTML (all items visible since API handles pagination)
    $html = wtwidget_render_trips_html($trips, array(
        'block_id' => $block_id,
        'env' => $env,
        'wetravelUserID' => $wetravel_user_id,
        'displayType' => $display_type,
        'buttonType' => $button_type,
        'buttonText' => $button_text,
        'buttonColor' => $button_color,
        'itemsPerPage' => $per_page,
        'itemsPerRow' => $items_per_row,
        'serverPagination' => true,
    ));

    // Cache the fresh results for next server render
    $cache_key = 'wetravel_trips_' . md5($api_url);
    $cache_duration = defined( 'WETRAVEL_CACHE_DURATION' ) ? WETRAVEL_CACHE_DURATION : ( 5 * MINUTE_IN_SECONDS );
    set_transient($cache_key, $api_response, $cache_duration);

    // For carousel display, also return rendered HTML for each trip for progressive loading
    $structured_trips = array();
    if ($display_type === 'carousel') {
        foreach ($trips as $trip) {
            // Render each trip using the same PHP function to ensure consistent styling
            $trip_html = wtwidget_render_trip_item($trip, array(
                'env' => $env,
                'wetravelUserID' => $wetravel_user_id,
                'displayType' => $display_type,
                'buttonType' => $button_type,
                'buttonText' => $button_text,
                'buttonColor' => $button_color,
            ));

            $structured_trips[] = array(
                'id' => $trip['id'] ?? $trip['uuid'] ?? '',
                'html' => $trip_html, // Fully rendered HTML
            );
        }
    }

    return rest_ensure_response(array(
        'success' => true,
        'html' => $html,
        'trips' => $structured_trips, // Include structured data for carousel progressive loading
        'trips_count' => count($trips),
        'pagination' => $pagination,
    ));
}

/**
 * Get fresh trips data without caching (for hydration)
 *
 * @param string $api_url The API URL to fetch data from.
 * @return array|false Array with 'trips' and 'pagination' keys, or false on error.
 */
function wtwidget_get_fresh_trips_data( $api_url ) {
    // Fetch from API without caching
    $response = wp_remote_get(
        $api_url,
        array(
            'timeout' => defined( 'WETRAVEL_API_TIMEOUT' ) ? WETRAVEL_API_TIMEOUT : 15,
            'headers' => array(
                'Accept' => 'application/json',
            ),
        )
    );

    if ( is_wp_error( $response ) ) {
        if ( function_exists( 'wtwidget_log_error' ) ) {
            wtwidget_log_error(
                'API request failed',
                array(
                    'error' => $response->get_error_message(),
                    'url'   => $api_url,
                )
            );
        }
        return false;
    }

    $response_code = wp_remote_retrieve_response_code( $response );
    if ( $response_code !== 200 ) {
        if ( function_exists( 'wtwidget_log_error' ) ) {
            wtwidget_log_error(
                'API returned non-200 status code',
                array(
                    'status_code' => $response_code,
                    'url'         => $api_url,
                )
            );
        }
        return false;
    }

    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );

    // Check if we have valid data - new API uses 'data' array
    if ( ! isset( $data['data'] ) || ! is_array( $data['data'] ) ) {
        if ( function_exists( 'wtwidget_log_error' ) ) {
            wtwidget_log_error(
                'API returned invalid data structure',
                array(
                    'url'      => $api_url,
                    'has_data' => isset( $data['data'] ),
                    'is_array' => isset( $data['data'] ) ? is_array( $data['data'] ) : false,
                )
            );
        }
        return false;
    }

    // Return trips data with pagination
    return array(
        'trips'      => $data['data'],
        'pagination' => $data['pagination'],
    );
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
    $items_per_page = $options['itemsPerPage'] ?? 10;
    $server_pagination = $options['serverPagination'] ?? false;

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
            // With server-side pagination, all items are visible (API already paginated)
            // Only use visibility classes for client-side pagination fallback
            $visibility_class = $server_pagination ? 'visible-item' : ($counter < $items_per_page ? 'visible-item' : 'hidden-item');
            $html .= wp_kses( wtwidget_render_trip_item( $trip, $options, $visibility_class ), $allowed_html_tags );
            ++$counter;
        }
    }

    return $html;
}

/**
 * REST API callback to search destinations
 *
 * @param WP_REST_Request $request The REST request object.
 * @return WP_REST_Response|WP_Error The response.
 */
function wtwidget_rest_search_destinations( $request ) {
    $query = $request->get_param('query');
    $organizer_id = $request->get_param('organizer_id');
    $env = get_option('wetravel_trips_env', 'https://pre.wetravel.to');

    // Optional filter parameters
    $visibility = $request->get_param('visibility');
    $date_start = $request->get_param('date_start');
    $date_end = $request->get_param('date_end');
    $trip_type = $request->get_param('trip_type');
    $destinations = $request->get_param('destinations');

    // Validate required parameters
    if (empty($query) || empty($organizer_id)) {
        return new WP_Error(
            'missing_params',
            'Missing required parameters: query and organizer_id are required',
            array('status' => 400)
        );
    }

    // Validate query length (3-1000 characters as per spec)
    if (strlen($query) < 3 || strlen($query) > 1000) {
        return new WP_Error(
            'invalid_query',
            'Query must be between 3 and 1000 characters',
            array('status' => 400)
        );
    }

    // Build the WeTravel API URL for destinations search
    $api_url = rtrim($env, '/') . '/v1/trips/destinations/search';
    $query_params = array(
        'query' => $query,
        'organizer_id' => $organizer_id,
    );

    // Add optional filter parameters
    if (!empty($visibility)) {
        $query_params['visibility'] = $visibility;
    }

    // Build departure_date nested parameters (transform dateStart -> gte, dateEnd -> lte)
    if (!empty($date_start)) {
        $query_params['departure_date[gte]'] = $date_start;
    }
    if (!empty($date_end)) {
        $query_params['departure_date[lte]'] = $date_end;
    }

    // Transform trip_type to recurring for API
    if (isset($trip_type) && $trip_type !== '') {
        $recurring_value = wtwidget_convert_trip_type_to_recurring($trip_type);
        if ($recurring_value !== null) {
            $query_params['recurring'] = $recurring_value;
        }
    }

    // Build full API URL with query parameters
    $full_api_url = add_query_arg($query_params, $api_url);

    // Check cache first
    $cache_key = 'wetravel_destinations_' . md5($full_api_url);
    $cached_destinations = get_transient($cache_key);

    if (false !== $cached_destinations) {
        return rest_ensure_response($cached_destinations);
    }

    // Fetch from WeTravel API
    $response = wp_remote_get(
        $full_api_url,
        array(
            'timeout' => defined( 'WETRAVEL_API_TIMEOUT' ) ? WETRAVEL_API_TIMEOUT : 15,
            'headers' => array(
                'Accept' => 'application/json',
            ),
        )
    );

    if ( is_wp_error( $response ) ) {
        if ( function_exists( 'wtwidget_log_error' ) ) {
            wtwidget_log_error(
                'Destinations API request failed',
                array(
                    'error' => $response->get_error_message(),
                    'url'   => $full_api_url,
                )
            );
        }
        return new WP_Error(
            'api_error',
            'Failed to fetch destinations from WeTravel API: ' . $response->get_error_message(),
            array('status' => 500)
        );
    }

    $response_code = wp_remote_retrieve_response_code( $response );
    if ( $response_code !== 200 ) {
        if ( function_exists( 'wtwidget_log_error' ) ) {
            wtwidget_log_error(
                'Destinations API returned non-200 status code',
                array(
                    'status_code' => $response_code,
                    'url'         => $full_api_url,
                )
            );
        }
        return new WP_Error(
            'api_error',
            'WeTravel API returned status code: ' . $response_code,
            array('status' => $response_code)
        );
    }

    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );

    // Validate response structure - expecting array of destination strings
    if ( ! is_array( $data ) ) {
        if ( function_exists( 'wtwidget_log_error' ) ) {
            wtwidget_log_error(
                'Destinations API returned invalid data structure',
                array(
                    'url'      => $full_api_url,
                    'response' => $body,
                )
            );
        }
        return new WP_Error(
            'api_error',
            'Invalid response structure from WeTravel API',
            array('status' => 500)
        );
    }

    // Filter destinations if design locations are provided
    if (!empty($destinations)) {
        $allowed_destinations = array_map('trim', explode(';', $destinations));
        $allowed_destinations = array_filter($allowed_destinations); // Remove empty entries

        // Filter API results to only include destinations that match the design locations
        // Use case-insensitive matching
        if (!empty($allowed_destinations)) {
            $data = array_filter($data, function($destination) use ($allowed_destinations) {
                foreach ($allowed_destinations as $allowed) {
                    if (stripos($destination, $allowed) !== false || stripos($allowed, $destination) !== false) {
                        return true;
                    }
                }
                return false;
            });
            // Re-index array after filtering
            $data = array_values($data);
        }
    }

    // Prepare response in consistent format
    $response_data = array(
        'destinations' => $data,
        'count' => count($data)
    );

    // Cache the results (5 minutes)
    $cache_duration = defined( 'WETRAVEL_CACHE_DURATION' ) ? WETRAVEL_CACHE_DURATION : ( 5 * MINUTE_IN_SECONDS );
    set_transient($cache_key, $response_data, $cache_duration);

    return rest_ensure_response($response_data);
}

