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

		// Track widget usage to keep user state up-to-date
		add_action( 'save_post', array( $this, 'update_user_state_on_widget_change' ), 10, 3 );

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
	 * Check if current context is admin, edit, preview, or customizer
	 */
	public function is_admin_or_edit_context() {
		// Skip in WordPress admin
		if ( is_admin() ) {
			return true;
		}

		// Skip in preview mode
		if ( is_preview() ) {
			return true;
		}

		// Skip in customizer
		if ( is_customize_preview() ) {
			return true;
		}

		// Skip in block editor (REST API context)
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return true;
		}

		// Skip if this is an iframe preview (Gutenberg editor)
		// Check for 'context' parameter and verify nonce if present
		if (
			isset( $_GET['context'], $_GET['_wpnonce'] ) &&
			wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'wetravel_context_nonce' ) &&
			sanitize_text_field( wp_unslash( $_GET['context'] ) ) === 'edit'
		) {
			return true;
		}

		return false;
	}

	/**
	 * Enqueue tracking scripts
	 */
	public function enqueue_tracking_scripts() {
		// Skip if tracking is disabled
		if ( ! $this->is_tracking_enabled() ) {
			return;
		}

		// Skip tracking in admin, edit, preview, or customizer contexts
		if ( $this->is_admin_or_edit_context() ) {
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
				'is_admin_context' => $this->is_admin_or_edit_context(),
				'tracking_endpoint' => get_option( 'wetravel_tracking_endpoint', 'http://localhost:9292' ),
				'events' => array(
					'widget_load' => $this->is_tracking_enabled(),
					'widget_click' => $this->is_tracking_enabled(),
					'button_click' => $this->is_tracking_enabled(),
				),
				'site_info' => array(
					'site_url' => get_site_url(),
					'site_name' => get_bloginfo( 'name' ),
				)
			)
		);
	}

	/**
	 * Handle tracking event AJAX request
	 */
	public function handle_track_event() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wetravel_tracking_nonce' ) ) {
			wp_die( 'Security check failed' );
		}

		// Check if tracking is enabled
		if ( ! $this->is_tracking_enabled() ) {
			wp_send_json_error( 'User has not given consent' );
		}


		$widget_id = isset( $_POST['widget_id'] ) ? sanitize_text_field( wp_unslash( $_POST['widget_id'] ) ) : '';
		$raw_event_data = isset( $_POST['event_data'] ) ? sanitize_text_field( wp_unslash( $_POST['event_data'] ) ) : array();
		$event_data = $this->sanitize_event_data( $raw_event_data );

		// Send directly to wt_widgets_tracking endpoint
		$sent = $this->send_tracking_data( $event_data );

		if ( $sent ) {
			wp_send_json_success( 'Event tracked successfully' );
		} else {
			wp_send_json_error( 'Failed to send to tracking endpoint' );
		}
	}

	/**
	 * Track a plugin event
	 */
	public function track_event( $event_type, $event_data = array() ) {
		// Extract widget and layout information from event data
		$wt_widget_type = $event_data['wt_widget_type'] ?? 'unknown';
		$layout_type = $event_data['display_type'] ?? 'vertical';
		$button_type = $event_data['button_type'] ?? 'book_now';
		$page_url = $event_data['page_url'] ?? ( isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '' );
		$integration_type = $event_data['integration_type'] ?? 'block';

		$data = array(
			'wt_user_id'       => $event_data['wt_user_id'] ?? '',
			'base_url'         => home_url(),
			'wt_widget_type'   => $wt_widget_type,
			'version'          => WETRAVEL_PLUGIN_VERSION,
			'full_page_url'    => $page_url,
			'event_type'       => $event_type,
			'layout_type'      => $layout_type,
			'button_type'      => $button_type,
			'integration_type' => $integration_type,
			'trip_uuid'        => $event_data['trip_uuid'] ?? '',
			'trip_type'        => $event_data['trip_type'] ?? '',
			'user_agent'       => $event_data['user_agent'] ?? '',
			'requested_for'    => 'wp_plugin_event',
		);

		return $this->send_tracking_data( $data, false );
	}

	/**
	 * Convenience methods for common events
	 */
	public function track_widget_view( $event_data ) {
		$page_url = $this->get_current_page_url();
		return $this->track_event( 'widget_load', array_merge( $event_data, array(
			'page_url' => $page_url,
			'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
		) ) );
	}

	/**
	 * Track user state - call this periodically or on significant changes
	 *
	 * @param string|null $wt_user_id WeTravel user ID (null if not yet configured)
	 * @param string|null $wt_user_slug WeTravel user slug (null if not yet configured)
	 * @param bool|null $force_plugin_state Force plugin state (null for auto-detection)
	 * @param bool $anonymous Whether to track anonymously
	 * @param array $additional_data Additional data to include
	 * @param bool $bypass_consent Whether to bypass consent check for critical events
	 */
	public function track_user_state( $wt_user_id, $wt_user_slug, $force_plugin_state = null, $anonymous = false, $additional_data = array(), $bypass_consent = false ) {
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

			// Hash site_url
			$hashed_site_url = hash('sha256', $salt . '|' . home_url());

			// Hash WeTravel user credentials (null values are expected and handled properly)
			$hashed_wt_user_id = hash('sha256', $salt . '|' . $wt_user_id);
			$hashed_wt_user_slug = hash('sha256', $salt . '|' . $wt_user_slug);

			// Build the data payload with the specified structure
			$data = array(
				'site_url' => $hashed_site_url,
				'wt_user_id' => $hashed_wt_user_id,
				'wt_user_slug' => $hashed_wt_user_slug,
				'plugin_version' => WETRAVEL_PLUGIN_VERSION,
				'is_plugin_active' => $is_plugin_active,
				'wp_consent_type' => $consent_type,
			);

		} else {
			$data = array(
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

		// Add requested_for field if not already set (e.g., from deactivation)
		if ( ! isset( $data['requested_for'] ) ) {
			$data['requested_for'] = 'wp_user_state';
		}

		return $this->send_tracking_data( $data, $bypass_consent );
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
			'requested_for'      => 'wp_plugin_state',
		);

		return $this->send_tracking_data( $plugin_state_data, $bypass_consent );
	}

	/**
	 * Send tracking data to WeTravel widget tracking endpoint
	 *
	 * @param array $event_data The tracking data to send
	 * @param bool $bypass_consent Whether to bypass consent check for critical events
	 * @param int $timeout HTTP request timeout in seconds (default: 10)
	 * @return bool True if tracking was successful, false otherwise
	 */
	public function send_tracking_data( $event_data, $bypass_consent = false, $timeout = 10 ) {
		// Check if tracking is enabled (unless bypass is requested)
		if ( ! $bypass_consent && ! $this->is_tracking_enabled() ) {
			return false;
		}

		// Get the tracking URL (same logic as embeds)
		$env = get_option( 'wetravel_trips_env', 'https://wetravel.com' );
		$tracking_url = $this->get_tracking_url( $env );

		// Use event data directly
		$payload = $event_data;

		$response = wp_remote_post( $tracking_url, array(
			'timeout' => $timeout,
			'headers' => array(
				'Content-Type' => 'application/json',
			),
			'body' => json_encode( $payload ),
		) );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		return $response_code >= 200 && $response_code < 300;
	}

	/**
	 * Same tracking URL logic as embeds
	 */
	private function get_tracking_url( $env ) {
		if ( strpos( $env, 'wetravel.com' ) !== false ) {
			return 'https://t.wetravel.com/widgets';
		}

		$parsed = wp_parse_url( $env );
		$host = $parsed['host'] ?? $env;
		$base = implode( '.', array_slice( explode( '.', $host ), -3 ) );
		return 'https://t.' . $base . '/widgets';
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
	 * Get widget counts by widget type and display type, dynamically.
	 * Returns array: [ 'all-trips' => [ 'vertical' => 2, 'carousel' => 1, ... ], ... ]
	 *
	 * @todo Consider consolidating with get_active_widget_counts() to reduce code duplication
	 */
	private function get_widget_counts() {
		$designs = get_option( 'wetravel_trips_designs', array() );
		$counts = array();

		foreach ( $designs as $design ) {
			$wt_widget_type = isset( $design['wtWidgetType'] ) ? $design['wtWidgetType'] : 'all-trips';
			$display_type = isset( $design['displayType'] ) ? $design['displayType'] : 'vertical';

			if ( ! isset( $counts[ $wt_widget_type ] ) ) {
				$counts[ $wt_widget_type ] = array();
			}
			if ( ! isset( $counts[ $wt_widget_type ][ $display_type ] ) ) {
				$counts[ $wt_widget_type ][ $display_type ] = 0;
			}
			$counts[ $wt_widget_type ][ $display_type ]++;
		}

		// Optionally, add total for each widget type and grand total
		$grand_total = 0;
		foreach ( $counts as $wt_widget_type => $display_counts ) {
			$type_total = array_sum( $display_counts );
			$counts[ $wt_widget_type ]['total'] = $type_total;
			$grand_total += $type_total;
		}
		$counts['total'] = $grand_total;

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
			return (object) $counts;
		}

		foreach ( $designs as $design_id => $design ) {
			$wt_widget_type = isset( $design['widgetType'] ) ? $design['widgetType'] : 'all-trips';

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
				// Use caching for database queries to improve performance
				$cache_key = 'wetravel_shortcode_count_' . md5( $pattern );
				$pattern_count = wp_cache_get( $cache_key );

				if ( false === $pattern_count ) {
					$cache_duration = defined( 'WETRAVEL_USAGE_CACHE_DURATION' ) ? WETRAVEL_USAGE_CACHE_DURATION : HOUR_IN_SECONDS;
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Necessary for content analysis with caching
					$pattern_count = $wpdb->get_var( $wpdb->prepare(
						"SELECT COUNT(DISTINCT ID) FROM {$wpdb->posts}
						 WHERE post_status='publish'
						 AND post_content LIKE %s",
						'%' . $wpdb->esc_like( $pattern ) . '%'
					) );
					wp_cache_set( $cache_key, $pattern_count, '', $cache_duration );
				}
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
				// Use caching for database queries to improve performance
				$cache_key = 'wetravel_block_count_' . md5( $pattern . '_block' );
				$pattern_count = wp_cache_get( $cache_key );

				if ( false === $pattern_count ) {
					$cache_duration = defined( 'WETRAVEL_USAGE_CACHE_DURATION' ) ? WETRAVEL_USAGE_CACHE_DURATION : HOUR_IN_SECONDS;
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Necessary for content analysis with caching implemented
					$pattern_count = $wpdb->get_var( $wpdb->prepare(
						"SELECT COUNT(DISTINCT ID) FROM {$wpdb->posts}
						 WHERE post_status='publish'
						 AND post_content LIKE %s
						 AND post_content LIKE %s",
						'%' . $wpdb->esc_like( $pattern ) . '%',
						'%wetravel-trips/block%'
					) );
					wp_cache_set( $cache_key, $pattern_count, '', $cache_duration );
				}
				$block_count = max( $block_count, intval( $pattern_count ) );
			}

			if ( ! isset( $counts[ $wt_widget_type ] ) ) {
				$counts[ $wt_widget_type ] = array(
					'shortcode' => 0,
					'block' => 0,
				);
			}
			if ( $shortcode_count > 0 ) {
				$counts[ $wt_widget_type ]['shortcode']++;
			}
			if ( $block_count > 0 ) {
				$counts[ $wt_widget_type ]['block']++;
			}
		}

		return $counts;
	}

	/**
	 * Get current page URL
	 */
	public function get_current_page_url() {
		if ( ! isset( $_SERVER['HTTP_HOST'] ) || ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return home_url();
		}

		$protocol = ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ) ? 'https' : 'http';
		return $protocol . '://' . sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) . esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) );
	}

		/**
	 * Get WeTravel environment
	 */
	private function get_wetravel_environment() {
		$env_url = get_option( 'wetravel_trips_env', 'https://pre.wetravel.to' );

		return $env_url;
	}

	/**
	 * Update user state when widgets are added/modified on posts
	 * Lightweight check to keep user_state current without detailed event tracking
	 */
	public function update_user_state_on_widget_change( $post_id, $post, $update ) {
		// Skip if tracking is disabled
		if ( ! $this->is_tracking_enabled() ) {
			return;
		}

		// Skip autosaves and revisions
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Only track published posts
		if ( $post->post_status !== 'publish' ) {
			return;
		}

		// Check if post contains WeTravel widgets
		if ( $this->post_contains_wetravel_widgets( $post->post_content ) ) {
			// Throttle user state updates to prevent duplicate requests
			$this->throttled_user_state_update();
		}
	}

	/**
	 * Throttled user state update to prevent multiple requests from multiple save hooks
	 */
	private function throttled_user_state_update() {
		$user_id = get_current_user_id();
		$transient_key = 'wetravel_user_state_updated_' . $user_id;
		$last_update = get_transient( $transient_key );

		// Only update if we haven't updated in the last 30 seconds
		if ( ! $last_update ) {
			// Set transient to prevent duplicate updates
			set_transient( $transient_key, time(), 30 );

			// Update user state to keep active widget counts current
			if ( function_exists( 'wetravel_track_user_state' ) ) {
				wetravel_track_user_state();
			}
		}
	}

	/**
	 * Simple check if post content contains WeTravel widgets
	 */
	private function post_contains_wetravel_widgets( $content ) {
		// Check for Gutenberg blocks
		if ( has_blocks( $content ) && strpos( $content, 'wetravel-trips/block' ) !== false ) {
			return true;
		}

		// Check for shortcodes
		if ( strpos( $content, '[wetravel_trips' ) !== false ) {
			return true;
		}

		return false;
	}
}

// Initialize tracking
function wetravel_tracking_init() {
	return WetravelTracking::get_instance();
}

// Hook to initialize tracking
add_action( 'plugins_loaded', 'wetravel_tracking_init' );

/**
 * Helper function to track widget view
 */
function wetravel_track_widget_view( $event_data ) {
	// Skip tracking in admin/edit contexts
	$tracking = WetravelTracking::get_instance();
	if ( $tracking->is_admin_or_edit_context() ) {
		return false;
	}

	return $tracking->track_widget_view( $event_data );
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
    // Get WeTravel user info from plugin settings if not provided
    if ( ! $wt_user_id ) {
        $wt_user_id = get_option( 'wetravel_trips_user_id', '' );
    }
    if ( ! $wt_user_slug ) {
        $wt_user_slug = get_option( 'wetravel_trips_slug', '' );
    }

    $tracking = WetravelTracking::get_instance();
    return $tracking->track_user_state( $wt_user_id, $wt_user_slug, $force_plugin_state, $anonymous, $additional_data, $bypass_consent );
}

function wetravel_track_plugin_state($action_type){
	$tracking = WetravelTracking::get_instance();
	return $tracking->track_plugin_state( $action_type, true );
}
