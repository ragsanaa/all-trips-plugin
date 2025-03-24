<?php
/**
 * Shortcode functionality for All Trips Plugin
 *
 * @package WordPress
 */

// Exit if accessed directly..
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add shortcode support for All Trips
 *
 * @param array $atts Shortcode attributes.
 * @return string Rendered HTML
 */
function all_trips_shortcode( $atts ) {
	// Define default attributes.
	$default_atts = array(
		'design'         => '',  // Design ID or keyword.
		'slug'           => get_option( 'all_trips_slug', '' ),
		'env'            => get_option( 'all_trips_env', 'https://pre.wetravel.to' ),
		'display_type'   => get_option( 'all_trips_display_type', 'vertical' ),
		'button_type'    => get_option( 'all_trips_button_type', 'book_now' ),
		'button_text'    => '',
		'button_color'   => get_option( 'all_trips_button_color', '#33ae3f' ),
		'items_per_page' => get_option( 'all_trips_items_per_page', 10 ),
		'load_more_text' => get_option( 'all_trips_load_more_text', 'Load More' ),
		'trip_type'      => 'all',
		'date_start'     => '',
		'date_end'       => '',
	);

	// Parse incoming attributes into an array and merge it with defaults.
	$atts = shortcode_atts( $default_atts, $atts, 'all_trips' );

	// Convert to block attributes format.
	$block_atts = array(
		'slug'         => $atts['slug'],
		'env'          => $atts['env'],
		'displayType'  => $atts['display_type'],
		'buttonType'   => $atts['button_type'],
		'buttonText'   => $atts['button_text'],
		'buttonColor'  => $atts['button_color'],
		'itemsPerPage' => intval( $atts['items_per_page'] ),
		'loadMoreText' => $atts['load_more_text'],
		'tripType'     => $atts['trip_type'],
		'dateStart'    => $atts['date_start'],
		'dateEnd'      => $atts['date_end'],
	);

	// Check if using a design configuration.
	if ( ! empty( $atts['design'] ) ) {
		$block_atts['selectedDesignID'] = $atts['design'];

		// Get all designs.
		$designs   = get_option( 'all_trips_designs', array() );
		$design_id = $atts['design'];
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
	if ( function_exists( 'all_trips_block_render' ) ) {
		return all_trips_block_render( $block_atts );
	} else {
		// Fallback if block render function doesn't exist.
		return render_all_trips_fallback( $block_atts );
	}
}
add_shortcode( 'all_trips', 'all_trips_shortcode' );

/**
 * Fallback render function in case block render function doesn't exist
 *
 * @param array $atts Trip display attributes.
 * @return string Rendered HTML
 */
function render_all_trips_fallback( $atts ) {
	// Generate a unique ID for this shortcode instance.
	$block_id = wp_unique_id( 'wetravel-' );

	// Create a nonce for AJAX security.
	$nonce = wp_create_nonce( 'all_trips_nonce' );

	// Clean up the environment URL if needed.
	$env = rtrim( $atts['env'], '/' );

	// Set default buttonText based on buttonType if not provided.
	if ( empty( $atts['buttonText'] ) ) {
		$atts['buttonText'] = 'book_now' === $atts['buttonType'] ? 'Book Now' : 'View Trip';
	}

	// Ensure trips-loader.js is enqueued.
	wp_enqueue_script(
		'all-trips-loader',
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
			'all-trips-carousel',
			plugins_url( 'assets/js/carousel.js', __DIR__ ),
			array( 'jquery', 'swiper-js' ),
			filemtime( plugin_dir_path( __DIR__ ) . 'assets/js/carousel.js' ),
			true
		);
	} else {
		// Always enqueue pagination script for grid and vertical views.
		wp_enqueue_script(
			'all-trips-pagination',
			plugins_url( 'assets/js/pagination.js', __DIR__ ),
			array( 'jquery' ),
			filemtime( plugin_dir_path( __DIR__ ) . 'assets/js/pagination.js' ),
			true
		);
	}

	// Localize the script to provide the AJAX URL.
	wp_localize_script(
		'all-trips-loader',
		'allTripsData',
		array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => $nonce,
		)
	);

	// Add dynamic CSS for this instance.
	$custom_css = "
        /* Set dynamic CSS variables for this block instance */
        #trips-container-{$block_id} {
            --button-color: {$atts['buttonColor']};
        }

        /* Carousel styling for this specific instance */
        #trips-container-{$block_id}.carousel-view .swiper {
            padding: 0 40px;
            position: relative;
        }

        #trips-container-{$block_id}.carousel-view .swiper-button-next,
        #trips-container-{$block_id}.carousel-view .swiper-button-prev {
            top: 50%;
            transform: translateY(-50%);
            width: 40px;
            height: 40px;
            background-color: var(--button-color, #6a3bff);
            border-radius: 50%;
            color: white;
        }

        #trips-container-{$block_id}.carousel-view .swiper-button-next {
            right: 0;
        }

        #trips-container-{$block_id}.carousel-view .swiper-button-prev {
            left: 0;
        }

        #trips-container-{$block_id}.carousel-view .swiper-button-next:after,
        #trips-container-{$block_id}.carousel-view .swiper-button-prev:after {
            font-size: 18px;
            font-weight: bold;
        }

        #trips-container-{$block_id}.carousel-view .swiper-pagination {
            position: relative;
            margin-top: 20px;
        }

        #trips-container-{$block_id}.carousel-view .swiper-pagination-bullet {
            width: 12px;
            height: 12px;
            margin: 0 5px;
        }

        #trips-container-{$block_id}.carousel-view .swiper-pagination-bullet-active {
            background-color: var(--button-color, #6a3bff);
        }
    ";

	// Enqueue or add inline styles.
	if ( wp_style_is( 'all-trips-styles', 'registered' ) ) {
		wp_add_inline_style( 'all-trips-styles', $custom_css );
	} else {
		echo '<style>' . esc_attr( $custom_css ) . '</style>';
	}

	// Build the output HTML.
	ob_start();
	?>
	<div class="wp-block-all-trips-block">
		<div class="all-trips-container <?php echo esc_attr( $atts['displayType'] ); ?>-view"
			id="trips-container-<?php echo esc_attr( $block_id ); ?>"
			data-slug="<?php echo esc_attr( $atts['slug'] ); ?>"
			data-env="<?php echo esc_attr( $env ); ?>"
			data-nonce="<?php echo esc_attr( $nonce ); ?>"
			data-items-per-page="<?php echo esc_attr( $atts['itemsPerPage'] ); ?>"
			data-display-type="<?php echo esc_attr( $atts['displayType'] ); ?>"
			data-button-type="<?php echo esc_attr( $atts['buttonType'] ); ?>"
			data-button-text="<?php echo esc_attr( $atts['buttonText'] ); ?>"
			data-button-color="<?php echo esc_attr( $atts['buttonColor'] ); ?>"
			data-trip-type="<?php echo esc_attr( $atts['tripType'] ); ?>"
			<?php if ( ! empty( $atts['dateStart'] ) ) : ?>
			data-date-start="<?php echo esc_attr( $atts['dateStart'] ); ?>"
			<?php endif; ?>
			<?php if ( ! empty( $atts['dateEnd'] ) ) : ?>
			data-date-end="<?php echo esc_attr( $atts['dateEnd'] ); ?>"
			<?php endif; ?>
			<?php if ( ! empty( $atts['selectedDesignID'] ) ) : ?>
			data-design="<?php echo esc_attr( $atts['selectedDesignID'] ); ?>"
			<?php endif; ?>>

			<!-- Loading indicator -->
			<div class="all-trips-loading">
				<div class="loading-spinner"></div>
				<p>Loading trips...</p>
			</div>
		</div>

		<?php if ( 'carousel' !== $atts['displayType'] ) : ?>
			<!-- Numbered pagination container - will be populated by JavaScript -->
			<div id="pagination-<?php echo esc_attr( $block_id ); ?>" class="all-trips-pagination"></div>
		<?php endif; ?>
	</div>
	<?php

	// Add inline script to ensure the container structure matches what trips-loader.js expects.
	$inline_script = "
    <script>
    jQuery(document).ready(function($) {
        // Make sure the container is properly initialized.
        $('#trips-container-{$block_id}').each(function() {
            var container = $(this);
            if (typeof window.loadTrips === 'function') {
                window.loadTrips(container);
            } else {
                // If loadTrips isn't available yet, wait for it.
                $(document).on('tripsLoaderReady', function() {
                    window.loadTrips(container);
                });
            }
        });
    });
    </script>
    ";

	return ob_get_clean() . $inline_script;
}

/**
 * Add an event to notify when the trips loader script is ready
 * to handle cases where the shortcode is processed before the script is loaded
 */
function all_trips_loader_ready() {
	$inline_script = "
    <script>
    jQuery(document).ready(function($) {
        // Notify that the trips loader is ready.
        $(document).trigger('tripsLoaderReady');
    });
    </script>
    ";
	echo esc_js( $inline_script );
}
add_action( 'wp_footer', 'all_trips_loader_ready', 100 );
