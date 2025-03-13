<?php
/**
 * Backend API endpoint for fetching trips from WeTravel with detailed information
 */

// Register AJAX handlers
add_action('wp_ajax_fetch_wetravel_trips', 'fetch_wetravel_trips_handler');
add_action('wp_ajax_nopriv_fetch_wetravel_trips', 'fetch_wetravel_trips_handler');

/**
 * AJAX handler for fetching trips from WeTravel API
 */
function fetch_wetravel_trips_handler() {
    error_log('Fetching trips...');
    // Validate nonce for security
    if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'all_trips_nonce')) {
        wp_send_json_error('Invalid security token');
        return;
    }

    // Get required parameters
    $slug = isset($_GET['slug']) ? sanitize_text_field($_GET['slug']) : '';
    $env = isset($_GET['env']) ? sanitize_text_field($_GET['env']) : '';
    $trip_type = isset($_GET['trip_type']) ? sanitize_text_field($_GET['trip_type']) : 'all';
    $date_start = isset($_GET['date_start']) ? sanitize_text_field($_GET['date_start']) : '';
    $date_end = isset($_GET['date_end']) ? sanitize_text_field($_GET['date_end']) : '';

    // Validate required parameters
    if (empty($slug) || empty($env)) {
        wp_send_json_error('Missing required parameters');
        return;
    }

    // Clean up the environment URL if needed
    $env = rtrim($env, '/');

    // Build API endpoint
    $api_url = "{$env}/api/v2/embeds/all_trips?slug={$slug}";

    // Add query parameters based on trip type and date range
    if ($trip_type !== 'all') {
        $api_url .= "&type={$trip_type}";

        // Add date range for one-time trips
        if ($trip_type === 'one-time' && !empty($date_start) && !empty($date_end)) {
            $api_url .= "&startDate={$date_start}&endDate={$date_end}";
        }
    }

    // Get trips data with caching
    $trips = get_wetravel_trips_data($api_url, $env);

    if (empty($trips)) {
        wp_send_json_error('No trips found or error fetching trips');
        return;
    }

    error_log('Trips fetched successfully');
    error_log(print_r($trips, true));

    wp_send_json_success($trips);
}

/**
 * Get trips data from WeTravel API with caching
 *
 * @param string $api_url The API URL to fetch data from
 * @param string $env The environment URL base
 * @return array|false The trips data or false on error
 */
function get_wetravel_trips_data($api_url, $env = '') {
    // Try to get cached data first (1 minute cache)
    $cache_key = 'wetravel_trips_' . md5($api_url . '_details');
    $cached_data = get_transient($cache_key);

    if (false !== $cached_data) {
        return $cached_data;
    }

    // No cache, fetch from API
    $response = wp_remote_get($api_url, array(
        'timeout' => 15,
        'headers' => array(
            'Accept' => 'application/json'
        )
    ));

    if (is_wp_error($response)) {
        error_log('Error fetching trips: ' . $response->get_error_message());
        return false;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    // Check if we have valid data
    if (!isset($data['trips']) || !is_array($data['trips'])) {
        error_log('Invalid API response: ' . print_r($data, true));
        return false;
    }

    $trips = $data['trips'];

    // Enhance trips data with detailed information
    $trips = fetch_trip_details($trips, $env);

    // Cache for 1 minute (60 seconds)
    set_transient($cache_key, $trips, 60);

    return $trips;
}

/**
 * Fetch detailed information for each trip
 *
 * @param array $trips The basic trips data
 * @param string $env The environment URL base
 * @return array Enhanced trips data with details
 */
function fetch_trip_details($trips, $env) {
    $enhanced_trips = [];

    foreach ($trips as $trip) {
        // Skip if no UUID
        if (empty($trip['uuid'])) {
            $enhanced_trips[] = $trip;
            continue;
        }

        // Build detail endpoint URL
        $detail_url = "{$env}/api/v2/user/trips/{$trip['uuid']}/details";

        // Fetch trip details
        $response = wp_remote_get($detail_url, array(
            'timeout' => 15,
            'headers' => array(
                'Accept' => 'application/json'
            )
        ));

        if (is_wp_error($response)) {
            $enhanced_trips[] = $trip;
            continue;
        }

        $body = wp_remote_retrieve_body($response);
        $detail_data = json_decode($body, true);

        // Check if we have valid detailed data
        if (!isset($detail_data['data']) || !isset($detail_data['data']['trip'])) {
            $enhanced_trips[] = $trip;
            continue;
        }

        // Extract trip details and paragraphs
        $trip_details = $detail_data['data']['trip'];
        $paragraphs = isset($detail_data['data']['paragraphs']) ? $detail_data['data']['paragraphs'] : [];

        // Find specific paragraphs
        $full_description = '';
        $duration = '';

        foreach ($paragraphs as $paragraph) {
            if (isset($paragraph['title']) && $paragraph['title'] === 'About this trip' && isset($paragraph['text'])) {
                $full_description = $paragraph['text'];
            }

            if (isset($paragraph['title']) && $paragraph['title'] === 'Duration' && isset($paragraph['text'])) {
                $duration = strip_tags($paragraph['text']);
            }
        }

        // Enhance trip data with detailed information
        $trip['full_description'] = $full_description;
        $trip['custom_duration'] = $duration;

        // If banner image is available in details, use it
        if (!empty($trip_details['banner_img'])) {
            $trip['banner_image'] = $trip_details['banner_img'];
        }

        // If detailed price is available, use it
        if (isset($trip_details['price'])) {
            // Price might be in cents, convert to dollars for display
            $formatted_price = (float)$trip_details['price'] / 100;

            // If trip already has price, update it with the detailed format
            if (isset($trip['price']) && is_array($trip['price'])) {
                $trip['price']['amount'] = number_format($formatted_price, 2);
                $trip['price']['raw_amount'] = $formatted_price;
            } else {
                // Create a price object if it doesn't exist
                $trip['price'] = array(
                    'amount' => number_format($formatted_price, 2),
                    'raw_amount' => $formatted_price,
                    'currencySymbol' => isset($trip_details['currency']) ? get_currency_symbol($trip_details['currency']) : '$'
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
 * @param string $currency Currency code (e.g., USD, EUR)
 * @return string Currency symbol
 */
function get_currency_symbol($currency) {
    $symbols = array(
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'JPY' => '¥',
        'CAD' => 'C$',
        'AUD' => 'A$',
        'PEN' => 'S/.',
    );

    return isset($symbols[$currency]) ? $symbols[$currency] : '$';
}
