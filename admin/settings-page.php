<?php
/**
 * Admin settings page for WeTravel Widgets Plugin
 *
 * @package WordPress
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

ob_start();

/**
 * Sanitize embed code
 *
 * @param string $input User embed code.
 */
function wtwidget_sanitize_embed_code( $input ) {
	return wp_kses_post( $input ); // Allows safe HTML while stripping dangerous elements.
}

/**
 * Sanitize save time
 *
 * @param string $input Setting save time.
 */
function wtwidget_sanitize_text( $input ) {
	return sanitize_text_field( $input ); // Ensures plain text only.
}

/** Register settings */
function wtwidget_register_settings() {
	// Register embed code setting with explicit sanitization
	register_setting( 'wetravel_trips_options', 'wetravel_trips_embed_code', 'wetravel_trips_sanitize_embed_code' );


	// Register last saved timestamp with explicit sanitization
	register_setting( 'wetravel_trips_options', 'wetravel_trips_last_saved', 'wetravel_trips_sanitize_text' );

}
add_action('admin_init', 'wtwidget_register_settings');

/** Render Setting page */
function wetravel_trips_settings_page() {
	$embed_code     = get_option( 'wetravel_trips_embed_code', '' );
	$last_saved     = get_option( 'wetravel_trips_last_saved', '' );
	$has_embed_code = ! empty( $embed_code );

	// Check for widget usage
	$widget_usage = wtwidget_check_widget_usage();

	// Reset embed code if requested and no active widgets
	if ( isset( $_GET['reset_embed'] ) && 'true' === $_GET['reset_embed'] ) {
		// Only verify nonce when actually processing a reset action.
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'wetravel_trips_reset_nonce' ) ) {
			wp_die( 'Security check failed' );
		}

		// Check if widgets are in use
		if ($widget_usage['has_usage']) {
			wp_safe_redirect( add_query_arg('error', 'widgets_in_use', admin_url( 'admin.php?page=wetravel-trips-settings' )) );
			exit;
		}

		// Delete options
		delete_option( 'wetravel_trips_embed_code' );
		delete_option( 'wetravel_trips_last_saved' );
		delete_option( 'wetravel_trips_slug' );
		delete_option( 'wetravel_trips_env' );
		delete_option( 'wetravel_trips_user_id' );

		wp_safe_redirect( admin_url( 'admin.php?page=wetravel-trips-settings' ) );
		exit;
	}
	?>
	<div class="wrap">
		<h1>WeTravel Widgets Plugin - Settings</h1>

		<div class="nav-tab-wrapper">
			<a href="?page=wetravel-trips-instructions" class="nav-tab">Instructions</a>
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

			<?php if ( isset( $_GET['error'] ) && 'widgets_in_use' === $_GET['error'] ) : ?>
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
						<?php if (!$widget_usage['has_usage']) : ?>
							<?php
							// Create a reset link with a proper nonce.
							$reset_url = wp_nonce_url(
								admin_url( 'admin.php?page=wetravel-trips-settings&reset_embed=true' ),
								'wetravel_trips_reset_nonce',
								'_wpnonce'
							);
							?>
							<a href="<?php echo esc_url( $reset_url ); ?>" class="button button-secondary">Re-enter Embed Code</a>
						<?php else : ?>
							<p class="description">
								<span class="dashicons dashicons-info"></span>
								Cannot re-enter embed code while WeTravel widgets are in use. Please remove all widgets from your content first.
							</p>
						<?php endif; ?>
					</div>
				<?php else : ?>
					<form method="post" action="options.php" class="wetravel-trips-embed-form">
						<?php
						settings_fields( 'wetravel_trips_options' );
						do_settings_sections( 'wetravel_trips_options' );
						wp_nonce_field('wetravel_trips_settings_nonce', 'wetravel_trips_settings_nonce');
						?>
						<div class="wetravel-trips-embed-input-container">
							<textarea id="wetravel_trips_embed_code" name="wetravel_trips_embed_code" class="large-text code" rows="4" placeholder='Paste your WeTravel "All Trips" embed script here...'><?php echo esc_textarea( $embed_code ); ?></textarea>
							<p class="description"><?php esc_html_e('The plugin will extract the necessary details automatically.', 'wetravel-widgets'); ?></p>
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
	// Change the main menu page to point to the design library.
	add_menu_page(
		'WeTravel Widgets Plugin',
		'WeTravel Widgets',
		'manage_options',
		'wetravel-trips-design-library', // Change to design library slug.
		'wetravel_trips_design_library_page', // Use design library callback.
		'dashicons-location-alt'
	);

	// Add Widget Library as first submenu (will be duplicated as main).
	add_submenu_page(
		'wetravel-trips-design-library',
		'Widget Library',
		'Widget Library',
		'manage_options',
		'wetravel-trips-design-library', // Same as parent to make it the default page.
		'wetravel_trips_design_library_page'
	);

	// Add Create Widget submenu.
	add_submenu_page(
		'wetravel-trips-design-library',
		'Create Widget',
		'Create Widget',
		'manage_options',
		'wetravel-trips-create-design',
		'wtwidget_trip_create_design_page'
	);

	// Add instructions submenu.
	add_submenu_page(
		'wetravel-trips-design-library',
		'Instructions',
		'Instructions',
		'manage_options',
		'wetravel-trips-instructions',
		'wetravel_trips_instructions_page'
	);

	// Add Settings as submenu.
	add_submenu_page(
		'wetravel-trips-design-library',
		'Settings',
		'Settings',
		'manage_options',
		'wetravel-trips-settings',
		'wetravel_trips_settings_page'
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
		wp_enqueue_style( 'wetravel-trips-admin-styles', WETRAVEL_WIDGETS_PLUGIN_URL . 'admin/css/admin-styles.css', array(), filemtime( WETRAVEL_WIDGETS_PLUGIN_DIR . 'admin/css/admin-styles.css' ) );
		wp_enqueue_script( 'wetravel-trips-admin-scripts', WETRAVEL_WIDGETS_PLUGIN_URL . 'admin/js/admin-scripts.js', array( 'jquery', 'wp-color-picker' ), filemtime( WETRAVEL_WIDGETS_PLUGIN_DIR . 'admin/js/admin-scripts.js' ), true );
	}
}
add_action( 'admin_enqueue_scripts', 'wetravel_trips_admin_enqueue_scripts' );
?>
