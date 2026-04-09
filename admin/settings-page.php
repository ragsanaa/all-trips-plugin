<?php
/**
 * Admin setup page for WeTravel Widgets Plugin
 *
 * @package WordPress
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/** Note: Settings are handled by custom save function in includes/functions.php */

/**
 * Handle embed code reset action
 */
function wetravel_trips_handle_reset_embed() {
	// Only run on our admin page and if reset is requested
	if ( ! isset( $_GET['page'] ) || ! isset( $_GET['reset_embed'] ) ) {
		return;
	}

	// Verify nonce first before processing any data
	if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'wetravel_trips_reset_nonce' ) ) {
		return; // Silently return if nonce verification fails
	}

	// Now safely check the sanitized GET parameters
	$page = sanitize_text_field( wp_unslash( $_GET['page'] ) );
	$reset_embed = sanitize_text_field( wp_unslash( $_GET['reset_embed'] ) );

	if ( $page !== 'wetravel-trips-setup' ) {
		return;
	}

	if ( $reset_embed !== 'true' ) {
		return;
	}

	// Clear cache and check for widget usage to ensure fresh results
	wtwidget_clear_usage_cache();
	$widget_usage = wtwidget_check_widget_usage();

	// Check if widgets are in use
	if ( $widget_usage['has_usage'] ) {
		$error_redirect_url = add_query_arg(
			array(
				'error' => 'widgets_in_use',
				'error_nonce' => wp_create_nonce( 'wetravel_error_message' )
			),
			admin_url( 'admin.php?page=wetravel-trips-setup' )
		);
		wp_safe_redirect( $error_redirect_url );
		exit;
	}

	// Delete options
	delete_option( 'wetravel_trips_embed_code' );
	delete_option( 'wetravel_trips_last_saved' );
	delete_option( 'wetravel_trips_slug' );
	delete_option( 'wetravel_trips_env' );
	delete_option( 'wetravel_trips_user_id' );

	$has_consent = get_option( 'wetravel_consent_given', false );
	if ( $has_consent && function_exists( 'wetravel_track_user_state' ) ) {
		$wt_user_id = get_option( 'wetravel_trips_user_id', '' );
		$wt_user_slug = get_option( 'wetravel_trips_slug', '' );
		wetravel_track_user_state( $wt_user_id, $wt_user_slug, true, false, array() );
	}

	wp_safe_redirect( admin_url( 'admin.php?page=wetravel-trips-setup' ) );
	exit;
}
add_action( 'admin_init', 'wetravel_trips_handle_reset_embed' );

/** Render Setting page */
function wetravel_trips_setup_page() {
	$embed_code     = get_option( 'wetravel_trips_embed_code', '' );
	$last_saved     = get_option( 'wetravel_trips_last_saved', '' );
	$has_embed_code = ! empty( $embed_code );

	// Clear cache and check for widget usage to ensure fresh results
	wtwidget_clear_usage_cache();
	$widget_usage = wtwidget_check_widget_usage();
	?>
	<div class="wrap">
		<h1>WeTravel Widgets Plugin - Setup</h1>

		<?php
		// Display consent message if user just completed consent
		$consent_param = isset( $_GET['consent'] ) ? sanitize_text_field( wp_unslash( $_GET['consent'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only parameter, no data is processed.

		if ( $consent_param === 'allowed' ) : ?>
			<div class="notice notice-success is-dismissible" id="wetravel-consent-notice">
				<p>
					<strong>Thank you!</strong> You've opted in to help us improve WeTravel Widgets.
					We'll collect usage data to understand how you use the plugin and identify areas for improvement.
				</p>
				<button type="button" class="notice-dismiss" onclick="dismissConsentNotice()">
					<span class="screen-reader-text">Dismiss this notice.</span>
				</button>
			</div>
		<?php elseif ( $consent_param === 'skipped' ) : ?>
			<div class="notice notice-info is-dismissible" id="wetravel-consent-notice">
				<p>
					<strong>Consent skipped.</strong> You can always opt in later through the plugin settings if you change your mind.
				</p>
				<button type="button" class="notice-dismiss" onclick="dismissConsentNotice()">
					<span class="screen-reader-text">Dismiss this notice.</span>
				</button>
			</div>
		<?php endif; ?>

		<div class="nav-tab-wrapper">
			<a href="?page=wetravel-trips-instructions" class="nav-tab">Instructions</a>
			<a href="?page=wetravel-trips-setup" class="nav-tab nav-tab-active">Setup</a>
			<a href="?page=wetravel-trips-design-library" class="nav-tab">Widget Library</a>
			<a href="?page=wetravel-trips-create-design" class="nav-tab">Create Widget</a>
		</div>

		<div class="wetravel-trips-setup-container">
			<h2>WeTravel Embed Code</h2>
			<p>Configure your WeTravel integration by pasting your <b>All Trips</b> embed code below.</p>
			<?php
			// Safely check for saved parameter with nonce verification
			$saved_param = isset( $_GET['saved'] ) ? sanitize_text_field( wp_unslash( $_GET['saved'] ) ) : '';
			$display_nonce = isset( $_GET['display_nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['display_nonce'] ) ) : '';

			if ( $saved_param === 'true' && wp_verify_nonce( $display_nonce, 'wetravel_display_message' ) ) : ?>
				<div class="notice notice-success is-dismissible">
					<p>Embed code saved successfully!</p>
				</div>
			<?php endif; ?>

			<?php
			// Safely check for error parameter with nonce verification
			$error_param = isset( $_GET['error'] ) ? sanitize_text_field( wp_unslash( $_GET['error'] ) ) : '';
			$error_nonce = isset( $_GET['error_nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['error_nonce'] ) ) : '';

			if ( $error_param === 'widgets_in_use' && wp_verify_nonce( $error_nonce, 'wetravel_error_message' ) ) : ?>
				<div class="notice notice-error is-dismissible">
					<p><strong>Cannot reset embed code:</strong> There are active WeTravel widgets being used in your content.</p>
					<?php if (!empty($widget_usage['blocks'])) : ?>
						<p><strong>Blocks found in:</strong></p>
						<ul>
							<?php foreach ($widget_usage['blocks'] as $post) : ?>
								<li>
									<a href="<?php echo esc_url($post['edit_url']); ?>" target="_blank">
										<?php echo esc_html($post['title']); ?> (<?php echo esc_html($post['type']); ?>)
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
					<?php if (!empty($widget_usage['shortcodes'])) : ?>
						<p><strong>Shortcodes found in:</strong></p>
						<ul>
							<?php foreach ($widget_usage['shortcodes'] as $post) : ?>
								<li>
									<a href="<?php echo esc_url($post['edit_url']); ?>" target="_blank">
										<?php echo esc_html($post['title']); ?> (<?php echo esc_html($post['type']); ?>)
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
					<p>Please remove all WeTravel widgets from your content before resetting the embed code.</p>
				</div>
			<?php endif; ?>

			<div class="wetravel-trips-embed-form-container">
				<?php if ( $has_embed_code ) : ?>
					<div class="wetravel-trips-embed-info">
						<div class="wetravel-trips-embed-status">
							<span class="dashicons dashicons-yes-alt"></span>
							<span>Embed code saved successfully on <?php echo esc_html( $last_saved ); ?></span>
						</div>
						<div class="wetravel-trips-extracted-info">
							<p><strong>Slug:</strong> <?php echo esc_html( get_option( 'wetravel_trips_slug', '' ) ); ?></p>
							<p><strong>Environment:</strong> <?php echo esc_html( get_option( 'wetravel_trips_env', '' ) ); ?></p>
							<p><strong>WeTravel User ID:</strong> <?php echo esc_html( get_option( 'wetravel_trips_user_id', '' ) ); ?></p>
						</div>
					</div>
				<?php endif; ?>

				<!-- Always show the form to embed new all trips widget code -->
				<form method="post" action="options.php" class="wetravel-trips-embed-form">
					<?php
					settings_fields( 'wetravel_trips_options' );
					do_settings_sections( 'wetravel_trips_options' );
					wp_nonce_field('wetravel_trips_settings_nonce', 'wetravel_trips_settings_nonce');
					?>
					<div class="wetravel-trips-embed-input-container">
						<textarea id="wetravel_trips_embed_code" name="wetravel_trips_embed_code" rows="4" placeholder='Paste your WeTravel "All Trips" embed script here...'></textarea>
						<p class="description"><?php esc_html_e('The plugin will extract the necessary details automatically.', 'wetravel-widgets'); ?></p>
					</div>
					<div class="wetravel-trips-embed-button-container">
						<?php submit_button(); ?>
					</div>
				</form>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Handle main menu page redirect based on embed code setup
 */
function wetravel_trips_main_page() {
	$embed_code = get_option( 'wetravel_trips_embed_code', '' );

	if ( empty( $embed_code ) ) {
		// No embed code set up, redirect to settings
		wp_safe_redirect( admin_url( 'admin.php?page=wetravel-trips-setup' ) );
		exit;
	} else {
		// Embed code exists, redirect to design library
		wp_safe_redirect( admin_url( 'admin.php?page=wetravel-trips-design-library' ) );
		exit;
	}
}

/**
 * Handle main menu redirect action
 */
function wetravel_trips_handle_main_redirect() {
	// Only run on our main menu page - safely check the page parameter
	$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
	if ( $page !== 'wetravel-trips-main' ) {
		return;
	}

	// Verify user has admin capabilities
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	wetravel_trips_main_page();
}
add_action( 'admin_init', 'wetravel_trips_handle_main_redirect' );

/** Set menu and submenu */
function wetravel_trips_add_admin_menu() {
	// Main menu page with redirect logic.
	add_menu_page(
		'WeTravel Widgets Plugin',
		'WeTravel Widgets',
		'manage_options',
		'wetravel-trips-main', // Use main slug for redirect logic.
		'wetravel_trips_main_page', // Use main page callback.
		'dashicons-location-alt'
	);

	// Add Widget Library as first submenu.
	add_submenu_page(
		'wetravel-trips-main',
		'Widget Library',
		'Widget Library',
		'manage_options',
		'wetravel-trips-design-library',
		'wetravel_trips_design_library_page'
	);

	// Add Create Widget submenu.
	add_submenu_page(
		'wetravel-trips-main',
		'Create Widget',
		'Create Widget',
		'manage_options',
		'wetravel-trips-create-design',
		'wtwidget_trip_create_design_page'
	);

	// Add instructions submenu.
	add_submenu_page(
		'wetravel-trips-main',
		'Instructions',
		'Instructions',
		'manage_options',
		'wetravel-trips-instructions',
		'wetravel_trips_instructions_page'
	);

	// Add Settings as submenu.
	add_submenu_page(
		'wetravel-trips-main',
		'Setup',
		'Setup',
		'manage_options',
		'wetravel-trips-setup',
		'wetravel_trips_setup_page'
	);

	// Hidden error log page — not shown in menu, accessible via URL only.
	add_submenu_page(
		null,
		'Error Log',
		'Error Log',
		'manage_options',
		'wetravel-trips-error-log',
		'wetravel_trips_error_log_page'
	);
}
add_action( 'admin_menu', 'wetravel_trips_add_admin_menu' );

/**
 * Render the error log page.
 * Access via: admin.php?page=wetravel-trips-error-log
 */
function wetravel_trips_error_log_page() {
	$error_log = get_option( 'wetravel_error_log', array() );
	?>
	<div class="wrap">
		<h1>WeTravel Widgets — Error Log</h1>
		<p>Recent errors logged by the plugin (last 50 entries).</p>

		<?php if ( ! empty( $error_log ) ) : ?>
			<div style="display: flex; justify-content: flex-end; margin-bottom: 10px;">
				<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wetravel-trips-error-log&clear_error_log=true' ), 'wetravel_clear_error_log' ) ); ?>"
				   class="button button-secondary"
				   onclick="return confirm('Are you sure you want to clear the error log?');">
					Clear Log
				</a>
			</div>
			<div style="max-height: 600px; overflow-y: auto;">
				<table class="widefat striped">
					<thead>
						<tr>
							<th style="width: 160px;">Time</th>
							<th>Message</th>
							<th>Context</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( array_reverse( $error_log ) as $entry ) : ?>
							<tr>
								<td><code><?php echo esc_html( $entry['time'] ); ?></code></td>
								<td><?php echo esc_html( $entry['message'] ); ?></td>
								<td>
									<?php if ( ! empty( $entry['context'] ) ) : ?>
										<code style="word-break: break-all;"><?php echo esc_html( wp_json_encode( $entry['context'] ) ); ?></code>
									<?php else : ?>
										&mdash;
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php else : ?>
			<p style="color: #666; font-style: italic;">No errors logged.</p>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Handle clearing the error log.
 */
function wetravel_trips_handle_clear_error_log() {
	$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
	if ( 'wetravel-trips-error-log' !== $page || ! isset( $_GET['clear_error_log'] ) ) {
		return;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'wetravel_clear_error_log' ) ) {
		return;
	}

	wtwidget_clear_error_log();
	wp_safe_redirect( admin_url( 'admin.php?page=wetravel-trips-error-log' ) );
	exit;
}
add_action( 'admin_init', 'wetravel_trips_handle_clear_error_log' );

/**
 * Enqueue admin scripts and styles.
 *
 * @param string $hook Get all trips hook.
 */
function wetravel_trips_admin_enqueue_scripts( $hook ) {
	if ( strpos( $hook, 'wetravel-trips' ) !== false || strpos( $hook, 'wetravel-consent' ) !== false ) {
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_style( 'wetravel-trips-admin-styles', WETRAVEL_WIDGETS_PLUGIN_URL . 'admin/css/admin-styles.css', array(), filemtime( WETRAVEL_WIDGETS_PLUGIN_DIR . 'admin/css/admin-styles.css' ) );
		wp_enqueue_script( 'wetravel-trips-admin-scripts', WETRAVEL_WIDGETS_PLUGIN_URL . 'admin/js/admin-scripts.js', array( 'jquery', 'wp-color-picker' ), filemtime( WETRAVEL_WIDGETS_PLUGIN_DIR . 'admin/js/admin-scripts.js' ), true );

		// Localize script for AJAX - pass data to admin-scripts.js
		wp_localize_script( 'wetravel-trips-admin-scripts', 'wetravel_ajax', array(
			'ajaxurl'      => admin_url( 'admin-ajax.php' ),
			'rest_url'     => esc_url_raw( rest_url() ),
			'nonce'        => wp_create_nonce( 'wetravel_trips_nonce' ),
			'organizer_id' => get_option( 'wetravel_trips_user_id', '' )
		) );

		// Localize plugin settings
		wp_localize_script( 'wetravel-trips-admin-scripts', 'wetravelTripsSettings', array(
			'pluginUrl' => plugins_url( '', dirname( __FILE__ ) ) . '/'
		) );
	}
}
add_action( 'admin_enqueue_scripts', 'wetravel_trips_admin_enqueue_scripts' );

/**
 * Add JavaScript for consent notice dismissal and plugin state checking
 */
function wetravel_consent_notice_script() {
    // Only output script on our admin page for authorized users
    if (
        isset( $_GET['page'], $_GET['display_nonce'] ) &&
        sanitize_text_field( wp_unslash( $_GET['page'] ) ) === 'wetravel-trips-setup' &&
        wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['display_nonce'] ) ), 'wetravel_display_message' ) &&
        current_user_can( 'manage_options' )
    ) {
        ?>
        <script type="text/javascript">
        function dismissConsentNotice() {
            var notice = document.getElementById('wetravel-consent-notice');
            if (notice) {
                notice.style.display = 'none';
            }
        }

        </script>
        <?php
    }
}
add_action( 'admin_footer', 'wetravel_consent_notice_script' );
?>
