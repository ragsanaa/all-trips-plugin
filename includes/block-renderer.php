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
	$src                    = $attributes['src'] ?? get_option( 'wetravel_trips_src', '' );
	$slug                   = $attributes['slug'] ?? get_option( 'wetravel_trips_slug', '' );
	$env                    = $attributes['env'] ?? get_option( 'wetravel_trips_env', 'https://pre.wetravel.to' );
	$wetravel_trips_user_id = $attributes['wetravelUserID'] ?? get_option( 'wetravel_trips_user_id', '' );
	$display_type           = $attributes['displayType'] ?? get_option( 'wetravel_trips_display_type', 'vertical' );
	$button_type            = $attributes['buttonType'] ?? get_option( 'wetravel_trips_button_type', 'book_now' );
	$button_color           = $attributes['buttonColor'] ?? get_option( 'wetravel_trips_button_color', '#33ae3f' );
	$items_per_page         = intval( $attributes['itemsPerPage'] ?? get_option( 'wetravel_trips_items_per_page', 10 ) );
	$items_per_row          = intval( $attributes['itemsPerRow'] ?? get_option( 'wetravel_trips_items_per_row', 3 ) );
	$items_per_slide        = intval( $attributes['itemsPerSlide'] ?? get_option( 'wetravel_trips_items_per_slide', 3 ) );
	$load_more_text         = $attributes['loadMoreText'] ?? get_option( 'wetravel_trips_load_more_text', 'Load More' );

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

	// Get trip_type, date_start and date_end from attributes or design.
	$trip_type  = ! empty( $attributes['tripType'] ) ? $attributes['tripType'] : ( ! empty( $design['tripType'] ) ? $design['tripType'] : 'all' );
	$date_start = ! empty( $attributes['dateStart'] ) ? $attributes['dateStart'] : ( ! empty( $design['dateRangeStart'] ) ? $design['dateRangeStart'] : '' );
	$date_end   = ! empty( $attributes['dateEnd'] ) ? $attributes['dateEnd'] : ( ! empty( $design['dateRangeEnd'] ) ? $design['dateRangeEnd'] : '' );

	// Fetch trips data directly.
	$api_url      = "{$env}/api/v2/embeds/all_trips";
	$query_params = array( 'slug' => $slug );

	// Format dates if they exist.
	if ( ! empty( $date_start ) ) {
		$date_obj = date_create( $date_start );
		if ( $date_obj ) {
			$date_start = date_format( $date_obj, 'Y-m-d' );
		}
	}

	if ( ! empty( $date_end ) ) {
		$date_obj = date_create( $date_end );
		if ( $date_obj ) {
			$date_end = date_format( $date_obj, 'Y-m-d' );
		}
	}

	// Set recurring/one-time parameters.
	if ( 'one-time' === $trip_type ) {
		$query_params['all_year'] = 'false';

		// Add date range for one-time trips.
		if ( ! empty( $date_start ) ) {
			$query_params['from_date'] = $date_start;
		}

		if ( ! empty( $date_end ) ) {
			$query_params['to_date'] = $date_end;
		}
	}

	// Build the final URL with parameters.
	$api_url = add_query_arg( $query_params, $api_url );

	// Get trips data with caching.
	$trips = get_wetravel_trips_data( $api_url, $env );

	if ( 'recurring' === $trip_type ) {
		// Filter trips where 'all_year' is true.
		$trips = array_filter(
			$trips,
			function ( $trip ) {
				return ! empty( $trip['all_year'] ) && true === $trip['all_year'];
			}
		);
	}

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
		<!-- Initial loading state - show by default -->
		<div class="wetravel-trips-loading" id="loading-<?php echo esc_attr( $block_id ); ?>">
			<div class="loading-spinner"></div>
			<p>Loading trips...</p>
		</div>

		<div class="wetravel-trips-container <?php echo esc_attr( $display_type ); ?>-view"
			id="trips-container-<?php echo esc_attr( $block_id ); ?>"
			data-slug="<?php echo esc_attr( $slug ); ?>"
			data-env="<?php echo esc_attr( $env ); ?>"
			data-wetravel-user-id="<?php echo esc_attr( $wetravel_trips_user_id ); ?>"
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
			data-trip-type="<?php echo esc_attr( $trip_type ); ?>"
			data-date-start="<?php echo esc_attr( $date_start ); ?>"
			data-date-end="<?php echo esc_attr( $date_end ); ?>">

			<?php if ( empty( $trips ) ) : ?>
				<div class="no-trips">No trips found</div>
			<?php else : ?>

				<?php if ( 'carousel' === $display_type ) : ?>
					<div class="swiper">
						<div class="swiper-wrapper">
							<?php foreach ( $trips as $trip ) : ?>
								<div class="swiper-slide">
									<?php
									// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML output is escaped inside render_trip_item().
									echo render_trip_item(
										$trip,
										array(
											'env'          => $env,
											'wetravelUserID' => $wetravel_trips_user_id,
											'displayType'  => $display_type,
											'buttonType'   => $button_type,
											'buttonText'   => $button_text,
											'buttonColor'  => $button_color,
											'itemsPerPage' => $items_per_page,
										)
									);
									?>
								</div>
							<?php endforeach; ?>
						</div>
						<div class="swiper-pagination"></div>
						<div class="swiper-button-next"></div>
						<div class="swiper-button-prev"></div>
					</div>
				<?php else : ?>
					<?php
					$counter = 0;
					foreach ( $trips as $trip ) :
						$visibility_class = $counter < $items_per_page ? 'visible-item' : 'hidden-item';
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML output is escaped inside render_trip_item().
						echo render_trip_item(
							$trip,
							array(
								'env'            => $env,
								'wetravelUserID' => $wetravel_trips_user_id,
								'displayType'    => $display_type,
								'buttonType'     => $button_type,
								'buttonText'     => $button_text,
								'buttonColor'    => $button_color,
								'itemsPerPage'   => $items_per_page,
							),
							$visibility_class
						);
						++$counter;
					endforeach;
					?>
				<?php endif; ?>

			<?php endif; ?>
		</div>

		<?php if ( 'carousel' !== $display_type && count( $trips ) > $items_per_page ) : ?>
			<!-- Numbered pagination container -->
			<div id="pagination-<?php echo esc_attr( $block_id ); ?>" class="wetravel-trips-pagination">
				<div class="pagination-controls">
					<?php
					$total_pages = ceil( count( $trips ) / $items_per_page );
					for ( $i = 1; $i <= $total_pages; $i++ ) {
						$active_class = 1 === $i ? 'active' : '';
						echo '<span class="page-number ' . esc_attr( $active_class ) . '" data-page="' . esc_attr( $i ) . '">' . esc_html( $i ) . '</span>';
					}
					?>
				</div>
			</div>
		<?php endif; ?>
	</div>
	<?php if ( 'book_now' === $button_type ) : ?>
		<?php
		wp_enqueue_script(
			'wetravel-embed-checkout',
			wetravel_trips_get_cdn_url( $env ) . '/widgets/embed_checkout.js',
			array(),
			'1.0.0', // Set a fixed version to avoid browser caching issues.
			true
		);
		?>
	<?php endif; ?>
	<?php

	// Script to handle loading spinner visibility.
	$inline_script = "
		jQuery(document).ready(function($) {
			// Function to hide loading spinner once content is loaded
			function hideLoadingSpinner(blockId) {
				// Hide the loading spinner
				$('#loading-' + blockId).fadeOut();
			}

			// For server-side rendered content
			var blockId = '" . esc_js( $block_id ) . "';
			var tripsContainer = $('#trips-container-' + blockId);

			// If trips are already in the container (server-side rendered),
			// hide spinner after a short delay to allow for visual feedback
			if (tripsContainer.find('.trip-item').length > 0) {
				setTimeout(function() {
					hideLoadingSpinner(blockId);
				}, 500);
			}

			// For client-side loaded content, the spinner is handled in trips-loader.js
			// Add a global callback function that can be called after AJAX trips load
			window.tripsLoaded = function(blockId) {
				hideLoadingSpinner(blockId);
			};

			// Add a fallback timeout to hide spinner after 15 seconds in case of errors
			setTimeout(function() {
				$('.wetravel-trips-loading').fadeOut();
			}, 15000);
		});
	";

	// Add the inline script to the output.
	wp_register_script( 'wetravel-trips-loading', '', array( 'jquery' ), filemtime( WETRAVEL_TRIPS_PLUGIN_DIR . 'assets/js/trips-loader.js' ), true );
	wp_add_inline_script( 'wetravel-trips-loading', $inline_script );
	wp_enqueue_script( 'wetravel-trips-loading' );
	return ob_get_clean();
}

/**
 * Get button URL for a trip based on options and trip data.
 *
 * @param array $trip Trip data.
 * @param array $options Button options.
 * @return string Button URL.
 */
function get_button_url( $trip, $options ) {
	$env        = $options['env'];
	$button_url = '';

	// Set up button URL based on button type.
	if ( 'book_now' === $options['buttonType'] ) {
		$button_url = $env . '/checkout_embed?uuid=' . $trip['uuid'];
	} else {
		$button_url = $env . '/trips/' . $trip['uuid'];
		if ( isset( $trip['href'] ) ) {
			$button_url = $trip['href'];
		}
	}

	return $button_url;
}

/**
 * Render a single trip item
 *
 * @param array  $trip Trip data.
 * @param array  $options Display options.
 * @param string $visibility_class CSS class for visibility.
 * @return string Trip HTML.
 */
function render_trip_item( $trip, $options, $visibility_class = '' ) {
	$html       = '';
	$button_url = get_button_url( $trip, $options );

	if ( 'vertical' === $options['displayType'] ) {
		$html .= '<div class="trip-item ' . $visibility_class . '">';
	} elseif ( 'grid' === $options['displayType'] ) {
		$html .= '<div class="trip-item wtrvl-checkout_button ' . $visibility_class . '" ' .
				'data-env="' . esc_attr( $options['env'] ) . '" ' .
				'data-version="v0.3" ' .
				'data-uid="' . esc_attr( $options['wetravelUserID'] ) . '" ' .
				'data-uuid="' . esc_attr( $trip['uuid'] ) . '" ' .
				'href="' . esc_url( $button_url ) . '" ' .
				'style="cursor: pointer;"' .
				'>';
	} elseif ( 'carousel' === $options['displayType'] ) {
		$html .= '<div class="trip-item wtrvl-checkout_button" ' .
				'data-env="' . esc_attr( $options['env'] ) . '" ' .
				'data-version="v0.3" ' .
				'data-uid="' . esc_attr( $options['wetravelUserID'] ) . '" ' .
				'data-uuid="' . esc_attr( $trip['uuid'] ) . '" ' .
				'href="' . esc_url( $button_url ) . '" ' .
				'style="cursor: pointer;"' .
				'>';
	}

	// Image.
	if ( ! empty( $trip['default_image'] ) ) {
		$html .= '<div class="trip-image">';
		$html .= '<img src="' . esc_url( $trip['default_image'] ) . '" alt="' . esc_attr( $trip['title'] ) . '">';
		$html .= '</div>';
	} else {
		$html .= '<div class="no-image-placeholder"><span>No Image Available</span></div>';
	}

	// Content.
	$html .= '<div class="trip-content">';
	$html .= '<div class="trip-title-desc">';
	$html .= '<h3>' . esc_html( $trip['title'] ) . '</h3>';

	// Description.
	if ( ! empty( $trip['full_description'] ) ) {
		$html .= '<div class="trip-description">' . wp_kses_post( $trip['full_description'] ) . '</div>';
	}
	$html .= '</div>'; // Close trip-title-desc.

	if ( 'carousel' === $options['displayType'] ) {
		$html .= "<div class='trip-loc-price'>";
	}

	// Date or duration.
	$html .= '<div class="trip-loc-duration">';

	if ( ! $trip['all_year'] ) {
		$html .= '<div class="trip-date trip-tag">' . esc_html( $trip['start_end_dates'] ) . '</div>';
	} elseif ( ! empty( $trip['custom_duration'] ) ) {
		$html .= '<div class="trip-duration trip-tag">' . esc_html( $trip['custom_duration'] ) . ' days</div>';
	}
	$html .= '<div class="trip-location trip-tag">' . esc_html( $trip['location'] ) . '</div>';
	$html .= '</div>'; // Close trip-loc-duration.

	if ( 'carousel' !== $options['displayType'] ) {
		$html .= '</div>'; // Close trip-content.
	}

	// Price and button section.
	$html .= '<div class="trip-price-button">';

	// Price.
	if ( ! empty( $trip['price'] ) ) {
		$html .= '<div class="trip-price"><p>From</p> <span>' .
			esc_html( $trip['price']['currencySymbol'] ) .
			esc_html( $trip['price']['amount'] ) .
			'</span></div>';
	}

	// Button.
	if ( 'carousel' !== $options['displayType'] ) {
		if ( 'book_now' === $options['buttonType'] ) {
			$html .= '<button class="wtrvl-checkout_button trip-button" ' .
				'data-env="' . esc_attr( $options['env'] ) . '" ' .
				'data-version="v0.3" ' .
				'data-uid="' . esc_attr( $options['wetravelUserID'] ) . '" ' .
				'data-uuid="' . esc_attr( $trip['uuid'] ) . '" ' .
				'href="' . esc_url( $button_url ) . '">' .
				esc_html( $options['buttonText'] ) .
				'</button>';
		} else {
			// Regular link for "View Trip".
			$html .= '<a href="' . esc_url( $button_url ) . '" class="trip-button" target="_blank">' .
				esc_html( $options['buttonText'] ) .
				'</a>';
		}
	}

	$html .= '</div>'; // Close trip-price-button.
	if ( 'carousel' === $options['displayType'] ) {
		$html .= '</div>'; // Close trip-loc-price.
		$html .= '</div>'; // Close trip-content.
	}
	$html .= '</div>'; // Close trip-item.

	return $html;
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
