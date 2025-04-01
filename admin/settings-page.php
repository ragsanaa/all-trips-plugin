<?php
/**
 * Admin settings page for WeTravel Trips Plugin
 *
 * @package WordPress
 */

ob_start();

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sanitize embed code
 *
 * @param string $input User embed code.
 */
function wetravel_trips_sanitize_embed_code( $input ) {
	return wp_kses_post( $input ); // Allows safe HTML while stripping dangerous elements.
}

/**
 * Sanitize save time
 *
 * @param string $input Setting save time.
 */
function wetravel_trips_sanitize_text( $input ) {
	return sanitize_text_field( $input ); // Ensures plain text only.
}

/** Register settings */
function wetravel_trips_register_settings() {
	register_setting( 'wetravel_trips_options', 'wetravel_trips_embed_code', 'wetravel_trips_sanitize_embed_code' );
	register_setting( 'wetravel_trips_options', 'wetravel_trips_last_saved', 'wetravel_trips_sanitize_text' );
}
add_action( 'admin_init', 'wetravel_trips_register_settings' );

/** Render Setting page */
function wetravel_trips_settings_page() {
	$embed_code     = get_option( 'wetravel_trips_embed_code', '' );
	$last_saved     = get_option( 'wetravel_trips_last_saved', '' );
	$has_embed_code = ! empty( $embed_code );

	// Reset embed code if requested.
	if ( isset( $_GET['reset_embed'] ) && 'true' === $_GET['reset_embed'] ) {
		// Only verify nonce when actually processing a reset action.
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'wetravel_trips_reset_nonce' ) ) {
			wp_die( 'Security check failed' );
		}

		delete_option( 'wetravel_trips_embed_code' );
		delete_option( 'wetravel_trips_last_saved' );
		wp_safe_redirect( admin_url( 'admin.php?page=wetravel-trips-settings' ) );
		exit;
	}
	?>
	<div class="wrap">
		<h1>WeTravel Trips Plugin Settings</h1>

		<div class="nav-tab-wrapper">
			<a href="?page=wetravel-trips-settings" class="nav-tab nav-tab-active">Settings</a>
			<a href="?page=wetravel-trips-design-library" class="nav-tab">Widget Library</a>
			<a href="?page=wetravel-trips-create-design" class="nav-tab">Create Widget</a>
		</div>

		<div class="wetravel-trips-settings-container">
			<h2>WeTravel Embed Code</h2>
			<p>Configure your WeTravel integration by pasting your <b>All Trips</b> embed code below.</p>
			<?php if ( isset( $_GET['saved'] ) && 'true' === $_GET['saved'] ) : ?>
				<div class="notice notice-success is-dismissible">
					<p>Embed code saved successfully!</p>
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
						</div>
						<?php
						// Create a reset link with a proper nonce.
						$reset_url = wp_nonce_url(
							admin_url( 'admin.php?page=wetravel-trips-settings&reset_embed=true' ),
							'wetravel_trips_reset_nonce',
							'_wpnonce'
						);
						?>
						<a href="<?php echo esc_url( $reset_url ); ?>" class="button button-secondary">Re-enter Embed Code</a>
					</div>
				<?php else : ?>
					<form method="post" action="options.php" class="wetravel-trips-embed-form">
						<?php
						settings_fields( 'wetravel_trips_options' );
						do_settings_sections( 'wetravel_trips_options' );
						?>
						<div class="wetravel-trips-embed-input-container">
							<textarea id="wetravel_trips_embed_code" name="wetravel_trips_embed_code" class="large-text code" rows="4" placeholder='Paste your WeTravel "All Trips" embed script here...'><?php echo esc_textarea( $embed_code ); ?></textarea>
							<p class="description">The plugin will extract the necessary details automatically.</p>
						</div>
						<div class="wetravel-trips-embed-button-container">
							<?php submit_button(); ?>
						</div>
					</form>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<?php
}

/** Set menu and submenu */
function wetravel_trips_add_admin_menu() {
	add_menu_page(
		'WeTravel Trips Plugin Settings',
		'WeTravel Trips',
		'manage_options',
		'wetravel-trips-settings',
		'wetravel_trips_settings_page',
		'dashicons-location-alt'
	);

	// Add Widget Library submenu.
	add_submenu_page(
		'wetravel-trips-settings',
		'Widget Library',
		'Widget Library',
		'manage_options',
		'wetravel-trips-design-library',
		'wetravel_trips_design_library_page'
	);

	// Add Create Widget submenu.
	add_submenu_page(
		'wetravel-trips-settings',
		'Create Widget',
		'Create Widget',
		'manage_options',
		'wetravel-trips-create-design',
		'wetravel_trips_create_design_page'
	);
}
add_action( 'admin_menu', 'wetravel_trips_add_admin_menu' );

/**
 * Enqueue admin scripts and styles.
 *
 * @param string $hook Get all trips hook.
 */
function wetravel_trips_admin_enqueue_scripts( $hook ) {
	if ( strpos( $hook, 'wetravel-trips' ) !== false ) {
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_style( 'wetravel-trips-admin-styles', WETRAVEL_TRIPS_PLUGIN_URL . 'admin/css/admin-styles.css', array(), filemtime( WETRAVEL_TRIPS_PLUGIN_DIR . 'admin/css/admin-styles.css' ) );
		wp_enqueue_script( 'wetravel-trips-admin-scripts', WETRAVEL_TRIPS_PLUGIN_URL . 'admin/js/admin-scripts.js', array( 'jquery', 'wp-color-picker' ), filemtime( WETRAVEL_TRIPS_PLUGIN_DIR . 'admin/js/admin-scripts.js' ), true );
	}
}
add_action( 'admin_enqueue_scripts', 'wetravel_trips_admin_enqueue_scripts' );
?>
