<?php
/**
 * Plugin Name: WeTravel Trips Widget
 * Plugin URI:  https://wetravel.com
 * Description: A plugin to embed WeTravel trips dynamically.
 * Version:     1.0
 * Author:      WeTravel
 * Author URI:  https://wetravel.com
 * License:     GPL2
 * Text Domain: wetravel-trips-widget
 *
 * @package WordPress
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin path.
define( 'WETRAVEL_TRIPS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WETRAVEL_TRIPS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include admin settings page.
require_once WETRAVEL_TRIPS_PLUGIN_DIR . 'admin/settings-page.php';

// In wetravel-trips-widget.php, add this line to include the fetch-trips.php file.
// Add this after the other require_once statements near the top of the file.

// Include fetch trips functionality.
require_once WETRAVEL_TRIPS_PLUGIN_DIR . 'includes/fetch-trips.php';

// Include functions.
require_once WETRAVEL_TRIPS_PLUGIN_DIR . 'includes/functions.php';

/** Enqueue styles and scripts for frontend. */
function wetravel_trips_enqueue_scripts() {
	// Register main stylesheet.
	wp_register_style(
		'wetravel-trips-styles',
		WETRAVEL_TRIPS_PLUGIN_URL . 'assets/css/wetravel-trips.css',
		array(),
		filemtime( WETRAVEL_TRIPS_PLUGIN_DIR . 'assets/css/wetravel-trips.css' )
	);

	// Enqueue main stylesheet.
	wp_enqueue_style( 'wetravel-trips-styles' );

	wp_add_inline_style(
		'wetravel-trips-styles',
		':root { --button-color: ' . get_option( 'wetravel_trips_button_color', '#33ae3f' ) . '; --items-per-row: ' . get_option( 'wetravel_trips_items_per_row', 3 ) . '; }'
	);
}
add_action( 'wp_enqueue_scripts', 'wetravel_trips_enqueue_scripts' );

/**  Enqueue scripts for block. */
function wetravel_trips_enqueue_block_assets() {
	// Enqueue block editor script.
	wp_enqueue_script(
		'wetravel-trips-block',
		WETRAVEL_TRIPS_PLUGIN_URL . 'blocks/index.js',
		array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components' ),
		filemtime( WETRAVEL_TRIPS_PLUGIN_DIR . 'blocks/index.js' ),
		true
	);

	// Prepare settings.
	$wetravel_trips_settings = array(
		'src'          	 => get_option( 'wetravel_trips_src', '' ),
		'slug'         	 => get_option( 'wetravel_trips_slug', '' ),
		'env'          	 => get_option( 'wetravel_trips_env', 'https://pre.wetravel.to' ),
		'wetravelUserID' => get_option( 'wetravel_trips_user_id', '' ),
		'displayType'    => get_option( 'wetravel_trips_display_type', 'vertical' ),
		'buttonType'     => get_option( 'wetravel_trips_button_type', 'book_now' ),
		'buttonColor'    => get_option( 'wetravel_trips_button_color', '#33ae3f' ),
		'itemsPerPage'   => (int) get_option( 'wetravel_trips_items_per_page', 10 ),
		'itemsPerRow'    => (int) get_option( 'wetravel_trips_items_per_row', 3 ),
		'itemsPerSlide'  => (int) get_option( 'wetravel_trips_items_per_slide', 3 ),
		'loadMoreText'   => get_option( 'wetravel_trips_load_more_text', 'Load More' ),
		'designs'        => get_option( 'wetravel_trips_designs', array() ),
	);

	// Localize the script with settings.
	wp_localize_script( 'wetravel-trips-block', 'wetravelTripsSettings', $wetravel_trips_settings );
}
add_action( 'enqueue_block_editor_assets', 'wetravel_trips_enqueue_block_assets' );

/**  Register block. */
function wetravel_trips_register_block() {
	// Skip block registration if Gutenberg is not available.
	if ( ! function_exists( 'register_block_type' ) ) {
		return;
	}

	// In wetravel-trips-widget.php, update the register_block_type attributes.
	register_block_type(
		'wetravel-trips/block',
		array(
			'editor_script'   => 'wetravel-trips-block',
			'render_callback' => 'wetravel_trips_block_render',
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
			),
		)
	);
}
add_action( 'init', 'wetravel_trips_register_block' );

// Include the block render function.
require_once WETRAVEL_TRIPS_PLUGIN_DIR . 'includes/block-renderer.php';

// Include shortcode functionality.
require_once WETRAVEL_TRIPS_PLUGIN_DIR . 'includes/shortcode.php';

// Include admin design library page.
require_once WETRAVEL_TRIPS_PLUGIN_DIR . 'admin/design-library-page.php';

// Include admin create design page.
require_once WETRAVEL_TRIPS_PLUGIN_DIR . 'admin/create-design-page.php';

/**  Register shortcode. */
function wetravel_trips_register_shortcode() {
	add_shortcode( 'wetravel_trips', 'wetravel_trips_shortcode' );
}
add_action( 'init', 'wetravel_trips_register_shortcode' );

/**  Add this function to clear transient timeouts. */
function wetravel_trips_clear_transients() {
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
register_activation_hook( __FILE__, 'wetravel_trips_clear_transients' );

/** Plugin activation hook. */
function wetravel_trips_activation() {
	// Create necessary directories if they don't exist.
	$dirs = array(
		WETRAVEL_TRIPS_PLUGIN_DIR . 'assets',
		WETRAVEL_TRIPS_PLUGIN_DIR . 'assets/css',
		WETRAVEL_TRIPS_PLUGIN_DIR . 'assets/js',
		WETRAVEL_TRIPS_PLUGIN_DIR . 'includes',
		WETRAVEL_TRIPS_PLUGIN_DIR . 'blocks',
	);

	foreach ( $dirs as $dir ) {
		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
		}
	}

	// Create empty files if they don't exist.
	$files = array(
		'assets/css/wetravel-trips.css'    => '',
		'assets/js/pagination.js'     => '',
		'assets/js/carousel.js'       => '',
		'blocks/editor.css'           => '',
		'includes/block-renderer.php' => '<?php
// Block renderer file.
if (!defined(\'ABSPATH\')) {
    exit;
}
',
		'includes/shortcode.php'      => '<?php
// Shortcode file.
if (!defined(\'ABSPATH\')) {
    exit;
}
',
	);

	global $wp_filesystem;

	if ( ! function_exists( 'WP_Filesystem' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}

	WP_Filesystem();

	foreach ( $files as $file => $content ) {
		$filepath = WETRAVEL_TRIPS_PLUGIN_DIR . $file;

		if ( ! file_exists( $filepath ) ) {
			$wp_filesystem->put_contents( $filepath, $content, FS_CHMOD_FILE );
		}
	}
}
register_activation_hook( __FILE__, 'wetravel_trips_activation' );
