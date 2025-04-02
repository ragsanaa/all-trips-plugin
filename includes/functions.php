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
function wetravel_trips_extract_settings( $embed_code ) {
	preg_match( '/src="([^"]+)"/', $embed_code, $src_match );
	preg_match( '/data-slug="([^"]+)"/', $embed_code, $slug_match );
	preg_match( '/data-env="([^"]+)"/', $embed_code, $env_match );
	preg_match( '/data-uid="([^"]+)"/', $embed_code, $wetravel_trips_user_id_match );

	return array(
		'src'  => isset( $src_match[1] ) ? $src_match[1] : '',
		'slug' => isset( $slug_match[1] ) ? $slug_match[1] : '',
		'env'  => isset( $env_match[1] ) ? $env_match[1] : '',
		'wetravel_trips_user_id' => isset( $wetravel_trips_user_id_match[1] ) ? $wetravel_trips_user_id_match[1] : '',
	);
}

/** Hook into 'admin_init' to process the settings update. */
function wetravel_trips_save_embed_code() {
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
				'data-uid'     => array(),
				'data-color'   => array(),
				'data-text'    => array(),
				'data-name'    => array(),
			),
		);

		$new_embed_code = wp_kses( wp_unslash( $_POST['wetravel_trips_embed_code'] ), $allowed_html );
		update_option( 'wetravel_trips_embed_code', $new_embed_code );

		// Extract and save the details.
		$extracted_values = wetravel_trips_extract_settings( $new_embed_code );
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
add_action( 'admin_init', 'wetravel_trips_save_embed_code' );

/** Register AJAX endpoint for keyword validation. */
function wetravel_trips_register_ajax() {
	add_action( 'wp_ajax_check_keyword_unique', 'wetravel_trips_check_keyword_unique' );
}
add_action( 'init', 'wetravel_trips_register_ajax' );

/** AJAX callback to check keyword uniqueness. */
function wetravel_trips_check_keyword_unique() {
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

/** Add this function to your plugin file or functions.php. */
function fix_trips_loading_in_editor() {
	?>
	<script>
	jQuery(document).ready(function($) {
		// Check if we're in any editing environment.
		var isEditMode = (
			// Generic ways to detect edit mode across different page builders.
			window.parent && window.parent !== window ||
			window.frames && window.frames.length > 0 ||
			document.body.classList.contains('editor-body') ||
			document.body.classList.contains('wp-admin') ||
			document.body.classList.contains('edit-php') ||
			(window.location.href && window.location.href.indexOf('action=edit') > -1)
		);

		if (isEditMode) {
			// Force reload trips in any editor.
			setTimeout(function() {
				$(".wetravel-trips-container").each(function() {
					var container = $(this);
					// Clear any existing content.
					container.find(".wetravel-trips-loading").show();
					// Reload trips.
					if (typeof loadTrips === 'function') {
						loadTrips(container);
					}
				});
			}, 1000); // Wait for everything to load.
		}
	});
	</script>
	<?php
}
add_action( 'wp_footer', 'fix_trips_loading_in_editor' );
