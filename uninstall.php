<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package WordPress
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete all plugin options.
$options = array(
	'all_trips_src',
	'all_trips_slug',
	'all_trips_env',
	'all_trips_display_type',
	'all_trips_button_type',
	'all_trips_button_color',
	'all_trips_items_per_page',
	'all_trips_items_per_row',
	'all_trips_load_more_text',
	'all_trips_designs'
);

foreach ( $options as $option ) {
	delete_option( $option );
}

// Delete any transients.
global $wpdb;
$like = $wpdb->esc_like( 'all_trips_' ) . '%';
$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '$like' AND option_name LIKE '%transient%'" );

// Clear any scheduled hooks.
wp_clear_scheduled_hook( 'all_trips_daily_cleanup' );

// Clear cache to ensure all deleted options are flushed.
wp_cache_flush();
