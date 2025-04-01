<?php
/**
 * Block Renderer
 *
 * Dynamically render widgets.
 *
 * @package WordPress
 */

/**
 * Render callback for dynamic block.
 *
 * @param array $attributes Give all settings and designs details.
 */
function wetravel_trips_block_render( $attributes ) {
	// Generate a unique ID for this block instance.
	$block_id = wp_unique_id( 'wetravel-' );

	// Check if there's a selected design and apply its settings.
	$designs            = get_option( 'wetravel_trips_designs', array() );
	$selected_design_id = isset( $attributes['selectedDesignID'] ) ? $attributes['selectedDesignID'] : '';

	// Start with block attributes.
	$src            = $attributes['src'] ?? get_option( 'wetravel_trips_src', '' );
	$slug           = $attributes['slug'] ?? get_option( 'wetravel_trips_slug', '' );
	$env            = $attributes['env'] ?? get_option( 'wetravel_trips_env', 'https://pre.wetravel.to' );
	$display_type   = $attributes['displayType'] ?? get_option( 'wetravel_trips_display_type', 'vertical' );
	$button_type    = $attributes['buttonType'] ?? get_option( 'wetravel_trips_button_type', 'book_now' );
	$button_color   = $attributes['buttonColor'] ?? get_option( 'wetravel_trips_button_color', '#33ae3f' );
	$items_per_page = intval( $attributes['itemsPerPage'] ?? get_option( 'wetravel_trips_items_per_page', 10 ) );
	$items_per_row  = intval( $attributes['itemsPerRow'] ?? get_option( 'wetravel_trips_items_per_row', 3 ) );
	$items_per_slide = intval( $attributes['itemsPerSlide'] ?? get_option( 'wetravel_trips_items_per_slide', 3 ) );
	$load_more_text = $attributes['loadMoreText'] ?? get_option( 'wetravel_trips_load_more_text', 'Load More' );

	// Override with design settings if a design is selected.
	if ( ! empty( $selected_design_id ) ) {
		// Handle both array and object format for designs.
		$design = null;

		if ( isset( $designs[ $selected_design_id ] ) ) {
			// Object format.
			$design = $designs[ $selected_design_id ];
		} else {
			// Array format - find by ID.
			foreach ( $designs as $d ) {
				if ( isset( $d['id'] ) && $d['id'] === $selected_design_id ) {
					$design = $d;
					break;
				}
			}
		}

		// Apply design settings, keeping block attributes as fallbacks.
		if ( $design ) {
			$display_type = isset( $design['displayType'] ) ? $design['displayType'] : $display_type;
			$button_type  = isset( $design['buttonType'] ) ? $design['buttonType'] : $button_type;
			$button_color = isset( $design['buttonColor'] ) ? $design['buttonColor'] : $button_color;

			// If the design has custom CSS, we'll add it later.
			$custom_css_design = isset( $design['customCSS'] ) ? $design['customCSS'] : '';

			// Check for buttonText in design.
			if ( ! empty( $design['buttonText'] ) ) {
				$button_text = $design['buttonText'];
			}
		}
	}

	// Set default buttonText based on buttonType if not provided.
	$default_button_text = 'book_now' === $button_type ? 'Book Now' : 'View Trip';
	$button_text         = ! empty( $attributes['buttonText'] ) ? $attributes['buttonText'] : $default_button_text;

	// If design has buttonText, override the default.
	if ( ! empty( $selected_design_id ) && isset( $designs[ $selected_design_id ]['buttonText'] ) ) {
		$button_text = $designs[ $selected_design_id ]['buttonText'];
	}

	// Clean up the environment URL if needed.
	$env = rtrim( $env, '/' );

	// Create a nonce for AJAX security.
	$nonce = wp_create_nonce( 'wetravel_trips_nonce' );

	// Enqueue necessary assets based on display type.
	if ( 'carousel' === $display_type ) {
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
			filemtime( WETRAVEL_TRIPS_PLUGIN_DIR . 'assets/js/carousel.js' ),
			true
		);
	} else {
		// Always enqueue pagination script for grid and vertical views.
		wp_enqueue_script(
			'wetravel-trips-pagination',
			plugins_url( 'assets/js/pagination.js', __DIR__ ),
			array( 'jquery' ),
			filemtime( WETRAVEL_TRIPS_PLUGIN_DIR . 'assets/js/pagination.js' ),
			true
		);
	}

	// Only add dynamic CSS that depends on block attributes.
	$custom_css = "
    /* Set dynamic CSS variables for this block instance */
    #trips-container-{$block_id} {
      --button-color: {$button_color};
			--items-per-row: {$items_per_row};
    }
  ";

	// Add design-specific custom CSS if available.
	if ( ! empty( $custom_css_design ) ) {
		$custom_css .= "\n/* Design-specific custom CSS */\n{$custom_css_design}";
	}

	wp_register_style(
		'wetravel-trips-styles',
		WETRAVEL_TRIPS_PLUGIN_URL . 'assets/css/wetravel-trips.css',
		array(),
		filemtime( WETRAVEL_TRIPS_PLUGIN_DIR . 'assets/css/wetravel-trips.css' )
	);

	wp_enqueue_style( 'wetravel-trips-styles' );

	// Output custom styles.
	wp_add_inline_style( 'wetravel-trips-styles', $custom_css );

	ob_start();
	?>
	<div class="wp-block-wetravel-trips-block">
	<div class="wetravel-trips-container <?php echo esc_attr( $display_type ); ?>-view"
		id="trips-container-<?php echo esc_attr( $block_id ); ?>"
		data-slug="<?php echo esc_attr( $slug ); ?>"
		data-env="<?php echo esc_attr( $env ); ?>"
		data-nonce="<?php echo esc_attr( $nonce ); ?>"
		data-items-per-page="<?php echo esc_attr( $items_per_page ); ?>"
		data-items-per-row="<?php echo esc_attr( $items_per_row ); ?>"
		data-items-per-slide="<?php echo esc_attr( $items_per_slide ); ?>"
		data-display-type="<?php echo esc_attr( $display_type ); ?>"
		data-button-type="<?php echo esc_attr( $button_type ); ?>"
		data-button-text="<?php echo esc_attr( $button_text ); ?>"
		data-button-color="<?php echo esc_attr( $button_color ); ?>"
		<?php if ( ! empty( $selected_design_id ) ) : ?>
		data-design="<?php echo esc_attr( $selected_design_id ); ?>"
		<?php endif; ?>
		<?php if ( ! empty( $attributes['dateStart'] ) || ( ! empty( $design ) && ! empty( $design['dateRangeStart'] ) ) ) : ?>
		data-date-start="<?php echo esc_attr( ! empty( $attributes['dateStart'] ) ? $attributes['dateStart'] : $design['dateRangeStart'] ); ?>"
		<?php endif; ?>
		<?php if ( ! empty( $attributes['dateEnd'] ) || ( ! empty( $design ) && ! empty( $design['dateRangeEnd'] ) ) ) : ?>
		data-date-end="<?php echo esc_attr( ! empty( $attributes['dateEnd'] ) ? $attributes['dateEnd'] : $design['dateRangeEnd'] ); ?>"
		<?php endif; ?>
		<?php if ( ! empty( $attributes['tripType'] ) || ( ! empty( $design ) && ! empty( $design['tripType'] ) ) ) : ?>
		data-trip-type="<?php echo esc_attr( ! empty( $attributes['tripType'] ) ? $attributes['tripType'] : $design['tripType'] ); ?>"
		<?php endif; ?>>

		<!-- Loading indicator -->
		<div class="wetravel-trips-loading">
			<div class="loading-spinner"></div>
			<p>Loading trips...</p>
		</div>

		<!-- This is where trips will be rendered -->
		<div class="wetravel-trips-list"></div>
		</div>

		<?php if ( 'carousel' !== $display_type ) : ?>
		<!-- Numbered pagination container - will be populated by JavaScript -->
		<div id="pagination-<?php echo esc_attr( $block_id ); ?>" class="wetravel-trips-pagination"></div>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Make sure this is outside the function (in the main plugin file or a setup function).
 * Add this in the enqueue_wetravel_trips_scripts function.
 */
function enqueue_wetravel_trips_scripts() {
	wp_enqueue_script(
		'wetravel-trips-loader',
		plugins_url( 'assets/js/trips-loader.js', __DIR__ ),
		array( 'jquery' ),
		filemtime( WETRAVEL_TRIPS_PLUGIN_DIR . 'assets/js/trips-loader.js' ),
		true
	);

	// Localize the script to provide the AJAX URL.
	wp_localize_script(
		'wetravel-trips-loader',
		'wetravelTripsData',
		array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'wetravel_trips_nonce' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'enqueue_wetravel_trips_scripts' );
