<?php
/**
 * WeTravel Widgets Tracking System
 *
 * Tracks widget loads, clicks, and button clicks with GDPR compliance
 *
 * @package WordPress
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WetravelTracking {

	/**
	 * Instance of this class
	 */
	private static $instance = null;

	/**
	 * Constructor
	 */
	private function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_tracking_scripts' ) );
		add_action( 'wp_ajax_wetravel_track_event', array( $this, 'handle_track_event' ) );
		add_action( 'wp_ajax_nopriv_wetravel_track_event', array( $this, 'handle_track_event' ) );

	}

	/**
	 * Get instance
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Check if tracking is enabled based on consent
	 */
	public function is_tracking_enabled() {
		return (bool) get_option( 'wetravel_consent_given', 0 );
	}

	/**
	 * Enqueue tracking scripts
	 */
	public function enqueue_tracking_scripts() {
		// Skip if tracking is disabled
		if ( ! $this->is_tracking_enabled() ) {
			return;
		}

		wp_enqueue_script(
			'wetravel-tracking',
			WETRAVEL_WIDGETS_PLUGIN_URL . 'assets/js/wetravel-tracking.js',
			array( 'jquery' ),
			filemtime( WETRAVEL_WIDGETS_PLUGIN_DIR . 'assets/js/wetravel-tracking.js' ),
			true
		);

		// Localize script with tracking data
		wp_localize_script(
			'wetravel-tracking',
			'wetravelTrackingData',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'wetravel_tracking_nonce' ),
				'has_consent' => $this->is_tracking_enabled(),
				'tracking_endpoint' => get_option( 'wetravel_tracking_endpoint', 'http://localhost:9292' ),
				'events' => array(
					'widget_load' => $this->is_tracking_enabled(),
					'widget_click' => $this->is_tracking_enabled(),
					'button_click' => $this->is_tracking_enabled(),
				),
				'site_info' => array(
					'site_url' => get_site_url(),
					'site_name' => get_bloginfo( 'name' ),
					'page_type' => $this->get_current_page_type(),
					'user_role' => $this->get_user_role(),
				)
			)
		);
	}

	/**
	 * Handle tracking event AJAX request
	 */
	public function handle_track_event() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'wetravel_tracking_nonce' ) ) {
			wp_die( 'Security check failed' );
		}

		// Check if tracking is enabled
		if ( ! $this->is_tracking_enabled() ) {
			wp_send_json_error( 'User has not given consent' );
		}

		$event_type = sanitize_text_field( $_POST['event_type'] );
		$widget_id = sanitize_text_field( $_POST['widget_id'] ?? '' );
		$event_data = $_POST['event_data'] ?? array();

		// Sanitize event data
		$event_data = $this->sanitize_event_data( $event_data );

		// Send to external endpoint
		$endpoint = get_option( 'wetravel_tracking_endpoint', 'http://localhost:9292/' );
		$sent = $this->send_to_endpoint( $event_type, $widget_id, $event_data, $endpoint );

		if ( $sent ) {
			wp_send_json_success( 'Event tracked successfully' );
		} else {
			wp_send_json_error( 'Failed to send to tracking endpoint' );
		}
	}



	/**
	 * Send tracking data to external endpoint
	 */
	private function send_to_endpoint( $event_type, $widget_id, $event_data, $endpoint ) {
		// Construct the full API endpoint URL
		$base_url = rtrim( $endpoint, '/' );
		$full_url = $base_url . '/public/v1/plugin/track';

		// Extract widget and layout type from event data
		$widget_type = $event_data['display_type'] ?? 'unknown';
		$layout_type = $event_data['display_type'] ?? 'vertical';
		$button_type = $event_data['button_type'] ?? 'book_now';

		// Get current user ID, default to 0 for anonymous
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			$user_id = 0;
		}

		// Map to new PluginLog structure
		$payload = array(
			'user_id' => $user_id,
			'wt_user_id' => $event_data['wt_user_id'] ?? '',
			'base_url' => home_url(),
			'wt_widget_type' => $widget_type,
			'version' => WETRAVEL_PLUGIN_VERSION,
			'full_page_url' => $event_data['page_url'] ?? $_SERVER['REQUEST_URI'] ?? '',
			'event_type' => $event_type,
			'layout_type' => $layout_type,
			'button_type' => $button_type,
			'integration_type' => 'wordpress-plugin',
			'trip_uuid' => $event_data['trip_uuid'] ?? '',
			'trip_type' => $event_data['trip_type'] ?? '',
		);

		// Get API key from settings or environment for authentication
		$api_key = get_option( 'wetravel_tracking_api_key', '' );
		if ( empty( $api_key ) && defined( 'WETRAVEL_INTERNAL_API_KEY' ) ) {
			$api_key = WETRAVEL_INTERNAL_API_KEY;
		}

		$headers = array(
			'Content-Type' => 'application/json',
			'User-Agent' => 'WeTravel-Widgets-Plugin/' . get_option( 'wetravel_plugin_version', '1.0' ),
		);

		// Add X-WP-Plugin-Key header if API key is available
		if ( ! empty( $api_key ) ) {
			$headers['X-WP-Plugin-Key'] = $api_key;
		}

		$response = wp_remote_post( $full_url, array(
			'timeout' => 10,
			'headers' => $headers,
			'body' => json_encode( $payload ),
		) );

		if ( is_wp_error( $response ) ) {
			error_log( 'WeTravel Tracking Error: ' . $response->get_error_message() );
			return false;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		return $response_code >= 200 && $response_code < 300;
	}

	/**
	 * Sanitize event data
	 */
	private function sanitize_event_data( $data ) {
		if ( ! is_array( $data ) ) {
			return array();
		}

		$sanitized = array();
		foreach ( $data as $key => $value ) {
			$key = sanitize_key( $key );
			if ( is_array( $value ) ) {
				$sanitized[$key] = $this->sanitize_event_data( $value );
			} else {
				$sanitized[$key] = sanitize_text_field( $value );
			}
		}

		return $sanitized;
	}

	/**
	 * Get current page type
	 */
	private function get_current_page_type() {
		if ( is_front_page() ) return 'home';
		if ( function_exists( 'is_shop' ) && is_shop() ) return 'shop';
		if ( function_exists( 'is_product' ) && is_product() ) return 'product';
		if ( function_exists( 'is_cart' ) && is_cart() ) return 'cart';
		if ( function_exists( 'is_checkout' ) && is_checkout() ) return 'checkout';
		if ( function_exists( 'is_account_page' ) && is_account_page() ) return 'account';
		if ( is_category() ) return 'category';
		if ( is_tag() ) return 'tag';
		if ( is_author() ) return 'author';
		if ( is_date() ) return 'date';
		if ( is_search() ) return 'search';
		if ( is_404() ) return '404';
		if ( is_page() ) return 'page';
		if ( is_single() ) return 'post';
		if ( is_archive() ) return 'archive';
		return 'other';
	}

	/**
	 * Get user role
	 */
	private function get_user_role() {
		if ( ! is_user_logged_in() ) {
			return 'visitor';
		}

		$user = wp_get_current_user();
		$roles = $user->roles;

		if ( empty( $roles ) ) {
			return 'user';
		}

		return $roles[0]; // Return first role
	}
}

// Initialize tracking
function wetravel_tracking_init() {
	return WetravelTracking::get_instance();
}

// Hook to initialize tracking
add_action( 'plugins_loaded', 'wetravel_tracking_init' );

/**
 * WeTravel Audit API Class
 *
 * Handles advanced tracking for plugin events and user state
 */
class WeTravelAuditAPI {

	/**
	 * Instance of this class
	 */
	private static $instance = null;

	/**
	 * Base API URL for tracking
	 */
	private $base_url;

	/**
	 * Constructor
	 */
	private function __construct() {
		// Get the tracking endpoint from settings, fallback to WeTravel production
		$this->base_url = get_option( 'wetravel_tracking_endpoint', 'http://localhost:9292' );
		// Remove trailing slash
		$this->base_url = rtrim( $this->base_url, '/' );
	}

	/**
	 * Get instance
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

		/**
	 * Make HTTP request to WeTravel API
	 */
	public function make_request( $endpoint, $data, $method = 'POST', $bypass_consent = false ) {
		// Check if tracking is enabled and has consent (unless bypass is requested)
		$tracking = WetravelTracking::get_instance();

		// Enhanced debugging for consent and settings
		$tracking_enabled = $tracking->is_tracking_enabled();

		if ( ! $tracking_enabled && ! $bypass_consent ) {
			return false;
		}

		$url = $this->base_url . $endpoint;

		// Get API key from settings or environment
		$api_key = get_option( 'wetravel_tracking_api_key', '' );
		if ( empty( $api_key ) && defined( 'WETRAVEL_INTERNAL_API_KEY' ) ) {
			$api_key = WETRAVEL_INTERNAL_API_KEY;
		}

		$headers = array(
			'Content-Type' => 'application/json',
			'User-Agent'   => 'WeTravel-WordPress-Plugin/' . WETRAVEL_PLUGIN_VERSION,
		);

		// Add X-WP-Plugin-Key header if API key is available
		if ( ! empty( $api_key ) ) {
			$headers['X-WP-Plugin-Key'] = $api_key;
		}

		$args = array(
			'method'  => $method,
			'timeout' => 15,
			'headers' => $headers,
			'body'    => json_encode( $data ),
		);

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		$response_headers = wp_remote_retrieve_headers( $response );


		$success = $response_code >= 200 && $response_code < 300;

		return $success;
	}

	/**
	 * Track a plugin event
	 */
	public function track_event( $user_id, $event_type, $event_data = array() ) {
		// Extract widget and layout information from event data
		// TODO: widget_type should be wt_widget_type, like 'all-trips', 'button'
		$widget_type = $event_data['widget_type'] ?? $event_data['display_type'] ?? 'unknown';
		$layout_type = $event_data['display_type'] ?? 'vertical';
		$button_type = $event_data['button_type'] ?? 'book_now';
		$page_url = $event_data['page_url'] ?? $_SERVER['REQUEST_URI'] ?? '';

		$data = array(
			'user_id'          => intval( $user_id ),
			'wt_user_id'       => $event_data['wt_user_id'] ?? '',
			'base_url'         => home_url(),
			'wt_widget_type'   => $widget_type,
			'version'          => WETRAVEL_PLUGIN_VERSION,
			'full_page_url'    => $page_url,
			'event_type'       => $event_type,
			'layout_type'      => $layout_type,
			'button_type'      => $button_type,
			'integration_type' => 'wordpress-plugin',
			'trip_uuid'        => $event_data['trip_uuid'] ?? '',
			'trip_type'        => $event_data['trip_type'] ?? '',
		);

		return $this->make_request( '/public/v1/plugin/track', $data );
	}

	/**
	 * Convenience methods for common events
	 */
	public function track_widget_view( $user_id, $widget_id, $widget_type ) {
		return $this->track_event( $user_id, 'widget_load', array(
			'widget_id'    => $widget_id,
			'widget_type'  => $widget_type,
			'display_type' => $widget_type,
			'page_url'     => $this->get_current_page_url(),
			'timestamp'    => current_time( 'mysql' ),
		) );
	}

	public function track_button_click( $user_id, $button_type, $trip_id = null ) {
		return $this->track_event( $user_id, 'button_click', array(
			'button_type' => $button_type,
			'trip_id'     => $trip_id,
			'page_url'    => $this->get_current_page_url(),
			'user_agent'  => $_SERVER['HTTP_USER_AGENT'] ?? '',
			'timestamp'   => current_time( 'mysql' ),
		) );
	}

	public function track_admin_action( $user_id, $action, $details = array() ) {
		return $this->track_event( $user_id, 'admin_action', array_merge( array(
			'action'       => $action,
			'wp_user_role' => $this->get_user_role( $user_id ),
			'timestamp'    => current_time( 'mysql' ),
		), $details ) );
	}

	/**
	 * Track user state - call this periodically or on significant changes
	 *
	 * @param int|string $wp_user_id WordPress user ID
	 * @param string|null $wt_user_id WeTravel user ID (null if not yet configured)
	 * @param string|null $wt_user_slug WeTravel user slug (null if not yet configured)
	 * @param bool|null $force_plugin_state Force plugin state (null for auto-detection)
	 * @param bool $anonymous Whether to track anonymously
	 * @param array $additional_data Additional data to include
	 * @param bool $bypass_consent Whether to bypass consent check for critical events
	 */
	public function track_user_state( $wp_user_id, $wt_user_id, $wt_user_slug, $force_plugin_state = null, $anonymous = false, $additional_data = array(), $bypass_consent = false ) {
		global $wp_version;

		// Get current plugin active status
		// Use forced state if provided (useful during activation/deactivation hooks)
		if ( $force_plugin_state !== null ) {
			$is_plugin_active = $force_plugin_state;
		} else {
			$is_plugin_active = is_plugin_active( 'wetravel-widgets/wetravel-widgets.php' );
		}

		// Get consent type
		$consent_type = get_option( 'wetravel_consent_type', 'unknown' );

		if ( $anonymous ) {
			// Create irreversible hashes for sensitive data
			$salt = bin2hex(random_bytes(16));

			// Hash user_id (WordPress user ID)
			$hashed_user_id = hash('sha256', $salt . '|' . ( $wp_user_id ? $wp_user_id : 'anonymous' ));

			// Hash site_url
			$hashed_site_url = hash('sha256', $salt . '|' . home_url());

			// Hash WeTravel user credentials (null values are expected and handled properly)
			$hashed_wt_user_id = hash('sha256', $salt . '|' . $wt_user_id);
			$hashed_wt_user_slug = hash('sha256', $salt . '|' . $wt_user_slug);

			// Build the data payload with the specified structure
			$data = array(
				'user_id' => $hashed_user_id,
				'site_url' => $hashed_site_url,
				'wt_user_id' => $hashed_wt_user_id,
				'wt_user_slug' => $hashed_wt_user_slug,
				'plugin_version' => WETRAVEL_PLUGIN_VERSION,
				'is_plugin_active' => $is_plugin_active,
				'wp_consent_type' => $consent_type,
			);

		} else {
			$data = array(
				'user_id' => $wp_user_id,
				'site_url' => home_url(),
				'wt_user_id' => $wt_user_id,
				'wt_user_slug' => $wt_user_slug,
				'wt_user_env' => $this->get_wetravel_environment(),
				'wt_widget_types' => $this->get_widget_counts(),
				'plugin_version' => WETRAVEL_PLUGIN_VERSION,
				'wp_theme' => get_option( 'template' ),
				'wp_version' => $wp_version,
				'wp_php_version' => phpversion(),
				'active_widgets_count' => $this->get_active_widget_counts(),
				'is_plugin_active' => $is_plugin_active,
				'wp_consent_type' => $consent_type,
				'is_multisite' => is_multisite(),
				'wp_locale' => get_locale(),
			);
		}

		// Add deactivation data if this is a deactivation event
		if ( ! $is_plugin_active ) {
			$deactivation_reason = get_transient( 'wetravel_deactivation_reason' ) ?: '';
			$deactivation_reason_details = get_transient( 'wetravel_deactivation_reason_details' ) ?: '';

			// Add deactivation data from additional_data if provided (from deactivation form)
			if ( ! empty( $additional_data['deactivation_reason'] ) ) {
				$deactivation_reason = $additional_data['deactivation_reason'];
			}
			if ( ! empty( $additional_data['deactivation_reason_details'] ) ) {
				$deactivation_reason_details = $additional_data['deactivation_reason_details'];
			}

			$data['deactivation_reason'] = $deactivation_reason;
			$data['deactivation_reason_details'] = $deactivation_reason_details;
		}

		// Add additional data if provided (excluding deactivation data as it's handled above)
		if ( ! empty( $additional_data ) ) {
			// Remove deactivation data from additional_data to avoid duplication
			unset( $additional_data['deactivation_reason'] );
			unset( $additional_data['deactivation_reason_details'] );

			// Add remaining additional data
			if ( ! empty( $additional_data ) ) {
				$data = array_merge( $data, $additional_data );
			}
		}

		return $this->make_request( '/public/v1/plugin/track/user/state', $data, 'POST', $bypass_consent );
	}

	/**
	 * Track plugin state - statistics about plugin usage
	 */
	public function track_plugin_state( $action_type = '', $bypass_consent = false ) {
		// Get plugin state statistics
		$plugin_state_data = array(
			'plugin_slug'        => WETRAVEL_PLUGIN_SLUG,
			'plugin_version'     => WETRAVEL_PLUGIN_VERSION,
			'event_type'         => $action_type,
		);

		return $this->make_request( '/public/v1/plugin/track/state', $plugin_state_data, 'POST', $bypass_consent );
	}

	/**
	 * Get WeTravel environment
	 */
	private function get_wetravel_environment() {
		$env_url = get_option( 'wetravel_trips_env', 'https://pre.wetravel.to' );

		return $env_url;
	}

	/**
	 * Get widget counts by type
	 */
	/**
	 * Get widget counts by widget type and display type, dynamically.
	 * Returns array: [ 'all-trips' => [ 'vertical' => 2, 'carousel' => 1, ... ], ... ]
	 */
	private function get_widget_counts() {
		$designs = get_option( 'wetravel_trips_designs', array() );
		$counts = array();

		foreach ( $designs as $design ) {
			// Use 'wtWidgetType' (new) or fallback to 'widgetType' (legacy), default to 'all-trips'
			$widget_type = isset( $design['wtWidgetType'] ) ? $design['wtWidgetType'] : ( isset( $design['widgetType'] ) ? $design['widgetType'] : 'all-trips' );
			$display_type = isset( $design['displayType'] ) ? $design['displayType'] : 'vertical';

			if ( ! isset( $counts[ $widget_type ] ) ) {
				$counts[ $widget_type ] = array();
			}
			if ( ! isset( $counts[ $widget_type ][ $display_type ] ) ) {
				$counts[ $widget_type ][ $display_type ] = 0;
			}
			$counts[ $widget_type ][ $display_type ]++;
		}

		// Optionally, add total for each widget type and grand total
		$grand_total = 0;
		foreach ( $counts as $widget_type => $display_counts ) {
			$type_total = array_sum( $display_counts );
			$counts[ $widget_type ]['total'] = $type_total;
			$grand_total += $type_total;
		}
		$counts['total'] = $grand_total;

		return $counts;
	}

	/**
	 * Get button type counts
	 */
	private function get_button_type_counts() {
		$designs = get_option( 'wetravel_trips_designs', array() );

		$counts = array(
			'book_now'          => 0,
			'trip_link'        => 0,
			'custom'            => 0,
		);

		foreach ( $designs as $design ) {
			$button_type = $design['buttonType'] ?? 'book_now';
			if ( isset( $counts[$button_type] ) ) {
				$counts[$button_type]++;
			} else {
				$counts['custom']++;
			}
		}

		return $counts;
	}

	/**
	 * Get active widget counts by type and integration method
	 * Counts actual usage of widgets in published posts/pages
	 */
	private function get_active_widget_counts() {
		global $wpdb;

		$designs = get_option( 'wetravel_trips_designs', array() );

		// Initialize structure to match WeTravel's expected format
		// Dynamically build $counts by widget type from $designs
		$counts = array();

		if ( empty( $designs ) ) {
			return $counts;
		}

		foreach ( $designs as $design_id => $design ) {
			$widget_type = isset( $design['widgetType'] ) ? $design['widgetType'] : 'all-trips';

			// Legacy widget counting for backward compatibility
			$has_src = ! empty( $design['src'] );
			$has_slug = ! empty( $design['slug'] );
			$has_user_id = ! empty( $design['wetravelUserID'] );

			// --- Check shortcode usage ---
			// Check for both design ID and keyword patterns
			$shortcode_patterns = array(
				'[wetravel_trips widget="' . $design_id . '"',
			);

			// Add keyword pattern if design has keyword
			if ( ! empty( $design['keyword'] ) ) {
				$shortcode_patterns[] = '[wetravel_trips widget="' . $design['keyword'] . '"';
			}

			$shortcode_count = 0;
			foreach ( $shortcode_patterns as $pattern ) {
				$pattern_count = $wpdb->get_var( $wpdb->prepare(
					"SELECT COUNT(DISTINCT ID) FROM {$wpdb->posts}
					 WHERE post_status='publish'
					 AND post_content LIKE %s",
					'%' . $wpdb->esc_like( $pattern ) . '%'
				) );
				$shortcode_count = max( $shortcode_count, intval( $pattern_count ) );
			}

			// --- Check Gutenberg block usage ---
			// Look for block patterns with this design ID
			$block_patterns = array(
				'"selectedDesignID":"' . $design_id . '"',
				'"selectedDesignID":' . $design_id,
			);

			// Add keyword pattern for blocks if design has keyword
			if ( ! empty( $design['keyword'] ) ) {
				$block_patterns[] = '"selectedDesignID":"' . $design['keyword'] . '"';
			}

			$block_count = 0;
			foreach ( $block_patterns as $pattern ) {
				$pattern_count = $wpdb->get_var( $wpdb->prepare(
					"SELECT COUNT(DISTINCT ID) FROM {$wpdb->posts}
					 WHERE post_status='publish'
					 AND post_content LIKE %s
					 AND post_content LIKE '%wetravel-trips/block%'",
					'%' . $wpdb->esc_like( $pattern ) . '%'
				) );
				$block_count = max( $block_count, intval( $pattern_count ) );
			}

			if ( ! isset( $counts[ $widget_type ] ) ) {
				$counts[ $widget_type ] = array(
					'shortcode' => 0,
					'block' => 0,
				);
			}
			if ( $shortcode_count > 0 ) {
				$counts[ $widget_type ]['shortcode']++;
			}
			if ( $block_count > 0 ) {
				$counts[ $widget_type ]['block']++;
			}
		}

		return $counts;
	}

	/**
	 * Get current page URL
	 */
	private function get_current_page_url() {
		if ( ! isset( $_SERVER['HTTP_HOST'] ) || ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return home_url();
		}

		$protocol = ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ) ? 'https' : 'http';
		return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	}

	/**
	 * Get user role
	 */
	private function get_user_role( $user_id ) {
		$user = get_userdata( $user_id );
		return $user ? implode( ', ', $user->roles ) : 'unknown';
	}
}

/**
 * Helper function to track events from PHP
 */
function wetravel_track_event( $event_type, $widget_id = '', $event_data = array() ) {
	$tracking = WetravelTracking::get_instance();

	if ( ! $tracking->is_tracking_enabled() ) {
		return false;
	}

	// This would be called from widget render functions
	// For now, we'll rely on JavaScript tracking
	return true;
}

/**
 * Helper function to get audit API instance
 */
function wetravel_get_audit_api() {
	return WeTravelAuditAPI::get_instance();
}

/**
 * Helper function to track widget view
 */
function wetravel_track_widget_view( $widget_id, $widget_type ) {
	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		$user_id = 0; // Anonymous user
	}

	$audit_api = wetravel_get_audit_api();
	return $audit_api->track_widget_view( $user_id, $widget_id, $widget_type );
}

/**
 * Helper function to track button click
 */
function wetravel_track_button_click( $button_type, $trip_id = null ) {
	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		$user_id = 0; // Anonymous user
	}

	$audit_api = wetravel_get_audit_api();
	return $audit_api->track_button_click( $user_id, $button_type, $trip_id );
}

/**
 * Helper function to get active widget counts
 * Can be called from anywhere in the plugin to get current usage statistics
 *
 * @return array Detailed widget usage counts by type and integration method
 */
function wetravel_get_active_widget_counts() {
	$audit_api = wetravel_get_audit_api();
	// Use reflection to call the private method
	$reflection = new ReflectionClass( $audit_api );
	$method = $reflection->getMethod( 'get_active_widget_counts' );
	$method->setAccessible( true );
	error_log( 'get_active_widget_counts' );
	return $method->invoke( $audit_api );
}

/**
 * Helper function to track user state
 *
 * @param string|null $wt_user_id WeTravel user ID (null if not yet configured)
 * @param string|null $wt_user_slug WeTravel user slug (null if not yet configured)
 * @param bool|null $force_plugin_state Force plugin state (null for auto-detection)
 * @param bool $anonymous Whether to track anonymously
 * @param array $additional_data Additional data to include
 * @param bool $bypass_consent Whether to bypass consent check for critical events
 */
function wetravel_track_user_state( $wt_user_id = null, $wt_user_slug = null, $force_plugin_state = null, $anonymous = false, $additional_data = array(), $bypass_consent = false ) {
    $wp_user_id = get_current_user_id();
    // If anonymous tracking is requested, generate a hash for the user_id
    if ( $anonymous ) {
        // Generate an irreversible hash for user_id using SHA-256 with a salt
        $salt = bin2hex(random_bytes(16));
        $wp_user_id = hash('sha256', $salt . '|' . $wp_user_id);
    }

    // Get WeTravel user info from plugin settings if not provided
    if ( ! $wt_user_id ) {
        $wt_user_id = get_option( 'wetravel_trips_user_id', '' );
    }
    if ( ! $wt_user_slug ) {
        $wt_user_slug = get_option( 'wetravel_trips_slug', '' );
    }

    $audit_api = wetravel_get_audit_api();
    return $audit_api->track_user_state( $wp_user_id, $wt_user_id, $wt_user_slug, $force_plugin_state, $anonymous, $additional_data, $bypass_consent );
}

function wetravel_track_plugin_state($action_type){
	$audit_api = wetravel_get_audit_api();
	return $audit_api->track_plugin_state( $action_type, true );
}

/**
 * AJAX handler to get active widget counts for testing
 */
// TODO: Remove this after testing
function wetravel_ajax_get_active_widget_counts() {
	// Check user permissions
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'You do not have sufficient permissions to access this page.' );
	}

	// Verify nonce if you want extra security (optional for testing)
	// check_ajax_referer( 'wetravel_admin_nonce', 'nonce' );

	$counts = wetravel_get_active_widget_counts();

	wp_send_json_success( array(
		'message' => 'Widget counts retrieved successfully',
		'counts' => $counts,
		'timestamp' => current_time( 'mysql' )
	) );
}

// Register AJAX handlers
add_action( 'wp_ajax_wetravel_get_active_widget_counts', 'wetravel_ajax_get_active_widget_counts' );
