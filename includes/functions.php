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
		update_option( 'wetravel_trips_last_saved', wp_date( 'F j, Y \a\t g:i a' ) );

		// Redirect to prevent resubmission.
		wp_safe_redirect( add_query_arg( 'saved', 'true', admin_url( 'admin.php?page=wetravel-trips-settings' ) ) );
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
 * Fix trips loading in editor
 */
function wtwidget_fix_trips_loading_in_editor() {
	// ... existing code ...
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

		// Check for Gutenberg blocks
		$block_posts = get_posts(array(
			'post_type' => 'any',
			'post_status' => 'publish',
			'posts_per_page' => -1,
			's' => '<!-- wp:wetravel-trips/block'
		));

		if (!empty($block_posts)) {
			$usage['has_usage'] = true;
			foreach ($block_posts as $post) {
				$usage['blocks'][] = array(
					'id' => $post->ID,
					'title' => $post->post_title,
					'type' => $post->post_type,
					'edit_url' => get_edit_post_link($post->ID)
				);
			}
		}

		// Check for shortcodes
		$shortcode_posts = get_posts(array(
			'post_type' => 'any',
			'post_status' => 'publish',
			'posts_per_page' => -1,
			's' => '[wetravel_trips'
		));

		if (!empty($shortcode_posts)) {
			$usage['has_usage'] = true;
			foreach ($shortcode_posts as $post) {
				$usage['shortcodes'][] = array(
					'id' => $post->ID,
					'title' => $post->post_title,
					'type' => $post->post_type,
					'edit_url' => get_edit_post_link($post->ID)
				);
			}
		}

		// Cache the results for 1 hour
		wp_cache_set($cache_key, $usage, '', HOUR_IN_SECONDS);
	}

	return $usage;
}
