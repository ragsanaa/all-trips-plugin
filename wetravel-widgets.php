<?php
/**
 * Plugin Name: WeTravel Widgets
 * Plugin URI:  https://github.com/ragsanaa/all-trips-plugin
 * Description: A plugin to display WeTravel widgets on your WordPress site.
 * Version:     1.2
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

// Include admin settings page.
require_once WETRAVEL_WIDGETS_PLUGIN_DIR . 'admin/settings-page.php';

// In wetravel-widgets.php, add this line to include the fetch-trips.php file.
// Add this after the other require_once statements near the top of the file.

// Include fetch trips functionality.
require_once WETRAVEL_WIDGETS_PLUGIN_DIR . 'includes/fetch-trips.php';

// Include functions.
require_once WETRAVEL_WIDGETS_PLUGIN_DIR . 'includes/functions.php';

/** Enqueue styles and scripts for frontend. */
function wtwidget_enqueue_frontend_scripts() {
	// Register main stylesheet.
	wp_register_style(
		'wetravel-trips-styles',
		WETRAVEL_WIDGETS_PLUGIN_URL . 'assets/css/wetravel-trips.css',
		array(),
		filemtime( WETRAVEL_WIDGETS_PLUGIN_DIR . 'assets/css/wetravel-trips.css' )
	);

	// Enqueue main stylesheet.
	wp_enqueue_style( 'wetravel-trips-styles' );

	wp_add_inline_style(
		'wetravel-trips-styles',
		':root { --button-color: ' . esc_attr( get_option( 'wetravel_trips_button_color', '#33ae3f' ) ) . '; --items-per-row: ' . esc_attr( get_option( 'wetravel_trips_items_per_row', 3 ) ) . '; }'
	);

	// Register and enqueue trips loader script
	wp_register_script(
		'wetravel-trips-loader',
		WETRAVEL_WIDGETS_PLUGIN_URL . 'assets/js/trips-loader.js',
		array('jquery'),
		filemtime( WETRAVEL_WIDGETS_PLUGIN_DIR . 'assets/js/trips-loader.js' ),
		true
	);

	wp_enqueue_script('wetravel-trips-loader');

	// Localize the trips loader script
	wp_localize_script(
		'wetravel-trips-loader',
		'wetravelTripsData',
		array(
			'ajaxurl' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('wetravel_trips_ajax_nonce'),
			'security_error' => esc_html__('Security check failed', 'wetravel-widgets'),
			'loading_error' => esc_html__('Error loading trips', 'wetravel-widgets')
		)
	);
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
		'itemsPerSlide'  => (int) get_option( 'wetravel_trips_items_per_slide', 3 ),
		'loadMoreText'   => get_option( 'wetravel_trips_load_more_text', 'Load More' ),
		'designs'        => get_option( 'wetravel_trips_designs', array() ),
		'searchVisibility' => (bool) get_option( 'wetravel_trips_search_visibility', false ),
	);

	// Localize the script with settings.
	wp_localize_script( 'wetravel-trips-block', 'wetravelTripsSettings', $wetravel_trips_settings );
}
add_action( 'enqueue_block_editor_assets', 'wtwidget_enqueue_block_assets' );

/**  Register block. */
function wtwidget_register_block() {
	// Skip block registration if Gutenberg is not available.
	if ( ! function_exists( 'register_block_type' ) ) {
		return;
	}

	// In wetravel-widgets.php, update the register_block_type attributes.
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
					'default' => 3,
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

/**  Register shortcode. */
function wtwidget_register_shortcode() {
	add_shortcode( 'wetravel_trips', 'wtwidget_trips_shortcode' );
}
add_action( 'init', 'wtwidget_register_shortcode' );

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
}
register_activation_hook( __FILE__, 'wtwidget_activation' );

// Add deactivation hook to clean up if needed
function wtwidget_deactivation() {
	// Clean up transients
	wtwidget_clear_transients();
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
