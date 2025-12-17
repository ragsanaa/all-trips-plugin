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
 * @return array|false Array with 'trips' and 'pagination' keys, or false on error.
 */
function wtwidget_get_trips_data( $api_url ) {
	// For backward compatibility, just call the fresh data function
	// The caching is now handled at a higher level with enhanced data
	return wtwidget_get_fresh_trips_data( $api_url );
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
 * @param string $wetravel_user_id WeTravel user ID.
 * @param array  $params Additional query parameters.
 * @return string Complete API URL.
 */
function wtwidget_build_api_url($env, $wetravel_user_id, $params = array()) {
    // New API endpoint: v1/trips/{wetravel_user_id}/public
    $api_url = rtrim($env, '/') . '/v1/trips/' . $wetravel_user_id . '/public';
    $query_params = array();

    // Category filter (upcoming or past) - optional, no default
    if (!empty($params['category'])) {
        $query_params['category'] = $params['category'];
    }

    // Date range filters (from_date and to_date)
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

    // Recurring filter (true/false)
    if (isset($params['trip_type'])) {
        if ('recurring' === $params['trip_type']) {
            $query_params['recurring'] = 'true';
        } elseif ('one-time' === $params['trip_type']) {
            $query_params['recurring'] = 'false';
        }
        // For 'all' trip type, no recurring parameter is set
    }

    // Location filter (array of strings, case-insensitive, matches from start)
    if (!empty($params['locations']) && is_array($params['locations'])) {
        // API expects location[] array format
        foreach ($params['locations'] as $index => $location) {
            $query_params['location[' . $index . ']'] = $location;
        }
    }

    // Pagination parameters
    if (!empty($params['page']) && is_numeric($params['page'])) {
        $query_params['page'] = intval($params['page']);
    }

    if (!empty($params['per_page']) && is_numeric($params['per_page'])) {
        $query_params['per_page'] = intval($params['per_page']);
    }

    if (!empty($query_params)) {
        return add_query_arg($query_params, $api_url);
    }

    return $api_url;
}

/**
 * Get unique trip locations from trips data
 *
 * @param array<int|string, mixed> $trips Array of trip data.
 * @return array<string> Array of unique locations.
 */
function wtwidget_get_trip_locations(array $trips): array {
    // Extract all locations from destination.title and filter out empty ones
    $locations = array();
    foreach ($trips as $trip) {
        if (!empty($trip['destination']['title'])) {
            $locations[] = $trip['destination']['title'];
        }
    }

    // Get unique values and sort them
    $unique_locations = array_unique($locations);
    sort($unique_locations, SORT_STRING);

    return $unique_locations;
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

    $min_price = min($prices);
    $currency_code = $trip['currency'] ?? 'USD';

    return array(
        'amount'         => number_format($min_price, 2),
        'raw_amount'     => $min_price,
        'currencySymbol' => wtwidget_get_currency_symbol($currency_code),
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
            'category' => array(
                'required' => false,
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '', // No default - comes from admin settings
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
    $category = $request->get_param('category');
    $trip_type = $request->get_param('trip_type');
    $date_start = $request->get_param('date_start');
    $date_end = $request->get_param('date_end');
    $locations_param = $request->get_param('locations');
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

    // Build API URL with all parameters including pagination
    $api_url = wtwidget_build_api_url($env, $wetravel_user_id, array(
        'category'   => $category,
        'trip_type'  => $trip_type,
        'date_start' => $date_start,
        'date_end'   => $date_end,
        'locations'  => $locations_array,
        'page'       => $page,
        'per_page'   => $per_page,
    ));

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
    set_transient($cache_key, $api_response, 300); // Cache for 5 minutes

    return rest_ensure_response(array(
        'success' => true,
        'html' => $html,
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

    // Check if we have valid data - new API uses 'data' array
    if ( ! isset( $data['data'] ) || ! is_array( $data['data'] ) ) {
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

