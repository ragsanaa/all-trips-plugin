<?php
/**
 * Block Renderer
 *
 * Dynamically render widgets.
 *
 * @package WordPress
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render callback for dynamic block.
 *
 * @param array $attributes Give all settings and designs details.
 */
function wtwidget_trips_block_render( $attributes ) {
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
	$search_visibility      = $attributes['searchVisibility'] ?? get_option( 'wetravel_trips_search_visibility', false );

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
			$search_visibility = isset( $design['searchVisibility'] ) ? $design['searchVisibility'] : $search_visibility;
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

	// Get selected locations from design
	$locations = !empty($design['locations']) ? $design['locations'] : array();

	// Build API URL with parameters
	$api_url = wtwidget_build_api_url($env, $slug, array(
		'trip_type' => $trip_type,
		'date_start' => $date_start,
		'date_end' => $date_end
	));

	// Get trips data
	$trips = wtwidget_get_trips_data($api_url);

	// Filter trips by location if locations are specified
	if (!empty($locations)) {
		$trips = array_filter($trips, function($trip) use ($locations) {
			return !empty($trip['location']) && in_array($trip['location'], $locations);
		});
	}

	if ( 'recurring' === $trip_type ) {
		// Filter trips where 'all_year' is true.
		$trips = array_filter(
			$trips,
			function ( $trip ) {
				return ! empty( $trip['all_year'] ) && true === $trip['all_year'];
			}
		);
	}

	// Fetch enhanced trip data with additional details since we need it for display
	if (!empty($trips)) {
		$trips = wtwidget_enhance_trips_with_details($trips, $env);
	}

	// Enqueue necessary assets based on display type.
	if ( 'carousel' === $display_type ) {
		wp_enqueue_style(
			'swiper-css',
			plugins_url( 'assets/css/swiper-bundle.min.css', dirname( __FILE__ ) ),
			array(),
			filemtime( plugin_dir_path( dirname( __FILE__ ) ) . 'assets/css/swiper-bundle.min.css' )
		);
		wp_enqueue_script(
			'swiper-js',
			plugins_url( 'assets/js/swiper-bundle.min.js', dirname( __FILE__ ) ),
			array(),
			filemtime( plugin_dir_path( dirname( __FILE__ ) ) . 'assets/js/swiper-bundle.min.js' ),
			true
		);
		wp_enqueue_script(
			'wetravel-trips-carousel',
			plugins_url( 'assets/js/carousel.js', dirname( __FILE__ ) ),
			array( 'jquery', 'swiper-js' ),
			filemtime( plugin_dir_path( dirname( __FILE__ ) ) . 'assets/js/carousel.js' ),
			true
		);
	} else {
		// Always enqueue pagination script for grid and vertical views.
		wp_enqueue_script(
			'wetravel-trips-pagination',
			plugins_url( 'assets/js/pagination.js', dirname( __FILE__ ) ),
			array( 'jquery' ),
			filemtime( plugin_dir_path( dirname( __FILE__ ) ) . 'assets/js/pagination.js' ),
			true
		);
	}

	// Enqueue Select2 for location filter
	wp_enqueue_style(
		'select2-css',
		plugins_url( 'assets/css/select2.min.css', dirname( __FILE__ ) ),
		array(),
		filemtime( plugin_dir_path( dirname( __FILE__ ) ) . 'assets/css/select2.min.css' )
	);
	wp_enqueue_script(
		'select2-js',
		plugins_url( 'assets/js/select2.min.js', dirname( __FILE__ ) ),
		array('jquery'),
		filemtime( plugin_dir_path( dirname( __FILE__ ) ) . 'assets/js/select2.min.js' ),
		true
	);

	// Enqueue search filter script
	wp_enqueue_script(
		'wetravel-trips-search-filter',
		plugins_url( 'assets/js/search-filter.js', dirname( __FILE__ ) ),
		array( 'jquery', 'select2-js' ),
		filemtime( plugin_dir_path( dirname( __FILE__ ) ) . 'assets/js/search-filter.js' ),
		true
	);

	// Initialize Select2 for this specific block
	wp_add_inline_script('select2-js', sprintf(
		'jQuery(document).ready(function($) {
			$("#search-filter-%s .location-filter").select2({
				placeholder: "Filter by location...",
				allowClear: true,
				width: "100%%"
			});
		});',
		esc_js($block_id)
	));

	// Only add dynamic CSS that depends on block attributes.
	$button_color = safecss_filter_attr($button_color);
	$items_per_row = absint($items_per_row); // Convert to positive integer

	$custom_css = sprintf(
		'#trips-container-%1$s { --button-color: %2$s; --items-per-row: %3$d; }',
		esc_attr($block_id),
		$button_color,
		$items_per_row
	);

	// Add design-specific custom CSS if available.
	if ( ! empty( $custom_css_design ) ) {
		$custom_css .= "\n/* Design-specific custom CSS */\n" . safecss_filter_attr($custom_css_design);
	}

	wp_register_style(
		'wetravel-trips-styles',
		plugins_url( 'assets/css/wetravel-trips.css', dirname( __FILE__ ) ),
		array(),
		filemtime( plugin_dir_path( dirname( __FILE__ ) ) . 'assets/css/wetravel-trips.css' )
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

		<?php
			if ( 'carousel' !== $display_type && $search_visibility ) :
		?>
		<!-- Search Filter UI -->
		<div class="wetravel-trips-search-filter" id="search-filter-<?php echo esc_attr( $block_id ); ?>"
			style="--button-color: <?php echo esc_attr( $button_color ); ?>; --button-color-rgb: <?php echo esc_attr(wtwidget_hex_to_rgb($button_color)); ?>;">
			<div class="search-filter-container">
				<!-- Search input with icon -->
				<input type="text"
						class="search-input"
						placeholder="Search trips by name..."
						data-block-id="<?php echo esc_attr( $block_id ); ?>"
					/>

				<!-- Location Filter -->
				<button type="button" class="location-button" data-block-id="<?php echo esc_attr( $block_id ); ?>">
					<span id="selected-text">Select locations</span>
					<span class="selected-count" id="selected-count" style="display: none;">0 selected</span>
					<span class="dropdown-arrow" id="dropdown-arrow">▲</span>
				</button>
			</div>
			<!-- Custom Location Dropdown -->
			<div class="location-dropdown">
				<div class="dropdown-menu" id="dropdown-menu">
					<div class="location-search">
						<input type="text" placeholder="Search Location" id="location-search" data-block-id="<?php echo esc_attr( $block_id ); ?>" />
					</div>
					<div class="location-list" id="location-list">
						<?php
						// Get unique locations from trips
						$locations = array();
						if (is_array($trips)) {
							$locations = array_unique(array_filter(array_map(function($trip) {
								return isset($trip['location']) ? $trip['location'] : '';
							}, $trips)));
							sort($locations);
						}

						foreach ($locations as $location) {
							if (!empty($location)) {
								$location_id = sanitize_title($location);
								echo '<div class="location-item" data-location="' . esc_attr($location) . '" data-block-id="' . esc_attr($block_id) . '">';
								echo '<div class="checkmark" id="check-' . esc_attr($location_id) . '"></div>';
								echo '<div class="location-name">' . esc_html($location) . '</div>';
								echo '</div>';
							}
						}
						?>
					</div>
				</div>
			</div>
		</div>
		<?php endif; ?>

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
			<?php
				$allowed_html_tags = array(
					'div' => array(
							'class' => true,
							'data-env' => true,
							'data-version' => true,
							'data-uid' => true,
							'data-uuid' => true,
							'href' => true,
							'style' => true,
					),
					'img' => array(
							'src' => true,
							'alt' => true,
							'class' => true,
							'loading' => true,
							'decoding' => true,
							'width' => true,
							'height' => true,
					),
					'h3' => array(),
					'p' => array(),
					'span' => array(),
					'button' => array(
						'class' => true,
						'style' => true,
						'href' => true,
						'data-uuid' => true,
						'data-uid' => true,
						'data-env' => true,
						'data-version' => true,
					),
					'a' => array(
						'class' => true,
						'style' => true,
						'href' => true,
						'target' => true,
					),
				);
			?>

			<?php if ( empty( $trips ) ) : ?>
				<div class="no-trips">No trips found</div>
			<?php else : ?>

				<?php if ( 'carousel' === $display_type ) : ?>
					<div class="swiper">
						<div class="swiper-wrapper">
							<?php foreach ( $trips as $trip ) : ?>
								<div class="swiper-slide">
									<?php
									// The output contains trusted, controlled HTML (e.g., iframe, div, etc.)
									// Escaping it with esc_html() breaks embed functionality
									// So we sanitize with wp_kses_post() to allow only safe HTML
									echo wp_kses(wtwidget_render_trip_item(
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
									), $allowed_html_tags );
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
						// The output contains trusted, controlled HTML (e.g., iframe, div, etc.)
						// Escaping it with esc_html() breaks embed functionality
						// So we sanitize with wp_kses_post() to allow only safe HTML
						echo wp_kses(wtwidget_render_trip_item(
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
						), $allowed_html_tags );
						++$counter;
					endforeach;
					?>
				<?php endif; ?>

			<?php endif; ?>
		</div>

		<?php if ( ! empty( $trips ) && 'carousel' !== $display_type && count( $trips ) > $items_per_page ) : ?>
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
			wtwidget_get_cdn_url( $env ) . '/widgets/embed_checkout.js',
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
	wp_register_script( 'wetravel-trips-loading', '', array( 'jquery' ),
		filemtime( plugin_dir_path( dirname( __FILE__ ) ) . 'assets/js/trips-loader.js' ), true );
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
function wtwidget_get_button_url( $trip, $options ) {
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
function wtwidget_render_trip_item( $trip, $options, $visibility_class = '' ) {
	$html       = '';
	$button_url = wtwidget_get_button_url( $trip, $options );

	if ( 'vertical' === $options['displayType'] ) {
		$html .= '<div class="trip-item ' . esc_attr( $visibility_class ) . '">';
	} elseif ( 'grid' === $options['displayType'] ) {
		$html .= sprintf(
			'<div class="trip-item wtrvl-checkout_button %s" data-env="%s" data-version="v0.3" data-uid="%s" data-uuid="%s" href="%s" style="cursor: pointer;">',
			esc_attr( $visibility_class ),
			esc_attr( $options['env'] ),
			esc_attr( $options['wetravelUserID'] ),
			esc_attr( $trip['uuid'] ),
			esc_url( $button_url )
		);
	} elseif ( 'carousel' === $options['displayType'] ) {
		$html .= sprintf(
			'<div class="trip-item wtrvl-checkout_button" data-env="%s" data-version="v0.3" data-uid="%s" data-uuid="%s" href="%s" style="cursor: pointer;">',
			esc_attr( $options['env'] ),
			esc_attr( $options['wetravelUserID'] ),
			esc_attr( $trip['uuid'] ),
			esc_url( $button_url )
		);
	}

	// Image.
	if ( ! empty( $trip['default_image'] ) ) {
		$trip_image = wtwidget_render_external_image(
			$trip['default_image'],
			$trip['title'],
			array(
				'class' => 'trip-image-thumbnail',
				'width' => 400, // Set appropriate size
				'height' => 300 // Set appropriate size
			)
		);
		$html .= '<div class="trip-image">' . $trip_image . '</div>';
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
		$html .= sprintf(
			'<div class="trip-duration trip-tag">%s days</div>',
			esc_html( $trip['custom_duration'] )
		);
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
		$html .= sprintf(
			'<div class="trip-price"><p>From</p> <span>%s%s</span></div>',
			esc_html( $trip['price']['currencySymbol'] ),
			esc_html( $trip['price']['amount'] )
		);
	}

	// Button.
	if ( 'carousel' !== $options['displayType'] ) {
		$button_style = '';
		if ($options['displayType'] === 'vertical') {
			// Filled button style for vertical view
			$button_style = sprintf(
				'background-color: %1$s; border-color: %1$s; color: #fff;',
				esc_attr( $options['buttonColor'] )
			);
		} else {
			// Outline button style for grid view
			$button_style = sprintf(
				'background-color: transparent; border-color: %1$s; color: %1$s;',
				esc_attr( $options['buttonColor'] )
			);
		}

		if ( 'book_now' === $options['buttonType'] ) {
			$html .= sprintf(
				'<button class="wtrvl-checkout_button trip-button" data-env="%s" data-version="v0.3" data-uid="%s" data-uuid="%s" href="%s" style="%s">%s</button>',
				esc_attr( $options['env'] ),
				esc_attr( $options['wetravelUserID'] ),
				esc_attr( $trip['uuid'] ),
				esc_url( $button_url ),
				$button_style,
				esc_html( $options['buttonText'] )
			);
		} else {
			$html .= sprintf(
				'<a href="%s" class="trip-button" target="_blank" style="%s">%s</a>',
				esc_url( $button_url ),
				$button_style,
				esc_html( $options['buttonText'] )
			);
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
 * Render external image with proper attributes and fallback
 *
 * @param string $url Image URL.
 * @param string $alt Alt text.
 * @param array  $args Additional arguments.
 * @return string HTML for the image.
 */
function wtwidget_render_external_image($url, $alt = '', $args = array()) {
	// Ensure URL is valid
	$url = esc_url($url);
	if (empty($url)) {
		return '';
	}

	// Default arguments
	$defaults = array(
		'class' => 'wetravel-trip-image',
		'loading' => 'lazy',
		'decoding' => 'async',
		'width' => '',
		'height' => ''
	);
	$args = wp_parse_args($args, $defaults);

	// Build attributes string
	$attributes = array(
		'src' => $url,
		'alt' => esc_attr($alt),
		'class' => esc_attr($args['class']),
		'loading' => $args['loading'],
		'decoding' => $args['decoding']
	);

	// Add optional width and height if provided
	if (!empty($args['width'])) {
		$attributes['width'] = absint($args['width']);
	}
	if (!empty($args['height'])) {
		$attributes['height'] = absint($args['height']);
	}

	// Build HTML attributes
	$html_attrs = '';
	foreach ($attributes as $name => $value) {
		if ($value) {
			$html_attrs .= ' ' . $name . '="' . $value . '"';
		}
	}

	return sprintf('<img%s />', $html_attrs);
}

/**
 * Enqueue necessary scripts for WeTravel Trips
 */
function wtwidget_enqueue_trips_scripts() {
	wp_enqueue_script(
		'wetravel-trips-loader',
		plugins_url( 'assets/js/trips-loader.js', dirname( __FILE__ ) ),
		array( 'jquery' ),
		filemtime( plugin_dir_path( dirname( __FILE__ ) ) . 'assets/js/trips-loader.js' ),
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
add_action( 'wp_enqueue_scripts', 'wtwidget_enqueue_trips_scripts' );

/**
 * Convert hex color to RGB values
 *
 * @param string $hex_color The hex color code.
 * @return string RGB values separated by commas.
 */
function wtwidget_hex_to_rgb($hex_color) {
    // Remove # if present
    $hex_color = ltrim($hex_color, '#');

    // Convert to RGB
    $r = hexdec(substr($hex_color, 0, 2));
    $g = hexdec(substr($hex_color, 2, 2));
    $b = hexdec(substr($hex_color, 4, 2));

    return "$r, $g, $b";
}
