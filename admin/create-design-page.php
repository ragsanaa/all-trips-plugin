<?php
/**
 * Admin: Create Widget page for WeTravel Widgets Plugin.
 *
 * @package WordPress
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once dirname(__FILE__, 2) . '/includes/functions.php';

/**
 * Handle form submission for widget creation/editing during admin_init
 */
function wtwidget_handle_design_form_submission() {
	// Only process if we have POST data that indicates a form submission
	if ( ! isset( $_POST['save_design'] ) || ! isset( $_POST['wetravel_trips_design_nonce'] ) ) {
		return;
	}

	// Verify nonce first before processing any data
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wetravel_trips_design_nonce'] ) ), 'wetravel_trips_design_action' ) ) {
		return; // Silently return if nonce verification fails
	}

	// Now safely check the sanitized GET parameter
	$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
	if ( $page !== 'wetravel-trips-create-design' ) {
		return;
	}

	wtwidget_process_form_submission();
}
add_action( 'admin_init', 'wtwidget_handle_design_form_submission' );

/**
 * Process the actual form submission
 */
function wtwidget_process_form_submission() {
	// Verify user capabilities
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wetravel-widgets' ) );
	}

	// Verify nonce
	if (
		! isset( $_POST['wetravel_trips_design_nonce'] ) ||
		! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wetravel_trips_design_nonce'] ) ), 'wetravel_trips_design_action' )
	) {
		wp_die( esc_html__( 'Invalid nonce verification', 'wetravel-widgets' ) );
	}

	// Get design ID if editing
	$design_id = '';
	$editing = false;
	if ( isset( $_POST['design_id'] ) ) {
		$design_id = sanitize_text_field( wp_unslash( $_POST['design_id'] ) );
		$editing = true;
	}

	// Validate keyword uniqueness if provided
	$keyword = isset( $_POST['design_keyword'] ) ? sanitize_text_field( wp_unslash( $_POST['design_keyword'] ) ) : '';
	$keyword_error = false;

	if ( ! empty( $keyword ) ) {
		$designs = get_option( 'wetravel_trips_designs', array() );
		foreach ( $designs as $id => $existing_design ) {
			if ( isset( $existing_design['keyword'] ) && $existing_design['keyword'] === $keyword && $id !== $design_id ) {
				$keyword_error = true;
				break;
			}
		}
	}

	if ( $keyword_error ) {
		$redirect_url = add_query_arg(
			array(
				'page'        => 'wetravel-trips-create-design',
				'error'       => 'keyword_exists',
				'error_nonce' => wp_create_nonce( 'wetravel_error_message' )
			),
			admin_url( 'admin.php' )
		);

		if ( $editing ) {
			$redirect_url = add_query_arg(
				array(
					'edit'     => $design_id,
					'_wpnonce' => wp_create_nonce( 'wetravel_trips_edit_nonce' ),
				),
				$redirect_url
			);
		}

		wp_safe_redirect( $redirect_url );
		exit;
	}

	// Get date range values if trip type is one-time
	$date_range_start = '';
	$date_range_end = '';
	if ( isset( $_POST['trip_type'] ) && 'one-time' === $_POST['trip_type'] ) {
		$date_range_start = isset( $_POST['date_range_start'] ) ? sanitize_text_field( wp_unslash( $_POST['date_range_start'] ) ) : '';
		$date_range_end = isset( $_POST['date_range_end'] ) ? sanitize_text_field( wp_unslash( $_POST['date_range_end'] ) ) : '';
	}

	$designs = get_option( 'wetravel_trips_designs', array() );
	$current_design = isset( $designs[$design_id] ) ? $designs[$design_id] : array( 'created' => time() );

	$new_design = array(
		'name'           => isset( $_POST['design_name'] ) ? sanitize_text_field( wp_unslash( $_POST['design_name'] ) ) : '',
		'displayType'    => isset( $_POST['display_type'] ) ? sanitize_text_field( wp_unslash( $_POST['display_type'] ) ) : '',
		'buttonType'     => isset( $_POST['button_type'] ) ? sanitize_text_field( wp_unslash( $_POST['button_type'] ) ) : '',
		'buttonText'     => isset( $_POST['button_text'] ) ? sanitize_text_field( wp_unslash( $_POST['button_text'] ) ) : '',
		'buttonColor'    => isset( $_POST['button_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['button_color'] ) ) : '',
		'keyword'        => $keyword,
		'tripType'       => isset( $_POST['trip_type'] ) ? sanitize_text_field( wp_unslash( $_POST['trip_type'] ) ) : '',
		'dateRangeStart' => $date_range_start,
		'dateRangeEnd'   => $date_range_end,
		'wtWidgetType'     => isset( $_POST['wt_widget_type'] ) ? sanitize_text_field( wp_unslash( $_POST['wt_widget_type'] ) ) : 'all-trips',
		'created'        => $current_design['created'],
		'modified'       => time(),
		'locations'      => isset($_POST['trip_location']) ? array_map('sanitize_text_field', wp_unslash($_POST['trip_location'])) : array(),
		'searchVisibility' => isset($_POST['search_visibility']) ? (bool) $_POST['search_visibility'] : false,
		'itemsPerSlide'  => isset($_POST['items_per_slide']) ? intval($_POST['items_per_slide']) : get_option('wetravel_trips_items_per_slide', 3),
		'itemsPerRow'    => isset($_POST['items_per_row']) ? intval($_POST['items_per_row']) : get_option('wetravel_trips_items_per_row', 3),
		'itemsPerPage'   => isset($_POST['items_per_page']) ? intval($_POST['items_per_page']) : get_option('wetravel_trips_items_per_page', 10),
		'borderRadius'   => isset($_POST['border_radius']) ? intval($_POST['border_radius']) : get_option('wetravel_trips_border_radius', 6),
	);

	// Generate a new ID if we're not editing
	if ( ! $editing ) {
		$design_id = 'design_' . time() . '_' . wp_rand( 1000, 9999 );
	}

	$designs[$design_id] = $new_design;
	update_option( 'wetravel_trips_designs', $designs );

	// Track widget creation/update with WeTravel user state tracking
	$has_consent = get_option( 'wetravel_consent_given', false );
	if ( $has_consent && function_exists( 'wetravel_track_user_state' ) ) {
		$wt_user_id = get_option( 'wetravel_trips_user_id', '' );
		$wt_user_slug = get_option( 'wetravel_trips_slug', '' );

		wetravel_track_user_state( $wt_user_id, $wt_user_slug, true, false, array() );
	}

	// If editing, redirect back to edit the same widget
	if ( $editing ) {
		$redirect_args = array(
			'page'          => 'wetravel-trips-create-design',
			'updated'       => '1',
			'edit'          => $design_id,
			'_wpnonce'      => wp_create_nonce( 'wetravel_trips_edit_nonce' ),
			'updated_nonce' => wp_create_nonce( 'wetravel_updated_message' ),
		);
		$redirect_url = add_query_arg( $redirect_args, admin_url( 'admin.php' ) );
	} else {
		// If creating new widget, redirect to fresh create page with success message
		$redirect_args = array(
			'page'          => 'wetravel-trips-create-design',
			'updated'       => '1',
			'created'       => 'success',
			'updated_nonce' => wp_create_nonce( 'wetravel_updated_message' ),
		);
		$redirect_url = add_query_arg( $redirect_args, admin_url( 'admin.php' ) );
	}

	wp_safe_redirect( $redirect_url );
	exit;
}

/** Render Create Widget Page. */
function wtwidget_trip_create_design_page() {
	// Verify user capabilities
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wetravel-widgets' ) );
	}

	// Default values
	$design = array(
		'name'           => '',
		'displayType'    => 'vertical',
		'buttonType'     => 'book_now',
		'buttonText'     => 'Book Now',
		'buttonColor'    => '#33ae3f',
		'keyword'        => '',
		'tripType'       => 'all',
		'dateRangeStart' => '',
		'dateRangeEnd'   => '',
		'wtWidgetType'     => 'all-trips',
		'searchVisibility' => false,
		'itemsPerSlide'  => get_option('wetravel_trips_items_per_slide', 3),
		'itemsPerRow'    => get_option('wetravel_trips_items_per_row', 3),
		'itemsPerPage'   => get_option('wetravel_trips_items_per_page', 10),
		'borderRadius'   => get_option('wetravel_trips_border_radius', 6),
		'created'        => time(),
	);

	$editing = false;
	$design_id = '';
	$success_message = '';
	$error_message = '';

	// Check for error messages with nonce verification
	$error_param = isset( $_GET['error'] ) ? sanitize_text_field( wp_unslash( $_GET['error'] ) ) : '';
	$error_nonce = isset( $_GET['error_nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['error_nonce'] ) ) : '';
	if ( $error_param === 'keyword_exists' && wp_verify_nonce( $error_nonce, 'wetravel_error_message' ) ) {
		$error_message = 'This keyword is already in use. Please choose a unique keyword.';
	}

	// Check for successful update or creation with nonce verification
	$updated_param = isset( $_GET['updated'] ) ? sanitize_text_field( wp_unslash( $_GET['updated'] ) ) : '';
	$updated_nonce = isset( $_GET['updated_nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['updated_nonce'] ) ) : '';
	if ( $updated_param === '1' && wp_verify_nonce( $updated_nonce, 'wetravel_updated_message' ) ) {
		$edit_param = isset( $_GET['edit'] ) ? sanitize_text_field( wp_unslash( $_GET['edit'] ) ) : '';
		if ( ! empty( $edit_param ) ) {
			$success_message = 'Widget updated successfully.';

			// Verify edit nonce
			if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'wetravel_trips_edit_nonce' ) ) {
				wp_die( esc_html__( 'Invalid nonce verification', 'wetravel-widgets' ) );
			}

			$design_id = sanitize_text_field( wp_unslash( $_GET['edit'] ) );
			$designs = get_option( 'wetravel_trips_designs', array() );

			if ( isset( $designs[$design_id] ) ) {
				$design = $designs[$design_id];
				$shortcode = wtwidget_generate_shortcode_with_params($design, $design_id);
			}
		} else {
			$created_param = isset( $_GET['created'] ) ? sanitize_text_field( wp_unslash( $_GET['created'] ) ) : '';
			if ( $created_param === 'success' ) {
				$success_message = 'Widget created successfully.';
				// No need to load any specific design data since we want a fresh create form
			}
		}
	}

	// Check if we're editing an existing design
	if ( isset( $_GET['edit'] ) && ! empty( $_GET['edit'] ) ) {
		// Verify edit nonce
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'wetravel_trips_edit_nonce' ) ) {
			wp_die( esc_html__( 'Invalid nonce verification', 'wetravel-widgets' ) );
		}

		$design_id = sanitize_text_field( wp_unslash( $_GET['edit'] ) );
		$designs = get_option( 'wetravel_trips_designs', array() );

		if ( isset( $designs[$design_id] ) ) {
			$design = $designs[$design_id];
			$editing = true;
		}
	}

	?>
	<div class="wrap">
		<h1>WeTravel Widgets Plugin - <?php echo $editing ? 'Edit Widget' : 'Create Widget'; ?></h1>

		<div class="nav-tab-wrapper">
			<a href="?page=wetravel-trips-instructions" class="nav-tab">Instructions</a>
			<a href="?page=wetravel-trips-setup" class="nav-tab">Setup</a>
			<a href="?page=wetravel-trips-design-library" class="nav-tab">Widget Library</a>
			<a href="?page=wetravel-trips-create-design" class="nav-tab nav-tab-active"><?php echo $editing ? 'Edit Widget' : 'Create Widget'; ?></a>
		</div>

		<?php if ( ! empty( $success_message ) ) : ?>
			<div class="notice notice-success is-dismissible">
				<p><?php echo esc_html( $success_message ); ?></p>
				<?php if ( isset( $shortcode ) ) : ?>
					<p>Use this shortcode to display your widget: <code><?php echo esc_html( $shortcode ); ?></code></p>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $error_message ) ) : ?>
			<div class="notice notice-error is-dismissible">
				<p><?php echo esc_html( $error_message ); ?></p>
			</div>
		<?php endif; ?>

		<div class="wetravel-trips-create-design-container">
			<div class="wetravel-trips-design-form-and-preview">
				<div class="wetravel-trips-design-form">
					<form method="post" action="">
						<?php wp_nonce_field( 'wetravel_trips_design_action', 'wetravel_trips_design_nonce' ); ?>

						<div class="wetravel-trips-form-field">
							<label for="design_name">Widget Name <span style="color:red;">*</span></label>
							<input type="text" id="design_name" name="design_name" value="<?php echo esc_attr( $design['name'] ); ?>" required>
							<p class="description">Give your widget a name to help you identify it later.</p>
						</div>

						<div class="wetravel-trips-form-field">
							<label for="design_keyword">Widget Keyword</label>
							<input type="text" id="design_keyword" name="design_keyword" value="<?php echo isset( $design['keyword'] ) ? esc_attr( $design['keyword'] ) : ''; ?>">
							<p class="description">Optional. Set a unique keyword to use in shortcode. If not provided, the widget ID will be used.</p>
						</div>

						<div class="wetravel-trips-form-field">
							<label for="display_type">Trip Display Type</label>
							<select id="display_type" name="display_type">
								<option value="vertical" <?php selected( $design['displayType'], 'vertical' ); ?>>Vertical</option>
								<option value="carousel" <?php selected( $design['displayType'], 'carousel' ); ?>>Carousel</option>
								<option value="grid" <?php selected( $design['displayType'], 'grid' ); ?>>Grid</option>
							</select>
						</div>

						<div class="wetravel-trips-form-field">
							<label for="trip_location">Trip Locations</label>
							<?php
								$env = get_option('wetravel_trips_env', 'https://pre.wetravel.to');
								$slug = get_option('wetravel_trips_slug', '');

								// Get unique locations
								$locations = array();

								// Only try to fetch locations if slug is configured
								if (!empty($slug) && function_exists('wtwidget_build_api_url') && function_exists('wtwidget_get_trips_data') && function_exists('wtwidget_get_trip_locations')) {
									try {
										// Get trips data (we only need basic data for locations)
										$api_url = wtwidget_build_api_url($env, $slug, array(
											'trip_type' => isset($design['tripType']) ? $design['tripType'] : 'all'
										));
										$trips = wtwidget_get_trips_data($api_url);

										if (is_array($trips)) {
											$locations = wtwidget_get_trip_locations($trips);
										}
									} catch (Exception $e) {
										$locations = array();
									}
								}

								// Get selected locations from design
								$selected_locations = isset($design['locations']) ? (array)$design['locations'] : array();
							?>
							<select id="trip_location" name="trip_location[]" multiple="multiple" class="wetravel-select2">
								<?php if (empty($locations)) : ?>
									<option value="" disabled>Configure WeTravel settings first to load locations</option>
								<?php else : ?>
									<?php foreach ($locations as $location) : ?>
										<option value="<?php echo esc_attr($location); ?>"
											<?php selected(in_array($location, $selected_locations), true); ?>>
											<?php echo esc_html($location); ?>
										</option>
									<?php endforeach; ?>
								<?php endif; ?>
							</select>
							<p class="description"><?php echo empty($locations) ? 'Please configure your WeTravel embed code in Setup first.' : 'Select one or more locations. Leave empty to show all locations.'; ?></p>
						</div>

						<div class="wetravel-trips-form-field">
							<label for="trip_type">Trip Type</label>
							<select id="trip_type" name="trip_type">
								<option value="all" <?php selected( isset( $design['tripType'] ) ? $design['tripType'] : 'all', 'all' ); ?>>All Trips</option>
								<option value="recurring" <?php selected( isset( $design['tripType'] ) ? $design['tripType'] : '', 'recurring' ); ?>>Recurring Trips</option>
								<option value="one-time" <?php selected( isset( $design['tripType'] ) ? $design['tripType'] : '', 'one-time' ); ?>>One-Time Trips</option>
							</select>
						</div>

						<div id="date-range-container" class="wetravel-trips-form-field" style="display: none;">
							<label>Date Range for Start Date</label>
							<div class="date-range-inputs">
								<div>
									<label for="date_range_start">From</label>
									<input type="date" id="date_range_start" name="date_range_start" value="<?php echo isset( $design['dateRangeStart'] ) ? esc_attr( $design['dateRangeStart'] ) : ''; ?>">
								</div>
								<div>
									<label for="date_range_end">To</label>
									<input type="date" id="date_range_end" name="date_range_end" value="<?php echo isset( $design['dateRangeEnd'] ) ? esc_attr( $design['dateRangeEnd'] ) : ''; ?>">
								</div>
							</div>
						</div>

						<div class="wetravel-trips-form-field">
							<label for="button_type">Button Type</label>
							<select id="button_type" name="button_type">
								<option value="book_now" <?php selected( $design['buttonType'], 'book_now' ); ?>>Book Now</option>
								<option value="trip_link" <?php selected( $design['buttonType'], 'trip_link' ); ?>>Trip Link</option>
							</select>
						</div>

						<div class="wetravel-trips-form-field">
							<label for="button_text">Button Text</label>
							<input type="text" id="button_text" name="button_text" value="<?php echo esc_attr( $design['buttonText'] ); ?>">
						</div>

						<div class="wetravel-trips-form-field">
							<label for="button_color">Button Color</label>
							<input type="text" id="button_color" name="button_color" class="color-picker" value="<?php echo esc_attr( $design['buttonColor'] ); ?>">
						</div>

						<!-- Widget Type field - System defined, not user editable -->
						<!-- This field is managed by the system for future button design implementations -->
						<input type="hidden" id="wt_widget_type" name="wt_widget_type" value="<?php echo esc_attr( isset( $design['wtWidgetType'] ) ? $design['wtWidgetType'] : 'all-trips' ); ?>">

						<div class="wetravel-trips-form-field">
							<label for="search_visibility">Display Search Bar</label>
							<label class="toogle-switch">
								<input type="checkbox" id="search_visibility" name="search_visibility" value="1" <?php echo isset( $design['searchVisibility'] ) ? checked( $design['searchVisibility'], 1, false ) : ''; ?>>
								<span class="toogle-switch-slider"></span>
							</label>
							<p class="description">This is not available for carousel display type.</p>
						</div>

						<!-- Hidden fields for shortcode generation - values will be set from global settings -->
						<input type="hidden" id="items_per_slide" name="items_per_slide" value="<?php echo isset( $design['itemsPerSlide'] ) ? esc_attr( $design['itemsPerSlide'] ) : esc_attr(get_option('wetravel_trips_items_per_slide', 3)); ?>">
						<input type="hidden" id="items_per_row" name="items_per_row" value="<?php echo isset( $design['itemsPerRow'] ) ? esc_attr( $design['itemsPerRow'] ) : esc_attr(get_option('wetravel_trips_items_per_row', 3)); ?>">
						<input type="hidden" id="items_per_page" name="items_per_page" value="<?php echo isset( $design['itemsPerPage'] ) ? esc_attr( $design['itemsPerPage'] ) : esc_attr(get_option('wetravel_trips_items_per_page', 10)); ?>">
						<input type="hidden" id="border_radius" name="border_radius" value="<?php echo isset( $design['borderRadius'] ) ? esc_attr( $design['borderRadius'] ) : esc_attr(get_option('wetravel_trips_border_radius', 6)); ?>">

						<div class="wetravel-trips-form-actions">
							<input type="submit" name="save_design" class="button button-primary" value="Save Widget">
							<a href="?page=wetravel-trips-design-library" class="button button-secondary">Cancel</a>
							<?php if ( $editing ) : ?>
								<input type="hidden" name="design_id" value="<?php echo esc_attr( $design_id ); ?>">
							<?php endif; ?>
						</div>
					</form>
				</div>

				<div class="wetravel-trips-design-preview-container">
					<h3>Live Preview</h3>
					<div id="design-preview" class="wetravel-trips-preview">
						<!-- Preview will be updated by JavaScript -->
						<div class="wetravel-trips-preview-layout">
							<div class="preview-display-type">Loading preview...</div>
						</div>
					</div>

					<div class="wetravel-trips-shortcode-generator">
						<h4>Generated Shortcode</h4>
						<div class="shortcode-preview">
							<?php if ( $editing ) : ?>
								<code><?php echo esc_html(wtwidget_generate_shortcode_with_params($design, $design_id)); ?></code>
								<button class="button button-small wetravel-trips-copy-shortcode"
										data-shortcode='<?php echo esc_attr(wtwidget_generate_shortcode_with_params($design, $design_id)); ?>'>Copy</button>
							<?php else : ?>
								<p>Shortcode will be generated after saving.</p>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
	// Enqueue Select2 library
	wp_enqueue_style(
		'select2',
		plugins_url('assets/css/select2.min.css', dirname(__FILE__)),
		array(),
		filemtime(plugin_dir_path(dirname(__FILE__)) . 'assets/css/select2.min.css')
	);
	wp_enqueue_script(
		'select2',
		plugins_url('assets/js/select2.min.js', dirname(__FILE__)),
		array('jquery'),
		filemtime(plugin_dir_path(dirname(__FILE__)) . 'assets/js/select2.min.js'),
		true
	);

	// Enqueue admin scripts
	wp_enqueue_script(
		'wetravel-trips-admin-scripts',
		plugins_url('js/admin-scripts.js', __FILE__),
		array('jquery', 'select2'),
		filemtime(plugin_dir_path(__FILE__) . 'js/admin-scripts.js'),
		true
	);

	// Localize script for AJAX - pass data to admin-scripts.js
	wp_localize_script('wetravel-trips-admin-scripts', 'wetravel_ajax', array(
		'ajaxurl' => admin_url('admin-ajax.php'),
		'nonce' => wp_create_nonce('wetravel_trips_nonce'),
		'design_id' => $design_id
	));

	// Initialize Select2
	?>
	<script type="text/javascript">
		jQuery(document).ready(function($) {
			$('.wetravel-select2').select2({
				placeholder: 'Select locations',
				allowClear: true,
				width: '100%'
			});

			// Add custom styles for Select2
			$('<style>')
				.prop('type', 'text/css')
				.html(`
					.select2-container--default .select2-selection--multiple {
						border: 1px solid #8c8f94;
						border-radius: 4px;
						min-height: 35px;
						max-height: 80px;
						overflow-y: auto;
					}
					.select2-container--default.select2-container--focus .select2-selection--multiple {
						border-color: #2271b1;
						box-shadow: 0 0 0 1px #2271b1;
						outline: 2px solid transparent;
					}
					.select2-container--default .select2-results>.select2-results__options {
						max-height: 200px;
						overflow-y: auto;
					}
					.select2-container--default .select2-selection--multiple .select2-selection__rendered {
						display: flex;
						flex-wrap: wrap;
						gap: 4px;
						padding: 4px;
					}
					.select2-container--default .select2-selection--multiple .select2-selection__choice {
						margin: 0;
					}
				`)
				.appendTo('head');
		});
	</script>
	<?php
}
