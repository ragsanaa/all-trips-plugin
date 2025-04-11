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
	'wetravel_trips_src',
	'wetravel_trips_slug',
	'wetravel_trips_env',
	'wetravel_trips_user_id',
	'wetravel_trips_display_type',
	'wetravel_trips_button_type',
	'wetravel_trips_button_color',
	'wetravel_trips_items_per_page',
	'wetravel_trips_items_per_row',
	'wetravel_trips_items_per_slide',
	'wetravel_trips_load_more_text',
	'wetravel_trips_designs',
);

foreach ( $options as $option ) {
	delete_option( $option );
}

// Delete any transients.
global $wpdb;
$like = $wpdb->esc_like( 'wetravel_trips_' ) . '%';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional transient cleanup logic
$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->options WHERE option_name LIKE %s AND option_name LIKE %s", $like, '%transient%' ) );

// Clear any scheduled hooks.
wp_clear_scheduled_hook( 'wetravel_trips_daily_cleanup' );

// Clear cache to ensure all deleted options are flushed.
wp_cache_flush();
