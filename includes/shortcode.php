<?php
/**
 * Shortcode functionality for WeTravel Trips Plugin
 *
 * @package WordPress
 */

// Exit if accessed directly..
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add shortcode support for WeTravel Trips
 *
 * @param array $atts Shortcode attributes.
 * @return string Rendered HTML
 */
function wetravel_trips_shortcode( $atts ) {
	// Define default attributes.
	$default_atts = array(
		'widget'         => '',  // Widget ID or keyword.
		'slug'           => get_option( 'wetravel_trips_slug', '' ),
		'env'            => get_option( 'wetravel_trips_env', 'https://pre.wetravel.to' ),
		'wetravel_trips_user_id' => get_option( 'wetravel_trips_user_id', '' ),
		'display_type'   => get_option( 'wetravel_trips_display_type', 'vertical' ),
		'button_type'    => get_option( 'wetravel_trips_button_type', 'book_now' ),
		'button_text'    => '',
		'button_color'   => get_option( 'wetravel_trips_button_color', '#33ae3f' ),
		'items_per_page' => get_option( 'wetravel_trips_items_per_page', 10 ),
		'items_per_row'  => get_option( 'wetravel_trips_items_per_row', 3 ),
		'items_per_slide' => get_option( 'wetravel_trips_items_per_slide', 3 ),
		'load_more_text' => get_option( 'wetravel_trips_load_more_text', 'Load More' ),
		'trip_type'      => 'all',
		'date_start'     => '',
		'date_end'       => '',
	);

	// Parse incoming attributes into an array and merge it with defaults.
	$atts = shortcode_atts( $default_atts, $atts, 'wetravel_trips' );

	// Convert to block attributes format.
	$block_atts = array(
		'slug'         => $atts['slug'],
		'env'          => $atts['env'],
		'wetravelUserID' => $atts['wetravel_trips_user_id'],
		'displayType'  => $atts['display_type'],
		'buttonType'   => $atts['button_type'],
		'buttonText'   => $atts['button_text'],
		'buttonColor'  => $atts['button_color'],
		'itemsPerPage' => intval( $atts['items_per_page'] ),
		'itemsPerRow'  => intval( $atts['items_per_row'] ),
		'itemsPerSlide' => intval( $atts['items_per_slide'] ),
		'loadMoreText' => $atts['load_more_text'],
		'tripType'     => $atts['trip_type'],
		'dateStart'    => $atts['date_start'],
		'dateEnd'      => $atts['date_end'],
	);

	// Check if using a design configuration.
	if ( ! empty( $atts['widget']  ) ) {
		$block_atts['selectedDesignID'] = $atts['widget'] ;

		// Get all designs.
		$designs   = get_option( 'wetravel_trips_designs', array() );
		$design_id = $atts['widget'] ;
		$design    = null;

		// First try to find design by keyword.
		foreach ( $designs as $id => $design_data ) {
			if ( isset( $design_data['keyword'] ) && $design_data['keyword'] === $design_id ) {
				$design                         = $design_data;
				$block_atts['selectedDesignID'] = $id; // Use the actual ID.
				break;
			}
		}

		// If not found by keyword, try to find by design ID.
		if ( null === $design && isset( $designs[ $design_id ] ) ) {
			$design = $designs[ $design_id ];
		}

		// Apply design parameters to block attributes if needed.
		if ( $design ) {
			// Map design parameters to block attributes.
			if ( ! empty( $design['displayType'] ) ) {
				$block_atts['displayType'] = $design['displayType'];
			}
			if ( ! empty( $design['buttonType'] ) ) {
				$block_atts['buttonType'] = $design['buttonType'];
			}
			if ( ! empty( $design['buttonText'] ) ) {
				$block_atts['buttonText'] = $design['buttonText'];
			}
			if ( ! empty( $design['buttonColor'] ) ) {
				$block_atts['buttonColor'] = $design['buttonColor'];
			}
			if ( ! empty( $design['tripType'] ) ) {
				$block_atts['tripType'] = $design['tripType'];
			}

			// Handle date range.
			if ( ! empty( $design['dateRangeStart'] ) ) {
				$block_atts['dateStart'] = $design['dateRangeStart'];
			}
			if ( ! empty( $design['dateRangeEnd'] ) ) {
				$block_atts['dateEnd'] = $design['dateRangeEnd'];
			}
		}
	}

	// Use the existing block render function to maintain consistency.
	if ( function_exists( 'wetravel_trips_block_render' ) ) {
		return wetravel_trips_block_render( $block_atts );
	} else {
		// Fallback if block render function doesn't exist.
		return render_wetravel_trips_fallback( $block_atts );
	}
}
add_shortcode( 'wetravel_trips', 'wetravel_trips_shortcode' );

/**
 * Fallback render function in case block render function doesn't exist
 *
 * @param array $atts Trip display attributes.
 * @return string Rendered HTML
 */
function render_wetravel_trips_fallback( $atts ) {
	// Generate a unique ID for this shortcode instance.
	$block_id = wp_unique_id( 'wetravel-' );

	// Create a nonce for AJAX security.
	$nonce = wp_create_nonce( 'wetravel_trips_nonce' );

	// Clean up the environment URL if needed.
	$env = rtrim( $atts['env'], '/' );

	// Set default buttonText based on buttonType if not provided.
	if ( empty( $atts['buttonText'] ) ) {
		$atts['buttonText'] = 'book_now' === $atts['buttonType'] ? 'Book Now' : 'View Trip';
	}

	// Ensure trips-loader.js is enqueued.
	wp_enqueue_script(
		'wetravel-trips-loader',
		plugins_url( 'assets/js/trips-loader.js', __DIR__ ),
		array( 'jquery' ),
		filemtime( plugin_dir_path( __DIR__ ) . 'assets/js/trips-loader.js' ),
		true
	);

	// Enqueue necessary assets based on display type.
	if ( 'carousel' === $atts['displayType'] ) {
		wp_enqueue_style(
			'swiper-css',
			plugin_dir_url( __DIR__ ) . 'assets/css/swiper-bundle.min.css',
			array(),
			filemtime( plugin_dir_path( __DIR__ ) . 'assets/css/swiper-bundle.min.css' )
		);
		wp_enqueue_script(
			'swiper-js',
			plugin_dir_url( __DIR__ ) . 'assets/js/swiper-bundle.min.js',
			array(),
			filemtime( plugin_dir_path( __DIR__ ) . 'assets/js/swiper-bundle.min.js' ),
			true
		);
		wp_enqueue_script(
			'wetravel-trips-carousel',
			plugins_url( 'assets/js/carousel.js', __DIR__ ),
			array( 'jquery', 'swiper-js' ),
			filemtime( plugin_dir_path( __DIR__ ) . 'assets/js/carousel.js' ),
			true
		);
	} else {
		// Always enqueue pagination script for grid and vertical views.
		wp_enqueue_script(
			'wetravel-trips-pagination',
			plugins_url( 'assets/js/pagination.js', __DIR__ ),
			array( 'jquery' ),
			filemtime( plugin_dir_path( __DIR__ ) . 'assets/js/pagination.js' ),
			true
		);
	}

	// Localize the script to provide the AJAX URL.
	wp_localize_script(
		'wetravel-trips-loader',
		'wetravelTripsData',
		array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => $nonce,
		)
	);

	// Add dynamic CSS for this instance.
	$dynamic_css = "
		#trips-container-{$block_id} {
			--button-color: {$atts['button_color']};
			--items-per-row: {$atts['items_per_row']};
		}
	";

	// Output container with loading spinner
	ob_start();
	?>
	<style><?php echo wp_strip_all_tags( $dynamic_css ); ?></style>
	<div id="trips-container-<?php echo esc_attr( $block_id ); ?>"
		class="wetravel-trips-container <?php echo esc_attr( $atts['display_type'] ); ?>-view"
		data-nonce="<?php echo esc_attr( $nonce ); ?>"
		data-slug="<?php echo esc_attr( $atts['slug'] ); ?>"
		data-env="<?php echo esc_attr( $env ); ?>"
		data-wetravel-user-id="<?php echo esc_attr( $atts['wetravel_trips_user_id'] ); ?>"
		data-display-type="<?php echo esc_attr( $atts['display_type'] ); ?>"
		data-button-type="<?php echo esc_attr( $atts['button_type'] ); ?>"
		data-button-text="<?php echo esc_attr( $atts['button_text'] ); ?>"
		data-button-color="<?php echo esc_attr( $atts['button_color'] ); ?>"
		data-items-per-page="<?php echo esc_attr( $atts['items_per_page'] ); ?>"
		data-items-per-row="<?php echo esc_attr( $atts['items_per_row'] ); ?>"
		data-items-per-slide="<?php echo esc_attr( $atts['items_per_slide'] ); ?>"
		data-trip-type="<?php echo esc_attr( $atts['trip_type'] ); ?>"
		data-date-start="<?php echo esc_attr( $atts['date_start'] ); ?>"
		data-date-end="<?php echo esc_attr( $atts['date_end'] ); ?>">
		<div id="loading-<?php echo esc_attr( $block_id ); ?>" class="wetravel-trips-loading">
			<div class="loading-spinner"></div>
			<p>Loading trips...</p>
		</div>
	</div>

	<?php if ( $atts['button_type'] === 'book_now' ) : ?>
	<script src="<?php echo esc_url(wetravel_trips_get_cdn_url($env) . '/widgets/embed_checkout.js'); ?>"></script>
	<?php endif; ?>

	<?php
	$output = ob_get_clean();

	// Add inline script for initialization
	$inline_script = "
	<script>
	jQuery(document).ready(function($) {
			// Notify that the trips loader is ready.
			$(document).trigger('tripsLoaderReady');
	});
	</script>
	";

	return $output . $inline_script;
}

/**
 * Add an event to notify when the trips loader script is ready
 * to handle cases where the shortcode is processed before the script is loaded
 */
function wetravel_trips_loader_ready() {
	?>
	<script>
	jQuery(document).ready(function($) {
			// Notify that the trips loader is ready.
			$(document).trigger('tripsLoaderReady');
	});
	</script>
	<?php
}
add_action( 'wp_footer', 'wetravel_trips_loader_ready', 100 );
