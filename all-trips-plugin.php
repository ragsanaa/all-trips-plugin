<?php
/**
 * Plugin Name: All Trips Plugin
 * Plugin URI:  https://wetravel.com
 * Description: A plugin to embed WeTravel trips dynamically.
 * Version:     1.0
 * Author:      WeTravel
 * Author URI:  https://wetravel.com
 * License:     GPL2
 * Text Domain: all-trips-plugin
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin path
define('ALL_TRIPS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ALL_TRIPS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include admin settings page
require_once ALL_TRIPS_PLUGIN_DIR . 'admin/settings-page.php';

// In all-trips-plugin.php, add this line to include the fetch-trips.php file
// Add this after the other require_once statements near the top of the file

// Include fetch trips functionality
require_once ALL_TRIPS_PLUGIN_DIR . 'includes/fetch-trips.php';

// Include functions
require_once ALL_TRIPS_PLUGIN_DIR . 'includes/functions.php';

// Enqueue styles and scripts for frontend
function all_trips_enqueue_scripts() {
    // Register main stylesheet
    wp_register_style(
        'all-trips-styles',
        ALL_TRIPS_PLUGIN_URL . 'assets/css/all-trips.css',
        array(),
        filemtime(ALL_TRIPS_PLUGIN_DIR . 'assets/css/all-trips.css')
    );

    // Enqueue main stylesheet
    wp_enqueue_style('all-trips-styles');
}
add_action('wp_enqueue_scripts', 'all_trips_enqueue_scripts');

// Enqueue scripts for block
function all_trips_enqueue_block_assets() {
    // Enqueue block editor script
    wp_enqueue_script(
        'all-trips-block',
        ALL_TRIPS_PLUGIN_URL . 'blocks/index.js',
        array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components'),
        filemtime(ALL_TRIPS_PLUGIN_DIR . 'blocks/index.js'),
        true
    );

    // Prepare settings
    $all_trips_settings = [
      'src' => get_option('all_trips_src', ''),
      'slug' => get_option('all_trips_slug', ''),
      'env' => get_option('all_trips_env', 'https://pre.wetravel.to'),
      'displayType' => get_option('all_trips_display_type', 'vertical'),
      'buttonType' => get_option('all_trips_button_type', 'book_now'),
      'buttonColor' => get_option('all_trips_button_color', '#33ae3f'),
      'itemsPerPage' => (int)get_option('all_trips_items_per_page', 10),
      'loadMoreText' => get_option('all_trips_load_more_text', 'Load More'),
      'designs' => get_option('all_trips_designs', array())
    ];

    // Localize the script with settings
    wp_localize_script('all-trips-block', 'allTripsSettings', $all_trips_settings);

    // Register editor styles
    wp_enqueue_style(
        'all-trips-editor-style',
        ALL_TRIPS_PLUGIN_URL . 'blocks/editor.css',
        array(),
        filemtime(ALL_TRIPS_PLUGIN_DIR . 'blocks/editor.css')
    );
}
add_action('enqueue_block_editor_assets', 'all_trips_enqueue_block_assets');

// Register block
function all_trips_register_block() {
    // Skip block registration if Gutenberg is not available
    if (!function_exists('register_block_type')) {
        return;
    }

    // In all-trips-plugin.php, update the register_block_type attributes
    register_block_type('all-trips/block', array(
        'editor_script' => 'all-trips-block',
        'render_callback' => 'all_trips_block_render',
        'attributes' => array(
            'designs' => array(
                'type' => 'array',
                'default' => array(),
            ),
            'selectedDesignID' => array(
                'type' => 'string',
                'default' => '',
            ),
            'src' => array(
                'type' => 'string',
                'default' => '',
            ),
            'slug' => array(
                'type' => 'string',
                'default' => '',
            ),
            'env' => array(
                'type' => 'string',
                'default' => 'https://pre.wetravel.to',
            ),
            'displayType' => array(
                'type' => 'string',
                'default' => 'vertical',
            ),
            'buttonType' => array(
                'type' => 'string',
                'default' => 'book_now',
            ),
            'buttonText' => array(
                'type' => 'string',
                'default' => '',  // Empty string by default
            ),
            'buttonColor' => array(
                'type' => 'string',
                'default' => '#33ae3f',
            ),
            'itemsPerPage' => array(
                'type' => 'number',
                'default' => 10,
            ),
            'loadMoreText' => array(
                'type' => 'string',
                'default' => 'Load More',
            ),
        ),
    ));
    }
add_action('init', 'all_trips_register_block');

// Include the block render function
require_once ALL_TRIPS_PLUGIN_DIR . 'includes/block-renderer.php';

// Include shortcode functionality
require_once ALL_TRIPS_PLUGIN_DIR . 'includes/shortcode.php';

// Include admin design library page
require_once ALL_TRIPS_PLUGIN_DIR . 'admin/design-library-page.php';

// Include admin create design page
require_once ALL_TRIPS_PLUGIN_DIR . 'admin/create-design-page.php';

// Register shortcode
function all_trips_register_shortcode() {
    add_shortcode('all_trips', 'all_trips_shortcode');
}
add_action('init', 'all_trips_register_shortcode');

// Add this function to clear transient timeouts
function all_trips_clear_transients() {
  global $wpdb;

  $sql = "DELETE FROM $wpdb->options WHERE option_name LIKE '%transient_timeout_settings_errors%'";
  $wpdb->query($sql);
}
register_activation_hook(__FILE__, 'all_trips_clear_transients');

// Plugin activation hook
function all_trips_activation() {
    // Create necessary directories if they don't exist
    $dirs = array(
        ALL_TRIPS_PLUGIN_DIR . 'assets',
        ALL_TRIPS_PLUGIN_DIR . 'assets/css',
        ALL_TRIPS_PLUGIN_DIR . 'assets/js',
        ALL_TRIPS_PLUGIN_DIR . 'includes',
        ALL_TRIPS_PLUGIN_DIR . 'blocks',
    );

    foreach ($dirs as $dir) {
        if (!file_exists($dir)) {
            wp_mkdir_p($dir);
        }
    }

    // Create empty files if they don't exist
    $files = array(
        'assets/css/all-trips.css' => '',
        'assets/js/pagination.js' => '',
        'assets/js/carousel.js' => '',
        'blocks/editor.css' => '',
        'includes/block-renderer.php' => '<?php
// Block renderer file
if (!defined(\'ABSPATH\')) {
    exit;
}

// Include the render function
require_once ALL_TRIPS_PLUGIN_DIR . \'includes/render-functions.php\';
',
        'includes/render-functions.php' => '',
        'includes/shortcode.php' => '<?php
// Shortcode file
if (!defined(\'ABSPATH\')) {
    exit;
}
',
    );

    foreach ($files as $file => $content) {
        $filepath = ALL_TRIPS_PLUGIN_DIR . $file;
        if (!file_exists($filepath)) {
            file_put_contents($filepath, $content);
        }
    }
}
register_activation_hook(__FILE__, 'all_trips_activation');
