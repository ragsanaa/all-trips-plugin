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
	$items_per_slide        = intval( $attributes['itemsPerSlide'] ?? get_option( 'wetravel_trips_items_per_slide', 1 ) );
	$search_visibility      = $attributes['searchVisibility'] ?? get_option( 'wetravel_trips_search_visibility', false );
	$border_radius          = intval( $attributes['borderRadius'] ?? get_option( 'wetravel_trips_border_radius', 6 ) );
	$integration_type       = $attributes['integrationType'] ?? 'block';
	$wt_widget_type            = $attributes['wtWidgetType'] ?? get_option( 'wetravel_trips_wt_widget_type', 'all-trips' );

	// Override with design settings if a design is selected.
	if ( ! empty( $selected_design_id ) ) {
		// Handle both array and object format for designs.
		$design = null;

		// First try to find design by keyword (same logic as shortcode)
		foreach ( $designs as $id => $design_data ) {
			if ( isset( $design_data['keyword'] ) && $design_data['keyword'] === $selected_design_id ) {
				$design = $design_data;
				break;
			}
		}

		// If not found by keyword, try direct ID lookup
		if ( null === $design && isset( $designs[ $selected_design_id ] ) ) {
			// Object format.
			$design = $designs[ $selected_design_id ];
		}

		// If still not found, try array format - find by ID.
		if ( null === $design ) {
			foreach ( $designs as $d ) {
				if ( isset( $d['id'] ) && $d['id'] === $selected_design_id ) {
					$design = $d;
					break;
				}
			}
		}

		// Apply design settings ONLY if block attributes are not set (empty/null).
		// This ensures user changes in the editor take precedence over design settings.
		if ( $design ) {
			// Only apply design settings if the corresponding block attribute is not set
			if ( empty( $attributes['displayType'] ) && isset( $design['displayType'] ) ) {
				$display_type = $design['displayType'];
			}
			if ( empty( $attributes['buttonType'] ) && isset( $design['buttonType'] ) ) {
				$button_type = $design['buttonType'];
			}
			if ( empty( $attributes['buttonColor'] ) && isset( $design['buttonColor'] ) ) {
				$button_color = $design['buttonColor'];
			}
			if ( ! isset( $attributes['searchVisibility'] ) && isset( $design['searchVisibility'] ) ) {
				$search_visibility = $design['searchVisibility'];
			}
			if ( empty( $attributes['borderRadius'] ) && isset( $design['borderRadius'] ) ) {
				$border_radius = intval( $design['borderRadius'] );
			}
			if ( empty( $attributes['itemsPerSlide'] ) && isset( $design['itemsPerSlide'] ) ) {
				$items_per_slide = intval( $design['itemsPerSlide'] );
			}
			if ( empty( $attributes['itemsPerRow'] ) && isset( $design['itemsPerRow'] ) ) {
				$items_per_row = intval( $design['itemsPerRow'] );
			}
			if ( empty( $attributes['itemsPerPage'] ) && isset( $design['itemsPerPage'] ) ) {
				$items_per_page = intval( $design['itemsPerPage'] );
			}
			if ( empty( $attributes['wtWidgetType'] ) && isset( $design['wtWidgetType'] ) ) {
				$wt_widget_type = $design['wtWidgetType'];
			}
			// If the design has custom CSS, we'll add it later.
			$custom_css_design = isset( $design['customCSS'] ) ? $design['customCSS'] : '';

			// Check for buttonText in design - only if not set in block attributes
			if ( empty( $attributes['buttonText'] ) && ! empty( $design['buttonText'] ) ) {
				$button_text = $design['buttonText'];
			}
		}
	}

	// Set default buttonText based on buttonType if not provided.
	$default_button_text = 'book_now' === $button_type ? 'Book Now' : 'View Trip';
	$button_text         = ! empty( $attributes['buttonText'] ) ? $attributes['buttonText'] : $default_button_text;

	// If design has buttonText and block attribute is not set, use design buttonText.
	if ( ! empty( $selected_design_id ) && empty( $attributes['buttonText'] ) && isset( $designs[ $selected_design_id ]['buttonText'] ) ) {
		$button_text = $designs[ $selected_design_id ]['buttonText'];
	}

	// Clean up the environment URL if needed.
	$env = rtrim( $env, '/' );

	// Create a nonce for AJAX security.
	$nonce = wp_create_nonce( 'wetravel_trips_nonce' );

	// Get recurring, and date range from attributes or design.
	// Prioritize block attributes over design settings.
	$trip_type = isset( $attributes['tripType'] ) ? $attributes['tripType'] : ( isset( $design['tripType'] ) ? $design['tripType'] : '' );
	$date_start = ! empty( $attributes['dateStart'] ) ? $attributes['dateStart'] : ( ! empty( $design['dateStart'] ) ? $design['dateStart'] : '' );
	$date_end = ! empty( $attributes['dateEnd'] ) ? $attributes['dateEnd'] : ( ! empty( $design['dateEnd'] ) ? $design['dateEnd'] : '' );

	// Get selected locations - prioritize shortcode attribute over design settings
	$locations = array();

	// Check if locations were specified in shortcode attributes
	if (isset($attributes['locations']) && $attributes['locations'] !== '') {
		$locations_attr = $attributes['locations'];
		// Parse semicolon-separated locations from shortcode
		$locations = array_map('trim', explode(';', $locations_attr));
		$locations = array_filter($locations); // Remove empty entries
	}
	// If no locations attribute in shortcode or it's empty, fall back to design settings
	elseif (!empty($design) && isset($design['locations']) && is_array($design['locations'])) {
		$locations = $design['locations'];
	}

	// Check if this is mock data request
	$is_mock_data = isset($attributes['mockData']) && $attributes['mockData'];

	// Get pagination settings
	$current_page = isset($attributes['currentPage']) ? intval($attributes['currentPage']) : 1;

	// Build API URL with all parameters including filters and pagination
	$api_params = array(
		'page'       => $current_page,
		'per_page'   => $items_per_page,
	);

	// Add trip type filter (will be transformed to recurring in API)
	if ($trip_type !== '') {
		$api_params['trip_type'] = $trip_type;
	}

	// Add departure date range filters
	if (!empty($date_start) || !empty($date_end)) {
		$api_params['departure_date'] = array();
		if (!empty($date_start)) {
			$api_params['departure_date']['gte'] = $date_start;
		}
		if (!empty($date_end)) {
			$api_params['departure_date']['lte'] = $date_end;
		}
	}

	// Add locations filter (will be transformed to destinations in API)
	if (!empty($locations)) {
		$api_params['locations'] = $locations;
	}

	$api_url = wtwidget_build_api_url($env, $wetravel_trips_user_id, $api_params);

	// Cache key includes all filter parameters
	$cache_key = 'wetravel_trips_' . md5($api_url);
	$cached_response = get_transient($cache_key);

	$trips = array();
	$enhanced_trips = array();
	$pagination = array(
		'total_count'  => 0,
		'page'         => $current_page,
		'per_page'     => $items_per_page,
		'total_pages'  => 1,
		'has_previous' => false,
		'has_next'     => false,
	);
	$is_using_cache = false;

	if ($is_mock_data) {
		// Use mock data instead of API, skip cache
		$trips = wtwidget_get_mock_trips_data($attributes);
		$enhanced_trips = $trips;
		$pagination['total_count'] = count($trips);
		$pagination['total_pages'] = ceil(count($trips) / $items_per_page);
	} elseif (false !== $cached_response && isset($cached_response['trips'])) {
		// Use cached data - fastest path
		$enhanced_trips = $cached_response['trips'];
		$pagination = $cached_response['pagination'] ?? $pagination;
		$is_using_cache = true;
	} else {
		// Cache miss: fetch fresh data from API
		$api_response = wtwidget_get_fresh_trips_data($api_url);

		if (false === $api_response) {
			$trips = array(); // Set to empty array to show "No trips found" message
		} else {
			// API already handles filtering and pagination
			$trips = $api_response['trips'];
			$pagination = $api_response['pagination'];
		}

		$enhanced_trips = $trips;

		// Cache complete response
		if (!empty($trips)) {
			$cache_duration = defined( 'WETRAVEL_CACHE_DURATION' ) ? WETRAVEL_CACHE_DURATION : ( 5 * MINUTE_IN_SECONDS );
			set_transient($cache_key, array(
				'trips'      => $enhanced_trips,
				'pagination' => $pagination,
			), $cache_duration);
		}
	}

	// Optimize carousel loading for small trip counts
	// If total trips fit in one API call (≤ per_page), load all at once to avoid unnecessary progressive loading
	$carousel_optimized = false;
	if ( 'carousel' === $display_type && !empty($pagination['total_count']) ) {
		$total_trips = intval($pagination['total_count']);
		// If total trips fit in one page (≤ per_page), fetch all in one go
		if ($total_trips <= $items_per_page && $total_trips > 0) {
			// Only re-fetch if we haven't loaded all trips yet
			if (count($enhanced_trips) < $total_trips) {
				$api_params['per_page'] = $total_trips; // Fetch all trips
				$api_url_optimized = wtwidget_build_api_url($env, $wetravel_trips_user_id, $api_params);
				$api_response_optimized = wtwidget_get_fresh_trips_data($api_url_optimized);

				if ($api_response_optimized !== false) {
					$enhanced_trips = $api_response_optimized['trips'];
					$pagination = $api_response_optimized['pagination'];
					$carousel_optimized = true;
				}
			}
		}
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

	// Only enqueue Select2 when search is enabled.
	if ( $search_visibility ) {
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

		// Enqueue centralized Select2 location initialization utility
		wp_enqueue_script(
			'wetravel-select2-locations',
			plugins_url( 'assets/js/select2-locations.js', dirname( __FILE__ ) ),
			array( 'jquery', 'select2-js' ),
			filemtime( plugin_dir_path( dirname( __FILE__ ) ) . 'assets/js/select2-locations.js' ),
			true
		);
	}

	// Enqueue search filter script
	wp_enqueue_script(
		'wetravel-trips-search-filter',
		plugins_url( 'assets/js/search-filter.js', dirname( __FILE__ ) ),
		array( 'jquery', 'select2-js', 'wetravel-select2-locations' ),
		filemtime( plugin_dir_path( dirname( __FILE__ ) ) . 'assets/js/search-filter.js' ),
		true
	);

	// Localize script data for frontend location search
	wp_localize_script('wetravel-trips-search-filter', 'wetravelSearchData', array(
		'restUrl' => esc_url_raw( rest_url() ),
		'organizerId' => $wetravel_trips_user_id,
		'nonce' => wp_create_nonce('wp_rest')
	));

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
	$border_radius = absint($border_radius); // Convert to positive integer

	// Use direct CSS instead of CSS custom properties for better compatibility with older WordPress versions
	$custom_css = sprintf(
		'#trips-container-%1$s { --button-color: %2$s; --items-per-row: %3$d; --border-radius: %4$dpx; }
		#loading-%1$s .loading-spinner { border-top-color: %2$s; }',
		esc_attr($block_id),
		$button_color,
		$items_per_row,
		$border_radius
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

	ob_start();
	?>
	<!-- Dynamic styles for WordPress version compatibility -->
	<style type="text/css">
		<?php echo esc_html( $custom_css ); ?>
	</style>
	<div class="wp-block-wetravel-trips-block">
		<!-- Initial loading state - show by default -->
		<div class="wetravel-trips-loading" id="loading-<?php echo esc_attr( $block_id ); ?>">
			<div class="loading-spinner"></div>
		</div>

		<?php
			if ( 'carousel' !== $display_type && $search_visibility ) :
		?>

		<!-- Search Filter UI -->
		<div class="wetravel-trips-search-filter"
     id="search-filter-<?php echo esc_attr( $block_id ); ?>"
     style="--button-color: <?php echo esc_attr( $button_color ); ?>; --button-color-rgb: <?php echo esc_attr(wtwidget_hex_to_rgb($button_color)); ?>;">

			<div class="search-filter-container">
				<!-- Search input with clear button -->
				<div class="search-input-wrapper">
						<input type="text"
										class="search-input"
										placeholder="Search trips by name (min 3 characters)..."
										data-block-id="<?php echo esc_attr( $block_id ); ?>"
						/>
						<button type="button" class="search-clear-btn"
										data-block-id="<?php echo esc_attr( $block_id ); ?>"
										style="display: none;">×</button>
				</div>

				<!-- Filter toggle button wrapper -->
				<div class="filter-button-wrapper">
					<button type="button" class="filter-button"
									data-block-id="<?php echo esc_attr( $block_id ); ?>">
							<span class="dashicons dashicons-admin-settings"></span>
							<span class="filter-button-text">Filters</span>
							<span class="filter-count-badge" style="display: none;">0</span>
					</button>

					<!-- Filter Dropdown (hidden until button click) -->
					<div class="filter-dropdown" style="display: none;">
						<!-- Filter Header with close button -->
						<div class="filter-header">
							<h3 class="filter-title">Filter</h3>
							<button type="button" class="filter-close-btn" data-block-id="<?php echo esc_attr( $block_id ); ?>">✕</button>
						</div>

						<!-- Location Filter -->
						<div class="filter-group">
								<div class="filter-label-row">
									<label class="filter-label">Location</label>
									<button type="button" class="location-clear-btn" data-block-id="<?php echo esc_attr( $block_id ); ?>" style="display: none;">Clear Location</button>
								</div>
								<div class="location-filter-wrapper">
									<select
										id="location-filter-<?php echo esc_attr( $block_id ); ?>"
										class="location-filter-select"
										data-block-id="<?php echo esc_attr( $block_id ); ?>"
										multiple="multiple"
										style="width: 100%;">
									</select>
								</div>
						</div>

						<!-- Date Range Filter -->
						<div class="filter-group">
								<label class="filter-label">Date Range</label>
								<div class="date-range-inputs">
								<div class="date-input-group">
										<input type="date" class="date-input date-start-input"
														data-block-id="<?php echo esc_attr( $block_id ); ?>"
														data-date-type="start" />
								</div>
								<span class="date-separator">To</span>
								<div class="date-input-group">
										<input type="date" class="date-input date-end-input"
														data-block-id="<?php echo esc_attr( $block_id ); ?>"
														data-date-type="end" />
								</div>
								</div>
						</div>

						<!-- Action Buttons -->
						<div class="filter-actions">
								<button type="button" class="reset-btn">Reset</button>
								<button type="button" class="apply-btn">Apply</button>
						</div>
					</div>
				</div>
			</div>

		</div>

		<?php endif; ?>

		<div class="wetravel-trips-container <?php echo esc_attr( $display_type ); ?>-view"
			id="trips-container-<?php echo esc_attr( $block_id ); ?>"
			data-slug="<?php echo esc_attr( $slug ); ?>"
			data-env="<?php echo esc_attr( $env ); ?>"
			data-trip-type="<?php echo esc_attr( $trip_type ); ?>"
			data-date-start="<?php echo esc_attr( $date_start ); ?>"
			data-date-end="<?php echo esc_attr( $date_end ); ?>"
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
			data-wetravel-widget-type="<?php echo esc_attr( $wt_widget_type ); ?>"
			data-integration-type="<?php echo esc_attr( $integration_type ); ?>"
			data-tracked-server-side="true"
			<?php if (!empty($locations)) : ?>
			data-locations="<?php echo esc_attr( implode(';', $locations) ); ?>"
			<?php endif; ?>
			data-hydrate="true"
			data-search-visibility="<?php echo $search_visibility ? 'true' : 'false'; ?>"
			data-cache-status="<?php echo $is_using_cache ? 'cached' : 'fresh'; ?>"
			data-total-pages="<?php echo esc_attr( $pagination['total_pages'] ?? 1 ); ?>"
			data-total-trips="<?php echo esc_attr( $pagination['total_count'] ?? 0 ); ?>"
			data-current-page="<?php echo esc_attr( $pagination['page'] ?? 1 ); ?>"
			>
			<?php
				$allowed_html_tags = array(
					'div' => array(
							'class' => true,
							'data-env' => true,
							'data-version' => true,
							'data-uid' => true,
							'data-uuid' => true,
							'data-trip-uuid' => true,
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
					'span' => array(
						'class' => true,
						'style' => true,
					),
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
						'data-trip-uuid' => true,
					),
				);
			?>

			<?php if ( empty( $enhanced_trips ) ) : ?>
				<div class="no-trips">No trips found</div>
			<?php else : ?>

				<?php if ( 'carousel' === $display_type ) : ?>
						<div class="wetravel-carousel-wrapper">
								<div class="swiper-container-wrapper">
										<div class="swiper">
												<div class="swiper-wrapper">
														<?php
														foreach ( $enhanced_trips as $trip ) :
														?>
																<div class="swiper-slide">
																		<?php
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
														<?php
														endforeach;
														?>
												</div>
												<div class="swiper-pagination"></div>
										</div>

										<!-- Navigation buttons inside the container wrapper -->
										<div class="swiper-button-prev"></div>
										<div class="swiper-button-next"></div>
								</div>
						</div>
			<?php else : ?>
				<?php
				// With server-side pagination, all returned trips are visible
				// No need for visibility classes - API already paginated the results
				foreach ( $enhanced_trips as $trip ) :
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
						'visible-item' // All items visible with server-side pagination
					), $allowed_html_tags );
				endforeach;
				?>
			<?php endif; ?>

			<?php endif; ?>
		</div>

		<?php if ( ! empty( $enhanced_trips ) && 'carousel' !== $display_type && $pagination['total_pages'] > 1 ) : ?>
			<!-- Server-side pagination container -->
			<div id="pagination-<?php echo esc_attr( $block_id ); ?>"
				 class="wetravel-trips-pagination"
				 data-current-page="<?php echo esc_attr( $pagination['page'] ); ?>"
				 data-total-pages="<?php echo esc_attr( $pagination['total_pages'] ); ?>"
				 data-per-page="<?php echo esc_attr( $pagination['per_page'] ); ?>"
				 data-total-items="<?php echo esc_attr( $pagination['total_count'] ); ?>">
				<div class="pagination-controls">
					<?php
					$total_pages = intval( $pagination['total_pages'] );
					$current_page_num = intval( $pagination['page'] );

					// Previous button
					if ( $current_page_num > 1 ) {
						echo '<span class="page-nav page-prev" data-page="' . esc_attr( $current_page_num - 1 ) . '">&laquo;</span>';
					}

					// Page numbers with ellipsis for large page counts
					$show_pages = array();
					if ( $total_pages <= 7 ) {
						// Show all pages if 7 or fewer
						$show_pages = range( 1, $total_pages );
					} else {
						// Show first, last, current and neighbors
						$show_pages[] = 1;
						if ( $current_page_num > 3 ) {
							$show_pages[] = '...';
						}
						for ( $i = max( 2, $current_page_num - 1 ); $i <= min( $total_pages - 1, $current_page_num + 1 ); $i++ ) {
							$show_pages[] = $i;
						}
						if ( $current_page_num < $total_pages - 2 ) {
							$show_pages[] = '...';
						}
						$show_pages[] = $total_pages;
					}

					foreach ( $show_pages as $page ) {
						if ( '...' === $page ) {
							echo '<span class="page-ellipsis">...</span>';
						} else {
							$active_class = $page === $current_page_num ? 'active' : '';
							echo '<span class="page-number ' . esc_attr( $active_class ) . '" data-page="' . esc_attr( $page ) . '">' . esc_html( $page ) . '</span>';
						}
					}

					// Next button
					if ( $current_page_num < $total_pages ) {
						echo '<span class="page-nav page-next" data-page="' . esc_attr( $current_page_num + 1 ) . '">&raquo;</span>';
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
	// Enqueue trip link handler script
	wp_enqueue_script(
		'wetravel-trip-link-handler',
		plugins_url( 'assets/js/trip-link-handler.js', dirname( __FILE__ ) ),
		array( 'jquery' ),
		filemtime( plugin_dir_path( dirname( __FILE__ ) ) . 'assets/js/trip-link-handler.js' ),
		true
	);

	// Enqueue hydration script for hybrid SSR + JS pattern
	wp_enqueue_script(
		'wetravel-trips-hydration',
		plugins_url( 'assets/js/wetravel-hydration.js', dirname( __FILE__ ) ),
		array( 'jquery' ),
		filemtime( plugin_dir_path( dirname( __FILE__ ) ) . 'assets/js/wetravel-hydration.js' ),
		true
	);
	?>
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

			// For client-side loaded content, the spinner is handled in wetravel-hydration.js
			// The hydration system will automatically handle loading states

			// Add a fallback timeout to hide spinner after 15 seconds in case of errors
			setTimeout(function() {
				$('.wetravel-trips-loading').fadeOut();
			}, 15000);
		});
	";

	// Add the inline script to the output.
	wp_register_script( 'wetravel-trips-loading', '', array( 'jquery' ), '1.0.0', true );
	wp_add_inline_script( 'wetravel-trips-loading', $inline_script );
	wp_enqueue_script( 'wetravel-trips-loading' );

	if ( function_exists( 'wetravel_track_widget_view' ) ) {
		$event_data = array(
			'wt_user_id' => $wetravel_trips_user_id,
			'wt_widget_type' => $wt_widget_type,
			'display_type' => $display_type,
			'button_type' => $button_type,
			'integration_type' => $integration_type,
			'trip_type' => $trip_type,
		);
		wetravel_track_widget_view( $event_data );
	}

	// Note: Hydration is now handled automatically by the wetravel-hydration.js script
	// based on the data-hydrate="true" attribute. No need for inline script initialization.
	// The container already has all necessary data attributes for auto-hydration.

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
		$button_url = $env . '/checkout_embed?uuid=' . $trip['uuid'] . '&source=wp_widget_book_now';
	} else {
		$button_url = $env . '/trips/' . $trip['uuid'] . '?source=wp_widget_trip_link';
		// Use 'url' field from new API if available
		if ( isset( $trip['url'] ) ) {
			$button_url = $trip['url'];
			// Add source parameter to existing URL
			$separator = strpos( $button_url, '?' ) !== false ? '&' : '?';
			$button_url .= $separator . 'source=wp_widget_trip_link';
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

	// Get values using helper functions (supports both new API and mock data formats)
	$trip_image    = wtwidget_get_trip_image( $trip );
	$trip_location = wtwidget_get_trip_location( $trip );
	$trip_price    = wtwidget_get_trip_price( $trip );
	$trip_dates    = wtwidget_get_trip_dates( $trip );
	$is_recurring  = wtwidget_is_trip_recurring( $trip );
	$trip_length   = wtwidget_get_trip_length( $trip );

	if ( 'vertical' === $options['displayType'] || ('grid' === $options['displayType'] && 'trip_link' === $options['buttonType'])) {
		$html .= '<div class="trip-item ' . esc_attr( $visibility_class ) . '" data-trip-uuid="' . esc_attr( $trip['uuid'] ) . '">';
	} elseif ( 'book_now' === $options['buttonType'] && ('grid' === $options['displayType'] || 'carousel' === $options['displayType']) ) {
		$html .= sprintf(
			'<div class="trip-item wtrvl-checkout_button %s" data-env="%s" data-version="v0.3" data-uid="%s" data-uuid="%s" href="%s" style="cursor: pointer;">',
			esc_attr( $visibility_class ),
			esc_attr( $options['env'] ),
			esc_attr( $options['wetravelUserID'] ),
			esc_attr( $trip['uuid'] ),
			esc_url( $button_url )
		);
	} elseif ( 'carousel' === $options['displayType'] && 'trip_link' === $options['buttonType'] ) {
		$html .= sprintf(
			'<div class="trip-item %s" data-trip-uuid="%s" target="_blank" href="%s" style="cursor: pointer;">',
			esc_attr( $visibility_class ),
			esc_attr( $trip['uuid'] ),
			esc_url( $button_url )
		);
	} else {
		// Fallback for any other cases
		$html .= '<div class="trip-item ' . esc_attr( $visibility_class ) . '" data-trip-uuid="' . esc_attr( $trip['uuid'] ) . '">';
	}

	// Image.
	if ( ! empty( $trip_image ) ) {
		$rendered_image = wtwidget_render_external_image(
			$trip_image,
			$trip['title'] ?? '',
			array(
				'class' => 'trip-image-thumbnail',
				'width' => 400,
				'height' => 300
			)
		);

		// Add date overlay for carousel and grid display types.
		if ( in_array( $options['displayType'], array( 'carousel', 'grid' ) ) ) {
			$date_overlay = '';
			if ( ! $is_recurring && ! empty( $trip_dates ) ) {
				$date_overlay = '<div class="trip-date-overlay trip-tag">' . esc_html( $trip_dates ) . '</div>';
			} elseif ( $is_recurring && ! empty( $trip_length ) ) {
				$date_overlay = sprintf(
					'<div class="trip-date-overlay trip-tag">%s days</div>',
					esc_html( $trip_length )
				);
			}

			$html .= '<div class="trip-image">' . $rendered_image . $date_overlay . '</div>';
		} else {
			$html .= '<div class="trip-image">' . $rendered_image . '</div>';
		}
	} else {
		$html .= '<div class="no-image-placeholder"><span>No Image Available</span></div>';
	}

	// Content.
	$html .= '<div class="trip-content">';
	$html .= '<div class="trip-title-desc">';
	$html .= '<h3>' . esc_html( $trip['title'] ?? '' ) . '</h3>';

	// Description with See More functionality.
	$description = $trip['description'] ?? '';
	if ( ! empty( $description ) ) {
		// Remove emojis from description to prevent layout issues.
		$clean_description = wtwidget_remove_emojis_comprehensive( $description );

		// Generate trip URL for See More link
		$trip_url = wtwidget_get_button_url( $trip, array(
			'env' => $options['env'],
			'buttonType' => 'trip_link'
		) );

		// Show description with See More link (always visible)
		$html .= '<div class="trip-description-wrapper">';
		$html .= '<div class="trip-description">' . wp_kses_post( $clean_description ) . '</div>';
		$html .= '<a href="' . esc_url( $trip_url ) . '" class="learn-more-link" target="_blank">See More <span class="dashicons dashicons-arrow-right-alt" style="vertical-align:middle; text-decoration: none;"></span></a>';
		$html .= '</div>';
	}
	$html .= '</div>'; // Close trip-title-desc.

	if ( 'carousel' === $options['displayType'] ) {
		$html .= "<div class='trip-loc-price'>";
	}

	// Date or duration.
	$html .= '<div class="trip-loc-duration">';

	if ( 'vertical' === $options['displayType'] ) {
		if ( ! $is_recurring && ! empty( $trip_dates ) ) {
			$html .= '<div class="trip-date trip-tag">' . esc_html( $trip_dates ) . '</div>';
		} elseif ( $is_recurring && ! empty( $trip_length ) ) {
			$html .= sprintf(
				'<div class="trip-duration trip-tag">%s days</div>',
				esc_html( $trip_length )
			);
		}
	}
	$html .= '<div class="trip-location trip-tag">' . esc_html( $trip_location ) . '</div>';
	$html .= '</div>'; // Close trip-loc-duration.

	if ( 'carousel' !== $options['displayType'] ) {
		$html .= '</div>'; // Close trip-content.
	}

	// Price and button section.
	$html .= '<div class="trip-price-button">';

	// Price.
	if ( ! empty( $trip_price ) ) {
		$html .= sprintf(
			'<div class="trip-price"><p>From</p> <span>%s</span></div>',
			esc_html( $trip_price['amount'] )
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
				'<a href="%s" class="trip-button" data-trip-uuid="%s" target="_blank" style="%s">%s</a>',
				esc_url( $button_url ),
				esc_attr( $trip['uuid'] ),
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
 * More comprehensive emoji removal function
 * This handles even more edge cases and complex emoji sequences
 */
function wtwidget_remove_emojis_comprehensive($text) {
    if (empty($text)) {
        return $text;
    }

    // Remove all emoji and symbol characters.
    $patterns = array(
        '/[\x{1F600}-\x{1F64F}]/u',     // Emoticons.
        '/[\x{1F300}-\x{1F5FF}]/u',     // Misc Symbols and Pictographs.
        '/[\x{1F680}-\x{1F6FF}]/u',     // Transport and Map Symbols.
        '/[\x{1F1E0}-\x{1F1FF}]/u',     // Regional country flags.
        '/[\x{2600}-\x{26FF}]/u',       // Misc symbols.
        '/[\x{2700}-\x{27BF}]/u',       // Dingbats.
        '/[\x{1F900}-\x{1F9FF}]/u',     // Supplemental Symbols and Pictographs.
        '/[\x{1FA70}-\x{1FAFF}]/u',     // Symbols and Pictographs Extended-A.
        '/[\x{FE00}-\x{FE0F}]/u',       // Variation Selectors.
        '/[\x{200D}]/u',                // Zero Width Joiner.
        '/[\x{20E3}]/u',                // Combining Enclosing Keycap.
        '/[\x{E0020}-\x{E007F}]/u',     // Tags.
        '/[\x{1F004}]/u',               // Mahjong Tile Red Dragon.
        '/[\x{1F0CF}]/u',               // Playing Card Black Joker.
        '/[\x{1F18E}]/u',               // Negative Squared AB.
        '/[\x{3030}]/u',                // Wavy Dash.
        '/[\x{303D}]/u',                // Part Alternation Mark.
        '/[\x{3297}]/u',                // Circled Ideograph Congratulation.
        '/[\x{3299}]/u',                // Circled Ideograph Secret.
        '/[\x{203C}]/u',                // Double Exclamation Mark.
        '/[\x{2049}]/u',                // Exclamation Question Mark.
        '/[\x{25AA}-\x{25AB}]/u',       // Black/White Small Square.
        '/[\x{25B6}]/u',                // Black Right-Pointing Triangle.
        '/[\x{25C0}]/u',                // Black Left-Pointing Triangle.
        '/[\x{25FB}-\x{25FE}]/u',       // Various squares.
        '/[\x{2B50}]/u',                // White Medium Star ⭐.
        '/[\x{2B55}]/u',                // Heavy Large Circle.
    );

    // Remove all emoji and symbol characters.
    foreach ($patterns as $pattern) {
        $text = preg_replace($pattern, '', $text);
    }

    // Clean up extra spaces and trim.
    $text = preg_replace('/\s+/', ' ', $text);
    $text = trim($text);

    return $text;
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
 * Get mock trips data for preview functionality
 *
 * @param array $attributes Block attributes with mock data parameters.
 * @return array Array of mock trips.
 */
function wtwidget_get_mock_trips_data($attributes) {
	// Load mock trips data from JSON file
	$json_file_path = plugin_dir_path(dirname(__FILE__)) . 'assets/data/mock-trips.json';

	if (!file_exists($json_file_path)) {
		error_log('WeTravel Widgets: Mock trips JSON file not found at ' . $json_file_path);
		return array();
	}

	$json_content = file_get_contents($json_file_path);
	if ($json_content === false) {
		error_log('WeTravel Widgets: Failed to read mock trips JSON file');
		return array();
	}

	$json_data = json_decode($json_content, true);
	if (json_last_error() !== JSON_ERROR_NONE) {
		error_log('WeTravel Widgets: Invalid JSON in mock trips file: ' . json_last_error_msg());
		return array();
	}

	// Support new format ('data') - same as API response
	if (!isset($json_data['data']) || !is_array($json_data['data'])) {
		error_log('WeTravel Widgets: Invalid JSON structure in mock trips file');
		return array();
	}

	$mock_trips = $json_data['data'];

	// Convert image paths to full URLs (images array format)
	$plugin_url = plugins_url('', dirname(__FILE__));
	foreach ($mock_trips as &$trip) {
		if (isset($trip['images']) && is_array($trip['images'])) {
			foreach ($trip['images'] as &$image) {
				if (isset($image['url']) && strpos($image['url'], 'http') !== 0) {
					$image['url'] = $plugin_url . '/' . $image['url'];
				}
			}
			unset($image);
		}
	}
	unset($trip); // Break the reference

	// Filter by trip type using helper function
	$trip_type = isset($attributes['mockTripType']) ? $attributes['mockTripType'] : 'all';
	if ('recurring' === $trip_type) {
		$mock_trips = array_filter($mock_trips, function($trip) {
			return wtwidget_is_trip_recurring($trip);
		});
	} elseif ('one-time' === $trip_type) {
		$mock_trips = array_filter($mock_trips, function($trip) {
			return !wtwidget_is_trip_recurring($trip);
		});
	}

	// Handle location assignment (update destination.title)
	$selected_locations = isset($attributes['mockLocations']) ? $attributes['mockLocations'] : array();
	if (!empty($selected_locations)) {
		// Assign selected locations to trips randomly
		$mock_trips = array_map(function($trip, $index) use ($selected_locations) {
			$random_location_index = $index % count($selected_locations);
			$trip['destination']['title'] = $selected_locations[$random_location_index];
			return $trip;
		}, array_values($mock_trips), array_keys($mock_trips));
	}

	return array_values($mock_trips);
}

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
