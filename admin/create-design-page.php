<?php
/**
 * Admin: Create Widget page for WeTravel Widgets Plugin.
 *
 * @package WordPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/** Render Create Widget Page. */
function wetravel_trips_create_design_page() {
	// Default values.
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
		'created'        => time(),
	);

	$editing   = false;
	$design_id = '';

	if ( isset( $_GET['updated'] ) && '1' === $_GET['updated'] ) {
		$success_message = 'Widget updated successfully.';

		// If we're in edit mode, generate the shortcode.
		if ( isset( $_GET['edit'] ) && ! empty( $_GET['edit'] ) ) {
			$design_id = sanitize_text_field( wp_unslash( $_GET['edit'] ) );
			$designs   = get_option( 'wetravel_trips_designs', array() );

			if ( isset( $designs[ $design_id ] ) ) {
				$design    = $designs[ $design_id ];
				$shortcode = '[wetravel_trips widget="' . ( ! empty( $design['keyword'] ) ? $design['keyword'] : $design_id ) . '"]';
			}
		}
	}

	// Check if we're editing an existing design.
	if ( isset( $_GET['edit'] ) && ! empty( $_GET['edit'] ) ) {
		// Only check nonce when editing.
		if ( isset( $_GET['edit'] ) && ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'wetravel_trips_edit_nonce' ) ) ) {
			wp_die( 'Security check failed' );
		}

		$design_id = sanitize_text_field( wp_unslash( $_GET['edit'] ) );
		$designs   = get_option( 'wetravel_trips_designs', array() );

		if ( isset( $designs[ $design_id ] ) ) {
			$design  = $designs[ $design_id ];
			$editing = true;
		}
	}

	// Handle form submission.
	if ( isset( $_POST['save_design'] ) ) {
		// Validate keyword uniqueness if provided.
		$keyword       = isset( $_POST['design_keyword'] ) ? sanitize_text_field( wp_unslash( $_POST['design_keyword'] ) ) : '';
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
			$error_message = 'This keyword is already in use. Please choose a unique keyword.';
		} else {
			// Get date range values if trip type is one-time.
			$date_range_start = '';
			$date_range_end   = '';
			if ( isset( $_POST['trip_type'] ) && 'one-time' === $_POST['trip_type'] ) {
				$date_range_start = isset( $_POST['date_range_start'] ) ? sanitize_text_field( wp_unslash( $_POST['date_range_start'] ) ) : '';
				$date_range_end   = isset( $_POST['date_range_end'] ) ? sanitize_text_field( wp_unslash( $_POST['date_range_end'] ) ) : '';
			}

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
				'created'        => $design['created'],
				'modified'       => time(),
			);

			$designs = get_option( 'wetravel_trips_designs', array() );

			// Generate a new ID if we're not editing.
			if ( ! $editing ) {
				$design_id = 'design_' . time() . '_' . wp_rand( 1000, 9999 );
			}

			$designs[ $design_id ] = $new_design;
			update_option( 'wetravel_trips_designs', $designs );

			$success_message = $editing ? 'Widget updated successfully.' : 'Widget created successfully.';

			// Generate shortcode for user.
			$shortcode = '[wetravel_trips widget="' . ( $keyword ? $keyword : $design_id ) . '"]';

			$redirect_url = add_query_arg(
				array(
					'page'     => 'wetravel-trips-create-design',
					'edit'     => $design_id,
					'updated'  => '1',
					'_wpnonce' => wp_create_nonce( 'wetravel_trips_edit_nonce' ),
				),
				admin_url( 'admin.php' )
			);
			wp_safe_redirect( $redirect_url );
			exit;
		}
	}

	?>
	<div class="wrap">
		<h1><?php echo $editing ? 'Edit Widget' : 'Create New Widget'; ?></h1>

		<div class="nav-tab-wrapper">
			<a href="?page=wetravel-trips-settings" class="nav-tab">Settings</a>
			<a href="?page=wetravel-trips-design-library" class="nav-tab">Widget Library</a>
			<a href="?page=wetravel-trips-create-design" class="nav-tab nav-tab-active">Create Widget</a>
		</div>

		<?php if ( isset( $success_message ) ) : ?>
			<div class="notice notice-success is-dismissible">
				<p><?php echo esc_html( $success_message ); ?></p>
				<?php if ( isset( $shortcode ) ) : ?>
					<p>Use this shortcode to display your widget: <code><?php echo esc_html( $shortcode ); ?></code></p>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if ( isset( $error_message ) ) : ?>
			<div class="notice notice-error is-dismissible">
				<p><?php echo esc_html( $error_message ); ?></p>
			</div>
		<?php endif; ?>

		<div class="wetravel-trips-create-design-container">
			<div class="wetravel-trips-design-form-and-preview">
				<div class="wetravel-trips-design-form">
					<form method="post" action="">
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
								<code>[wetravel_trips widget="<?php echo ! empty( $design['keyword'] ) ? esc_attr( $design['keyword'] ) : esc_attr( $design_id ); ?>"]</code>
								<button class="button button-small wetravel-trips-copy-shortcode"
										data-shortcode='[wetravel_trips widget="<?php echo ! empty( $design['keyword'] ) ? esc_attr( $design['keyword'] ) : esc_attr( $design_id ); ?>"]'>Copy</button>
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
}
