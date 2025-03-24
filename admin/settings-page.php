<?php
/**
 * Admin settings page for All Trips Plugin
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
function all_trips_sanitize_embed_code( $input ) {
	return wp_kses_post( $input ); // Allows safe HTML while stripping dangerous elements.
}

/**
 * Sanitize save time
 *
 * @param string $input Setting save time.
 */
function all_trips_sanitize_text( $input ) {
	return sanitize_text_field( $input ); // Ensures plain text only.
}

/** Register settings */
function all_trips_register_settings() {
	register_setting( 'all_trips_options', 'all_trips_embed_code', 'all_trips_sanitize_embed_code' );
	register_setting( 'all_trips_options', 'all_trips_last_saved', 'all_trips_sanitize_text' );
}
add_action( 'admin_init', 'all_trips_register_settings' );

/** Render Setting page */
function all_trips_settings_page() {
	$embed_code     = get_option( 'all_trips_embed_code', '' );
	$last_saved     = get_option( 'all_trips_last_saved', '' );
	$has_embed_code = ! empty( $embed_code );

	// Reset embed code if requested.
	if ( isset( $_GET['reset_embed'] ) && 'true' === $_GET['reset_embed'] ) {
		// Only verify nonce when actually processing a reset action.
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'all_trips_reset_nonce' ) ) {
			wp_die( 'Security check failed' );
		}

		delete_option( 'all_trips_embed_code' );
		delete_option( 'all_trips_last_saved' );
		wp_safe_redirect( admin_url( 'admin.php?page=all-trips-settings' ) );
		exit;
	}
	?>
	<div class="wrap">
		<h1>All Trips Plugin Settings</h1>

		<div class="nav-tab-wrapper">
			<a href="?page=all-trips-settings" class="nav-tab nav-tab-active">Settings</a>
			<a href="?page=all-trips-design-library" class="nav-tab">Design Library</a>
			<a href="?page=all-trips-create-design" class="nav-tab">Create Design</a>
		</div>

		<div class="all-trips-settings-container">
			<h2>WeTravel Embed Code</h2>
			<p>Configure your WeTravel integration by pasting your embed code below.</p>
			<?php if ( isset( $_GET['saved'] ) && 'true' === $_GET['saved'] ) : ?>
				<div class="notice notice-success is-dismissible">
					<p>Embed code saved successfully!</p>
				</div>
			<?php endif; ?>

			<div class="all-trips-embed-form-container">
				<?php if ( $has_embed_code ) : ?>
					<div class="all-trips-embed-info">
						<div class="all-trips-embed-status">
							<span class="dashicons dashicons-yes-alt"></span>
							<span>Embed code saved successfully on <?php echo esc_html( $last_saved ); ?></span>
						</div>
						<div class="all-trips-extracted-info">
							<p><strong>Slug:</strong> <?php echo esc_html( get_option( 'all_trips_slug', '' ) ); ?></p>
							<p><strong>Environment:</strong> <?php echo esc_html( get_option( 'all_trips_env', '' ) ); ?></p>
						</div>
						<?php
						// Create a reset link with a proper nonce.
						$reset_url = wp_nonce_url(
							admin_url( 'admin.php?page=all-trips-settings&reset_embed=true' ),
							'all_trips_reset_nonce',
							'_wpnonce'
						);
						?>
						<a href="<?php echo esc_url( $reset_url ); ?>" class="button button-secondary">Re-enter Embed Code</a>
					</div>
				<?php else : ?>
					<form method="post" action="options.php" class="all-trips-embed-form">
						<?php
						settings_fields( 'all_trips_options' );
						do_settings_sections( 'all_trips_options' );
						?>
						<div class="all-trips-embed-input-container">
							<textarea id="all_trips_embed_code" name="all_trips_embed_code" class="large-text code" rows="4" placeholder="Paste your WeTravel embed script here..."><?php echo esc_textarea( $embed_code ); ?></textarea>
							<p class="description">The plugin will extract the necessary details automatically.</p>
						</div>
						<div class="all-trips-embed-button-container">
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
function all_trips_add_admin_menu() {
	add_menu_page(
		'All Trips Plugin Settings',
		'All Trips',
		'manage_options',
		'all-trips-settings',
		'all_trips_settings_page',
		'dashicons-location-alt'
	);

	// Add Design Library submenu.
	add_submenu_page(
		'all-trips-settings',
		'Design Library',
		'Design Library',
		'manage_options',
		'all-trips-design-library',
		'all_trips_design_library_page'
	);

	// Add Create Design submenu.
	add_submenu_page(
		'all-trips-settings',
		'Create Design',
		'Create Design',
		'manage_options',
		'all-trips-create-design',
		'all_trips_create_design_page'
	);
}
add_action( 'admin_menu', 'all_trips_add_admin_menu' );

/**
 * Enqueue admin scripts and styles.
 *
 * @param string $hook Get all trips hook.
 */
function all_trips_admin_enqueue_scripts( $hook ) {
	if ( strpos( $hook, 'all-trips' ) !== false ) {
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_style( 'all-trips-admin-styles', ALL_TRIPS_PLUGIN_URL . 'admin/css/admin-styles.css', array(), filemtime( ALL_TRIPS_PLUGIN_DIR . 'admin/css/admin-styles.css' ) );
		wp_enqueue_script( 'all-trips-admin-scripts', ALL_TRIPS_PLUGIN_URL . 'admin/js/admin-scripts.js', array( 'jquery', 'wp-color-picker' ), filemtime( ALL_TRIPS_PLUGIN_DIR . 'admin/js/admin-scripts.js' ), true );
	}
}
add_action( 'admin_enqueue_scripts', 'all_trips_admin_enqueue_scripts' );
?>
