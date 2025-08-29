<?php
/**
 * Functions
 *
 * Shared functions
 *
 * @package WordPress
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Function to extract slug, env, and src from the embed script.
 *
 * @param  string $embed_code WeTravel All Trips widget code.
 */
function wtwidget_extract_settings( $embed_code ) {
	preg_match( '/src="([^"]+)"/', $embed_code, $src_match );
	preg_match( '/data-slug="([^"]+)"/', $embed_code, $slug_match );
	preg_match( '/data-env="([^"]+)"/', $embed_code, $env_match );
	preg_match( '/data-uid="([^"]+)"/', $embed_code, $wetravel_trips_user_id_match );

	return array(
		'src'                    => isset( $src_match[1] ) ? $src_match[1] : '',
		'slug'                   => isset( $slug_match[1] ) ? $slug_match[1] : '',
		'env'                    => isset( $env_match[1] ) ? $env_match[1] : '',
		'wetravel_trips_user_id' => isset( $wetravel_trips_user_id_match[1] ) ? $wetravel_trips_user_id_match[1] : '',
	);
}

/** Hook into 'admin_init' to process the settings update. */
function wtwidget_save_embed_code() {
	if ( isset( $_POST['wetravel_trips_embed_code'] ) ) {
		check_admin_referer( 'wetravel_trips_options-options' ); // Verify nonce.

		$allowed_html = array(
			'div'    => array(),
			'script' => array(
				'src'          => array(),
				'id'           => array(),
				'data-env'     => array(),
				'data-version' => array(),
				'data-uid'     => array(),
				'data-slug'    => array(),
				'data-color'   => array(),
				'data-text'    => array(),
				'data-name'    => array(),
			),
		);

		$new_embed_code = wp_kses( wp_unslash( $_POST['wetravel_trips_embed_code'] ), $allowed_html );
		update_option( 'wetravel_trips_embed_code', $new_embed_code );

		// Extract and save the details.
		$extracted_values = wtwidget_extract_settings( $new_embed_code );
		update_option( 'wetravel_trips_slug', $extracted_values['slug'] );
		update_option( 'wetravel_trips_env', $extracted_values['env'] );
		update_option( 'wetravel_trips_src', $extracted_values['src'] );
		update_option( 'wetravel_trips_user_id', $extracted_values['wetravel_trips_user_id'] );

		// Save the timestamp of the last update.
		update_option( 'wetravel_trips_last_saved', gmdate( 'F j, Y \a\t g:i a' ) );

		$has_consent = get_option( 'wetravel_consent_given', false );
		if ( $has_consent && function_exists( 'wetravel_track_user_state' ) ) {
			wetravel_track_user_state( $extracted_values['wetravel_trips_user_id'], $extracted_values['slug'], true, false, array() );
		}

		// Redirect to prevent resubmission.
		$redirect_url = add_query_arg(
			array(
				'saved' => 'true',
				'display_nonce' => wp_create_nonce( 'wetravel_display_message' )
			),
			admin_url( 'admin.php?page=wetravel-trips-setup' )
		);
		wp_safe_redirect( $redirect_url );
		exit;
	}
}
add_action( 'admin_init', 'wtwidget_save_embed_code' );

/** Register AJAX endpoint for keyword validation. */
function wtwidget_register_ajax() {
	add_action( 'wp_ajax_check_keyword_unique', 'wtwidget_check_keyword_unique' );
}
add_action( 'init', 'wtwidget_register_ajax' );

/** AJAX callback to check keyword uniqueness. */
function wtwidget_check_keyword_unique() {
	// Check nonce for security.
	check_ajax_referer( 'wetravel_trips_nonce', 'nonce' );

	$keyword           = isset( $_POST['keyword'] ) ? sanitize_text_field( wp_unslash( $_POST['keyword'] ) ) : '';
	$current_design_id = isset( $_POST['design_id'] ) ? sanitize_text_field( wp_unslash( $_POST['design_id'] ) ) : '';
	$is_unique         = true;

	if ( ! empty( $keyword ) ) {
		$designs = get_option( 'wetravel_trips_designs', array() );
		foreach ( $designs as $id => $existing_design ) {
			if ( isset( $existing_design['keyword'] ) &&
				$existing_design['keyword'] === $keyword &&
				$id !== $current_design_id ) {
				$is_unique = false;
				break;
			}
		}
	}

	wp_send_json(
		array(
			'unique' => $is_unique,
		)
	);
}

/**
 * Enqueue scripts and styles for the plugin
 */
function wtwidget_enqueue_scripts() {
	// Enqueue editor fix script
	wp_register_script(
		'wetravel-trips-editor-fix',
		plugins_url( 'assets/js/editor-fix.js', dirname( __FILE__ ) ),
		array( 'jquery' ),
		filemtime( plugin_dir_path( dirname( __FILE__ ) ) . 'assets/js/editor-fix.js' ),
		true
	);

	wp_enqueue_script( 'wetravel-trips-editor-fix' );
}
add_action( 'wp_enqueue_scripts', 'wtwidget_enqueue_scripts' );

/**
 * Get the appropriate CDN URL based on environment
 *
 * @param string $env The environment URL (e.g., 'https://pre.wetravel.to').
 * @return string The corresponding CDN URL
 */
function wtwidget_get_cdn_url( $env ) {
	// Remove protocol and trailing slashes.
	$clean_env = rtrim( preg_replace( '#^https?://#', '', $env ), '/' );

	// Map environments to their CDN domains.
	switch ( $clean_env ) {
		case 'wetravel.com':
		case 'www.wetravel.com':
			return 'https://cdn.wetravel.com';

		case 'demo.wetravel.to':
			return 'https://demo.cdn.wetravel.com';

		case 'pre.wetravel.to':
			return 'https://pre.cdn.wetravel.to';

		default:
			// For any subdomain of wetravel.com, use the main CDN
			if (strpos($clean_env, 'wetravel.com') !== false) {
				return 'https://cdn.wetravel.com';
			}
			// For any subdomain of demo.wetravel.to, use demo CDN
			if (strpos($clean_env, 'demo.wetravel.to') !== false) {
				return 'https://demo.cdn.wetravel.com';
			}
			// For any subdomain of pre.wetravel.to, use pre CDN
			if (strpos($clean_env, 'pre.wetravel.to') !== false) {
				return 'https://pre.cdn.wetravel.to';
			}
			// For staging environments (stage.wetravel.to), use staging CDN
			if (strpos($clean_env, 'stage.wetravel.to') !== false) {
				$domain_parts = explode('.', $clean_env);
				if (count($domain_parts) >= 3) {
					$subdomain = $domain_parts[0];
					return "https://{$subdomain}.cdn.wetravel.to";
				}
			}

			// For custom domains, follow the pattern from the embed script.
			$domain_parts = explode( '.', $clean_env );
			if ( count( $domain_parts ) >= 2 ) {
				return 'https://cdn.' . implode( '.', array_slice( $domain_parts, -2 ) );
			}
			// Fallback to pre environment.
			return 'https://pre.cdn.wetravel.to';
	}
}

/**
 * Check if WeTravel widgets are being used in any posts or pages
 *
 * @return array Array containing usage information
 */
function wtwidget_check_widget_usage() {
	// Try to get cached results first
	$cache_key = 'wetravel_widget_usage';
	$usage = wp_cache_get($cache_key);

	if (false === $usage) {
		$usage = array(
			'has_usage' => false,
			'blocks' => array(),
			'shortcodes' => array()
		);

		// Get all possible post statuses for comprehensive search
		$post_statuses = array('publish', 'draft', 'private', 'future', 'pending');

		// Include specific post types that might contain widgets
		$post_types = array('post', 'page', 'wp_template', 'wp_template_part', 'wp_block');

		// Get all available post types to ensure we don't miss any
		$all_post_types = get_post_types(array('public' => true));
		$post_types = array_merge($post_types, array_keys($all_post_types));
		$post_types = array_unique($post_types);

		// Check for Gutenberg blocks using direct content search
		global $wpdb;

		// Search for blocks in post_content directly
		$block_query = $wpdb->prepare("
			SELECT ID, post_title, post_type, post_content
			FROM {$wpdb->posts}
			WHERE post_content LIKE %s
			AND post_status IN ('" . implode("','", array_map('esc_sql', $post_statuses)) . "')
			AND post_type IN ('" . implode("','", array_map('esc_sql', $post_types)) . "')
		", '%<!-- wp:wetravel-trips/block%');

		$block_results = $wpdb->get_results($block_query);

		if (!empty($block_results)) {
			foreach ($block_results as $post) {
				// Check if this is actual widget usage or just design storage
				$is_actual_usage = wtwidget_is_actual_widget_usage($post->post_content, $post->post_type);

				if ($is_actual_usage) {
					$usage['has_usage'] = true;
					$edit_url = get_edit_post_link($post->ID);
					// For FSE templates, use site editor URL
					if ($post->post_type === 'wp_template' || $post->post_type === 'wp_template_part') {
						$edit_url = admin_url('site-editor.php?postId=' . $post->ID . '&postType=' . $post->post_type);
					}

					$usage['blocks'][] = array(
						'id' => $post->ID,
						'title' => !empty($post->post_title) ? $post->post_title : 'Template: ' . $post->ID,
						'type' => $post->post_type,
						'edit_url' => $edit_url
					);
				}
			}
		}

		// Search for shortcodes in post_content directly
		$shortcode_query = $wpdb->prepare("
			SELECT ID, post_title, post_type, post_content
			FROM {$wpdb->posts}
			WHERE post_content LIKE %s
			AND post_status IN ('" . implode("','", array_map('esc_sql', $post_statuses)) . "')
			AND post_type IN ('" . implode("','", array_map('esc_sql', $post_types)) . "')
		", '%[wetravel_trips%');

		$shortcode_results = $wpdb->get_results($shortcode_query);

		if (!empty($shortcode_results)) {
			$usage['has_usage'] = true;
			foreach ($shortcode_results as $post) {
				// Skip if this post was already added as a block to avoid duplicates
				$already_added = false;
				foreach ($usage['blocks'] as $block_post) {
					if ($block_post['id'] === $post->ID) {
						$already_added = true;
						break;
					}
				}

				if (!$already_added) {
					$edit_url = get_edit_post_link($post->ID);
					// For FSE templates, use site editor URL
					if ($post->post_type === 'wp_template' || $post->post_type === 'wp_template_part') {
						$edit_url = admin_url('site-editor.php?postId=' . $post->ID . '&postType=' . $post->post_type);
					}

					$usage['shortcodes'][] = array(
						'id' => $post->ID,
						'title' => !empty($post->post_title) ? $post->post_title : 'Template: ' . $post->ID,
						'type' => $post->post_type,
						'edit_url' => $edit_url
					);
				}
			}
		}

		// Cache the results for 1 hour
		wp_cache_set($cache_key, $usage, '', HOUR_IN_SECONDS);
	}

	return $usage;
}

/**
 * Determine if block content represents actual widget usage vs design storage
 *
 * @param string $content Post content to check
 * @param string $post_type Type of post being checked
 * @return bool True if actual widget usage, false if just design storage
 */
function wtwidget_is_actual_widget_usage($content, $post_type) {
	// Extract all WeTravel block instances from content
	if (preg_match_all('/<!-- wp:wetravel-trips\/block\s+({.*?})\s*(?:\/-->|-->)/s', $content, $matches)) {
		foreach ($matches[1] as $json_str) {
			// Clean up the JSON string
			$json_str = trim($json_str);

			// Try to decode the JSON attributes
			$attributes = json_decode($json_str, true);

			if (is_array($attributes)) {
				// Check for design storage indicators
				$has_designs_object = isset($attributes['designs']) && is_array($attributes['designs']);
				$designs_count = $has_designs_object ? count($attributes['designs']) : 0;

				// Check for actual widget configuration - these indicate active widget usage
				$has_widget_config = (
					isset($attributes['widget']) || // Has widget identifier
					isset($attributes['selectedDesign']) || // Has selected design
					isset($attributes['displayType']) || // Has display configuration
					isset($attributes['itemsPerPage']) || // Has pagination
					isset($attributes['itemsPerRow']) || // Has grid configuration
					isset($attributes['itemsPerSlide']) // Has carousel configuration
				);

				// For template parts, be more strict about what constitutes design storage
				if ($post_type === 'wp_template_part' || $post_type === 'wp_template') {
					// If it has designs object but no widget config, it's likely design storage
					if ($has_designs_object && !$has_widget_config) {
						continue; // Skip this block, it's design storage
					}
				}

				// If we find any block that looks like actual usage, return true
				if ($has_widget_config) {
					return true;
				}

				// For non-template posts, any block without designs object is likely usage
				if (!($post_type === 'wp_template_part' || $post_type === 'wp_template') && !$has_designs_object) {
					return true;
				}
			} else {
				// If we can't parse JSON but block exists, check for design storage patterns
				if (($post_type === 'wp_template_part' || $post_type === 'wp_template') &&
					strpos($json_str, '"designs":{') !== false) {
					continue; // Skip what appears to be design storage
				}
				return true; // Conservative: assume it's usage if we can't determine otherwise
			}
		}
	}

	return false; // No actual widget usage found
}

/**
 * Clear widget usage cache - useful for testing or when content changes
 */
function wtwidget_clear_usage_cache() {
	wp_cache_delete('wetravel_widget_usage');
}

/**
 * Generate shortcode with appropriate parameters based on display type
 *
 * @param array $design Design data
 * @param string $design_id Design ID
 * @return string Generated shortcode
 */
function wtwidget_generate_shortcode_with_params($design, $design_id) {
	$widget_identifier = !empty($design['keyword']) ? $design['keyword'] : $design_id;
	$shortcode = '[wetravel_trips widget="' . esc_attr($widget_identifier) . '"';

	// Add all relevant attributes from the current design
	$display_type = isset($design['displayType']) ? $design['displayType'] : 'vertical';

	// Always include border radius with fallback to global setting
	$border_radius = isset($design['borderRadius']) ? $design['borderRadius'] : get_option('wetravel_trips_border_radius', 6);
	$shortcode .= ' border_radius="' . intval($border_radius) . '"';

	if ($display_type === 'carousel') {
		// Carousel: items_per_slide
		$items_per_slide = isset($design['itemsPerSlide']) ? $design['itemsPerSlide'] : get_option('wetravel_trips_items_per_slide', 3);
		$shortcode .= ' items_per_slide="' . intval($items_per_slide) . '"';
	} elseif ($display_type === 'grid') {
		// Grid: items_per_row, items_per_page
		$items_per_row = isset($design['itemsPerRow']) ? $design['itemsPerRow'] : get_option('wetravel_trips_items_per_row', 3);
		$items_per_page = isset($design['itemsPerPage']) ? $design['itemsPerPage'] : get_option('wetravel_trips_items_per_page', 10);
		$shortcode .= ' items_per_row="' . intval($items_per_row) . '"';
		$shortcode .= ' items_per_page="' . intval($items_per_page) . '"';
	} else {
		// Vertical: items_per_page
		$items_per_page = isset($design['itemsPerPage']) ? $design['itemsPerPage'] : get_option('wetravel_trips_items_per_page', 10);
		$shortcode .= ' items_per_page="' . intval($items_per_page) . '"';
	}

	$shortcode .= ']';
	return $shortcode;
}

/**
 * Update existing designs to include wtWidgetType field for backward compatibility
 * This function should be called once to migrate existing designs
 */
function wtwidget_update_existing_designs_with_wt_widget_type() {
	$designs = get_option( 'wetravel_trips_designs', array() );
	$updated = false;

	foreach ( $designs as $design_id => $design ) {
		// Check if design already has wtWidgetType field
		if ( ! isset( $design['wtWidgetType'] ) ) {
			// Add default wtWidgetType for legacy designs
			$designs[ $design_id ]['wtWidgetType'] = 'all-trips';
			$updated = true;
		}
	}

	// Save updated designs if any changes were made
	if ( $updated ) {
		update_option( 'wetravel_trips_designs', $designs );
	}

	return $updated;
}

/**
 * Hook to run the design update on plugin activation or admin init
 */
add_action( 'admin_init', 'wtwidget_update_existing_designs_with_wt_widget_type' );


