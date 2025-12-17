<?php
/**
 * Plugin Name: WeTravel Widgets
 * Plugin URI:  https://github.com/ragsanaa/all-trips-plugin
 * Description: A plugin to display WeTravel widgets on your WordPress site.
 * Version:     1.2.3
 * Author:      WeTravel
 * Author URI:  https://github.com/wetravel-com
 * License:     GPLv2 or later
 * Text Domain: wetravel-widgets
 *
 * @package WordPress
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check PHP Version
if ( version_compare( PHP_VERSION, '7.0', '<' ) ) {
	add_action( 'admin_notices', function() {
		?>
		<div class="notice notice-error">
			<p><?php esc_html_e( 'WeTravel Widgets plugin requires PHP version 7.0 or higher. Please upgrade your PHP version or contact your hosting provider.', 'wetravel-widgets' ); ?></p>
		</div>
		<?php
	});
	return;
}

// Check WordPress Version
if ( version_compare( get_bloginfo( 'version' ), '5.0', '<' ) ) {
	add_action( 'admin_notices', function() {
		?>
		<div class="notice notice-error">
			<p><?php esc_html_e( 'WeTravel Widgets plugin requires WordPress version 5.0 or higher. Please upgrade WordPress to continue using this plugin.', 'wetravel-widgets' ); ?></p>
		</div>
		<?php
	});
	return;
}

// Define plugin path.
if ( ! defined( 'WETRAVEL_WIDGETS_PLUGIN_FILE' ) ) {
	define( 'WETRAVEL_WIDGETS_PLUGIN_FILE', __FILE__ );
}
define( 'WETRAVEL_WIDGETS_PLUGIN_DIR', plugin_dir_path( WETRAVEL_WIDGETS_PLUGIN_FILE ) );
define( 'WETRAVEL_WIDGETS_PLUGIN_URL', plugin_dir_url( WETRAVEL_WIDGETS_PLUGIN_FILE ) );

// Define plugin version constant for tracking
if ( ! defined( 'WETRAVEL_PLUGIN_VERSION' ) ) {
	$plugin_file = WP_PLUGIN_DIR . '/wetravel-widgets/wetravel-widgets.php';
	$plugin_data = get_file_data( $plugin_file, [ 'Version' => 'Version' ], 'plugin' );
	define( 'WETRAVEL_PLUGIN_VERSION', $plugin_data['Version'] );
}

// Define plugin name and slug constants
if ( ! defined( 'WETRAVEL_PLUGIN_NAME' ) ) {
	define( 'WETRAVEL_PLUGIN_NAME', 'WeTravel Widgets' );
}
if ( ! defined( 'WETRAVEL_PLUGIN_SLUG' ) ) {
	define( 'WETRAVEL_PLUGIN_SLUG', 'wetravel-widgets' );
}

// Include admin settings page.
require_once WETRAVEL_WIDGETS_PLUGIN_DIR . 'admin/settings-page.php';

// Include consent page.
require_once WETRAVEL_WIDGETS_PLUGIN_DIR . 'admin/consent-page.php';

// Include fetch trips functionality.
require_once WETRAVEL_WIDGETS_PLUGIN_DIR . 'includes/fetch-trips.php';

// Include functions.
require_once WETRAVEL_WIDGETS_PLUGIN_DIR . 'includes/functions.php';

/** Enqueue styles and scripts for frontend. */
function wtwidget_enqueue_frontend_scripts() {
	// Always enqueue on the frontend so builders like Elementor (which store
	// content in post meta, not post_content) also receive styles in preview.

	// Ensure Dashicons is available on the frontend for icons used in markup
	wp_enqueue_style('dashicons');

    // Register main stylesheet.
	wp_register_style(
		'wetravel-trips-styles',
		WETRAVEL_WIDGETS_PLUGIN_URL . 'assets/css/wetravel-trips.css',
		array(),
		filemtime( WETRAVEL_WIDGETS_PLUGIN_DIR . 'assets/css/wetravel-trips.css' )
	);

	wp_add_inline_style(
		'wetravel-trips-styles',
		':root { --button-color: ' . esc_attr( get_option( 'wetravel_trips_button_color', '#33ae3f' ) ) . '; --items-per-row: ' . esc_attr( get_option( 'wetravel_trips_items_per_row', 3 ) ) . '; }'
	);

	// Enqueue the stylesheet after registration and inline vars
	wp_enqueue_style('wetravel-trips-styles');

}
add_action( 'wp_enqueue_scripts', 'wtwidget_enqueue_frontend_scripts' );

/**  Enqueue scripts for block. */
function wtwidget_enqueue_block_assets() {
	// Register and enqueue block editor stylesheet.
	wp_register_style(
		'wetravel-trips-editor-style',
		WETRAVEL_WIDGETS_PLUGIN_URL . 'blocks/editor.css',
		array(),
		filemtime( WETRAVEL_WIDGETS_PLUGIN_DIR . 'blocks/editor.css' )
	);

	// Enqueue block editor script.
	wp_enqueue_script(
		'wetravel-trips-block',
		WETRAVEL_WIDGETS_PLUGIN_URL . 'blocks/index.js',
		array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components' ),
		filemtime( WETRAVEL_WIDGETS_PLUGIN_DIR . 'blocks/index.js' ),
		true
	);

	// Prepare settings.
	$wetravel_trips_settings = array(
		'src'            => get_option( 'wetravel_trips_src', '' ),
		'slug'           => get_option( 'wetravel_trips_slug', '' ),
		'env'            => get_option( 'wetravel_trips_env', 'https://pre.wetravel.to' ),
		'wetravelUserID' => get_option( 'wetravel_trips_user_id', '' ),
		'displayType'    => get_option( 'wetravel_trips_display_type', 'vertical' ),
		'buttonType'     => get_option( 'wetravel_trips_button_type', 'book_now' ),
		'buttonColor'    => get_option( 'wetravel_trips_button_color', '#33ae3f' ),
		'itemsPerPage'   => (int) get_option( 'wetravel_trips_items_per_page', 10 ),
		'itemsPerRow'    => (int) get_option( 'wetravel_trips_items_per_row', 3 ),
		'itemsPerSlide'  => (int) get_option( 'wetravel_trips_items_per_slide', 1 ),
		'loadMoreText'   => get_option( 'wetravel_trips_load_more_text', 'Load More' ),
		'designs'        => get_option( 'wetravel_trips_designs', array() ),
		'searchVisibility' => (bool) get_option( 'wetravel_trips_search_visibility', false ),
		'pluginUrl'      => WETRAVEL_WIDGETS_PLUGIN_URL,
	);

	// Localize the script with settings.
	wp_localize_script( 'wetravel-trips-block', 'wetravelTripsSettings', $wetravel_trips_settings );
}
add_action( 'enqueue_block_editor_assets', 'wtwidget_enqueue_block_assets' );

/**  Register block. */
function wtwidget_register_block() {
	// Check if Gutenberg blocks are available
	if ( ! function_exists( 'register_block_type' ) ) {
		// Show notice that Gutenberg blocks are not available but shortcodes still work
		add_action( 'admin_notices', function() {
			?>
			<div class="notice notice-warning is-dismissible">
				<p><?php esc_html_e( 'WeTravel Widgets: Gutenberg blocks are not available. You can still use shortcodes or upgrade WordPress/install Gutenberg plugin for block support.', 'wetravel-widgets' ); ?></p>
			</div>
			<?php
		});
		return;
	}

	register_block_type(
		'wetravel-trips/block',
		array(
			'editor_script'   => 'wetravel-trips-block',
			'editor_style'    => 'wetravel-trips-editor-style',
			'render_callback' => 'wtwidget_trips_block_render',
			'attributes'      => array(
				'designs'          => array(
					'type'    => 'array',
					'default' => array(),
				),
				'selectedDesignID' => array(
					'type'    => 'string',
					'default' => '',
				),
				'src'              => array(
					'type'    => 'string',
					'default' => '',
				),
				'slug'             => array(
					'type'    => 'string',
					'default' => '',
				),
				'env'              => array(
					'type'    => 'string',
					'default' => 'https://pre.wetravel.to',
				),
				'wetravelUserID'   => array(
					'type'    => 'string',
					'default' => '',
				),
				'displayType'      => array(
					'type'    => 'string',
					'default' => 'vertical',
				),
				'buttonType'       => array(
					'type'    => 'string',
					'default' => 'book_now',
				),
				'buttonText'       => array(
					'type'    => 'string',
					'default' => '',  // Empty string by default.
				),
				'buttonColor'      => array(
					'type'    => 'string',
					'default' => '#33ae3f',
				),
				'itemsPerPage'     => array(
					'type'    => 'number',
					'default' => 10,
				),
				'itemsPerRow'      => array(
					'type'    => 'number',
					'default' => 3,
				),
				'itemsPerSlide'    => array(
					'type'    => 'number',
					'default' => 1,
				),
				'loadMoreText'     => array(
					'type'    => 'string',
					'default' => 'Load More',
				),
				'searchVisibility' => array(
					'type'    => 'boolean',
					'default' => false,
				),
				'borderRadius'     => array(
					'type'    => 'number',
					'default' => 6,
				),
				'integrationType' => array(
					'type'    => 'string',
					'default' => 'block',
				),
				'wtWidgetType'      => array(
					'type'    => 'string',
					'default' => 'all-trips',
				),
			),
		)
	);
}
add_action( 'init', 'wtwidget_register_block' );

// Include the block render function.
require_once WETRAVEL_WIDGETS_PLUGIN_DIR . 'includes/block-renderer.php';

// Include shortcode functionality.
require_once WETRAVEL_WIDGETS_PLUGIN_DIR . 'includes/shortcode.php';

// Include admin design library page.
require_once WETRAVEL_WIDGETS_PLUGIN_DIR . 'admin/design-library-page.php';

// Include admin create design page.
require_once WETRAVEL_WIDGETS_PLUGIN_DIR . 'admin/create-design-page.php';

// Include admin instructions page.
require_once WETRAVEL_WIDGETS_PLUGIN_DIR . 'admin/instructions-page.php';

// Include tracking functionality.
require_once WETRAVEL_WIDGETS_PLUGIN_DIR . 'includes/tracking.php';

// Include deactivation form.
require_once WETRAVEL_WIDGETS_PLUGIN_DIR . 'admin/deactivation-form.php';

/**
 * Add database indexes for better performance
 */
function wtwidget_add_database_indexes() {
	global $wpdb;

	// Check if indexes already exist to avoid errors
	$indexes = $wpdb->get_results("SHOW INDEX FROM {$wpdb->posts} WHERE Key_name IN ('idx_post_content_wetravel', 'idx_post_status_type')");

	if (empty($indexes)) {
		// Add index for post_content searches (first 100 characters)
		$wpdb->query("ALTER TABLE {$wpdb->posts} ADD INDEX idx_post_content_wetravel (post_content(100))");

		// Add composite index for post_status and post_type
		$wpdb->query("ALTER TABLE {$wpdb->posts} ADD INDEX idx_post_status_type (post_status, post_type)");
	}
}

/**  Add this function to clear transient timeouts. */
function wtwidget_clear_transients() {
	global $wpdb;

	// Fetch all options (cached by WordPress).
	$all_options = wp_load_alloptions();

	foreach ( $all_options as $option_name => $value ) {
		if ( strpos( $option_name, 'transient_timeout_settings_errors' ) !== false ) {
			delete_option( $option_name );
		}
	}

	// Clear cache after deleting.
	wp_cache_flush();
}
register_activation_hook( __FILE__, 'wtwidget_clear_transients' );

/** Plugin activation hook. */
function wtwidget_activation() {
	// Create directory in uploads folder for any user-generated content
	$upload_dir = wp_upload_dir();
	$wetravel_upload_dir = $upload_dir['basedir'] . '/wetravel-widgets';

	if ( ! file_exists( $wetravel_upload_dir ) ) {
		wp_mkdir_p( $wetravel_upload_dir );

		// Protect the directory from direct access
		$htaccess_content = "Options -Indexes\nDeny from all";
		$htaccess_file = $wetravel_upload_dir . '/.htaccess';

		if (!function_exists('WP_Filesystem')) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		WP_Filesystem();
		global $wp_filesystem;

		if ($wp_filesystem) {
			$wp_filesystem->put_contents($htaccess_file, $htaccess_content, FS_CHMOD_FILE);
		}
	}

	// Clear any previous deactivation data when reactivating
	delete_transient( 'wetravel_deactivation_reason' );
	delete_transient( 'wetravel_deactivation_reason_details' );
	delete_option( 'wetravel_deactivation_feedback' );

	// Initialize default plugin settings in the database
	$default_settings = array(
		'wetravel_trips_button_color' => '#33ae3f',
		'wetravel_trips_items_per_row' => 3,
		'wetravel_trips_items_per_page' => 10,
		'wetravel_trips_display_type' => 'vertical',
		'wetravel_trips_button_type' => 'book_now',
		'wetravel_trips_env' => 'https://pre.wetravel.to',
		'wetravel_trips_load_more_text' => 'Load More',
		'wetravel_trips_search_visibility' => false,
	);

	foreach ($default_settings as $key => $value) {
		if (get_option($key) === false) {
			add_option($key, $value);
		}
	}

	// Set activation flag to show consent notice
	set_transient( 'wetravel_activation_consent_notice', true, 60 * 60 * 24 * 7 ); // 1 week

	// Trigger plugin activation action for tracking
	do_action( 'wetravel_plugin_activated' );

	// Add database indexes for better performance
	wtwidget_add_database_indexes();

	// Track plugin activation state
	if ( function_exists( 'wetravel_track_plugin_state' ) ) {
		wetravel_track_plugin_state( 'activated' );
	}

	// Update user state to reflect activation (clear deactivation reasons and update status)
	if ( function_exists( 'wetravel_track_user_state' ) ) {
		wetravel_track_user_state( null, null, true ); // Force plugin state to active (true)
	}

	// Clear any existing cache on activation to ensure fresh data
	wp_cache_flush();
}
register_activation_hook( __FILE__, 'wtwidget_activation' );

// Add deactivation hook to clean up if needed
function wtwidget_deactivation() {
	// Clean up transients
	wtwidget_clear_transients();

	// Track deactivation state
	if ( function_exists( 'wetravel_track_user_state' ) ) {
		$wt_user_id = get_option( 'wetravel_trips_user_id', '' );
		$wt_user_slug = get_option( 'wetravel_trips_slug', '' );
		$consent_given = get_option( 'wetravel_consent_given', false );

		// Get deactivation reason if available
		$deactivation_reason = get_transient( 'wetravel_deactivation_reason' ) ?: 'unspecified';
		$deactivation_reason_details = get_transient( 'wetravel_deactivation_reason_details' ) ?: '';

		// Prepare deactivation data
		$deactivation_data = array(
			'deactivation_reason' => $deactivation_reason,
			'deactivation_reason_details' => $deactivation_reason_details
		);

		// Track based on consent status - bypass consent check for deactivation
		if ( $consent_given ) {
			// User gave consent - track with full user data
			wetravel_track_user_state( $wt_user_id, $wt_user_slug, false, false, $deactivation_data, true );
		} else {
			// User skipped consent - track with anonymous data
			wetravel_track_user_state( $wt_user_id, $wt_user_slug, false, true, $deactivation_data, true );
		}
	}

	if ( function_exists( 'wetravel_track_plugin_state' ) ) {
		wetravel_track_plugin_state( 'deactivated' );
	}
}
register_deactivation_hook( __FILE__, 'wtwidget_deactivation' );

// Add uninstall hook to clean up when plugin is deleted
function wtwidget_uninstall() {
	// Remove all plugin options
	$options = array(
		'wetravel_trips_src',
		'wetravel_trips_slug',
		'wetravel_trips_env',
		'wetravel_trips_user_id',
		'wetravel_trips_button_color',
		'wetravel_trips_items_per_row',
		'wetravel_trips_items_per_page',
		'wetravel_trips_display_type',
		'wetravel_trips_button_type',
		'wetravel_trips_load_more_text',
		'wetravel_trips_search_visibility',
		'wetravel_consent_given',
		'wetravel_consent_timestamp',
		'wetravel_consent_type',
	);

	foreach ($options as $option) {
		delete_option($option);
	}

	// Optionally remove the uploads directory
	$upload_dir = wp_upload_dir();
	$wetravel_upload_dir = $upload_dir['basedir'] . '/wetravel-widgets';

	if (file_exists($wetravel_upload_dir)) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();
		global $wp_filesystem;

		if ($wp_filesystem) {
			$wp_filesystem->rmdir($wetravel_upload_dir, true);
		}
	}
}
register_uninstall_hook( __FILE__, 'wtwidget_uninstall' );
